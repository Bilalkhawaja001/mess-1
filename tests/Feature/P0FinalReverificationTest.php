<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Billing;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class P0FinalReverificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_p0_workflow_end_to_end_with_audit_and_exports(): void
    {
        $adminRole = Role::query()->create(['code' => 'ADMIN', 'name' => 'Admin']);
        $memberRole = Role::query()->create(['code' => 'MEMBER', 'name' => 'Member']);

        $admin = User::query()->create([
            'username' => 'admin',
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('Pass@123'),
            'role_id' => $adminRole->id,
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $memberUser = User::query()->create([
            'username' => 'member1',
            'name' => 'Member One',
            'email' => 'member1@example.com',
            'password' => Hash::make('Pass@123'),
            'role_id' => $memberRole->id,
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $member = Member::query()->create([
            'member_code' => 'M001',
            'name' => 'Member One',
            'department_name' => 'IT',
            'join_date' => '2026-01-01',
            'is_active' => true,
            'user_id' => $memberUser->id,
        ]);

        $billing = Billing::query()->create([
            'month_cycle' => '2026-03',
            'member_id' => $member->id,
            'active_days' => 20,
            'rate_per_day' => 100,
            'base_amount' => 2000,
            'extras_amount' => 100,
            'net_payable' => 2100,
            'is_locked' => false,
            'generated_by_user_id' => $admin->id,
            'billing_status' => 'POSTED',
        ]);

        $payment = Payment::query()->create([
            'member_id' => $member->id,
            'payment_date' => '2026-03-10',
            'amount' => 500,
            'method' => 'CASH',
            'reference_no' => 'R1',
            'notes' => 'seed',
            'status' => 'DRAFT',
            'posted_by_user_id' => $admin->id,
        ]);

        $this->get('/admin/dashboard')->assertRedirect('/login');

        $this->actingAs($admin);

        $this->post('/admin/month-governance/close', ['month_cycle' => '2026-03', 'reason' => 'close'])->assertStatus(302);
        $this->post('/admin/month-governance/reopen', ['month_cycle' => '2026-03', 'reason' => 'reopen'])->assertStatus(302);
        $this->post('/admin/month-governance/hard-reset', ['month_cycle' => '2026-03', 'reason' => 'hard'])->assertStatus(302);

        $this->post("/admin/billing/{$billing->id}/correct", ['new_net_payable' => 1999, 'reason' => 'correction'])->assertStatus(302);

        $this->post("/admin/payments/{$payment->id}/edit", [
            'payment_date' => '2026-03-11',
            'amount' => 550,
            'method' => 'CASH',
            'reference_no' => 'R2',
            'notes' => 'edited',
            'reason' => 'payment-edit',
        ])->assertStatus(302);

        $file = UploadedFile::fake()->createWithContent('opening.csv', "member_code,amount\nM001,1500\n");
        $this->post('/admin/ledger/import', ['file' => $file])->assertStatus(302);
        $this->post('/admin/ledger/recompute', ['member_id' => $member->id])->assertStatus(302);

        $this->post('/admin/auth/password-reset/request', ['username' => 'admin'])->assertStatus(302);
        $token = session('reset_token');
        $this->assertNotEmpty($token);
        $this->post('/admin/auth/password-reset/consume', ['token' => $token, 'new_password' => 'Pass@123'])->assertStatus(302);

        $this->post('/admin/auth/password-change', ['current_password' => 'Pass@123', 'new_password' => 'Pass@123'])->assertStatus(302);

        $csv = $this->get('/admin/summary?month_cycle=2026-03&export=csv');
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csv->headers->get('content-type'));
        $this->assertStringContainsString('Member Code,Member Name,Net Payable', $csv->streamedContent());

        $xlsx = $this->get('/admin/summary?month_cycle=2026-03&export=xlsx');
        $xlsx->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', (string) $xlsx->headers->get('content-type'));

        $xlsxPath = storage_path('app/testing-p0-summary.xlsx');
        file_put_contents($xlsxPath, $xlsx->streamedContent());
        $loaded = IOFactory::load($xlsxPath);
        $sheet = $loaded->getActiveSheet();
        $this->assertSame('Member Code', (string) $sheet->getCell('A1')->getValue());
        $this->assertSame('TOTAL', (string) $sheet->getCell('A4')->getValue());

        $actions = AuditLog::query()->pluck('action')->all();
        foreach ([
            'month.closed',
            'month.reopened',
            'month.hard_reset',
            'billing.corrected',
            'payment.edited',
            'ledger.opening_imported',
            'ledger.recomputed',
            'password.reset.requested',
            'password.reset.completed',
            'password.changed',
            'summary.export.csv',
            'summary.export.xlsx',
        ] as $action) {
            $this->assertContains($action, $actions, "Missing audit action: {$action}");
        }

        $this->assertGreaterThanOrEqual(12, AuditLog::query()->count());
    }
}
