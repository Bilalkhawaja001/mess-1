<?php

namespace Tests\Feature;

use App\Models\Billing;
use App\Models\BillingCycle;
use App\Models\Department;
use App\Models\Guest;
use App\Models\GuestMeal;
use App\Models\Member;
use App\Models\MemberLedger;
use App\Models\Mess;
use App\Models\MonthClosure;
use App\Models\MonthlyAttendance;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PurchaseOrder;
use App\Models\Item;
use App\Models\Vendor;
use App\Models\Menu;
use App\Models\KitchenIssue;
use App\Models\MealPlan;
use App\Models\GoodsReceipt;
use App\Models\RatePolicy;
use App\Models\Role;
use App\Models\User;
use App\Services\Billing\BillingCorrectionService;
use App\Services\Billing\BillingGenerationService;
use App\Services\Payments\PaymentAttemptService;
use App\Services\Payments\PaymentTransactionService;
use App\Services\MonthClosureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RepairFinancialFlowsTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $role = Role::query()->where('code', 'SUPER_ADMIN')->first() ?? Role::query()->create(['code' => 'SUPER_ADMIN', 'name' => 'Super Admin']);

        return User::query()->create([
            'username' => 'admin',
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('Pass@123'),
            'role_id' => $role->id,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    private function member(array $overrides = []): Member
    {
        return Member::query()->create(array_merge([
            'member_code' => 'M001',
            'name' => 'Member One',
            'department_name' => 'IT',
            'join_date' => '2026-03-01',
            'leave_date' => null,
            'is_active' => true,
        ], $overrides));
    }

    public function test_billing_generation_uses_locked_monthly_attendance_and_is_idempotent(): void
    {
        $admin = $this->adminUser();
        $member = $this->member();

        RatePolicy::query()->create([
            'rate_type' => 'PER_DAY',
            'value' => 150,
            'effective_from' => '2026-03-01',
            'effective_to' => null,
            'is_active' => true,
            'approved_at' => now(),
        ]);

        MonthlyAttendance::query()->create([
            'month_cycle' => '2026-03',
            'member_id' => $member->id,
            'present_days' => 12,
            'approved_by_user_id' => $admin->id,
            'approved_at' => now(),
            'is_locked' => true,
        ]);

        $service = app(BillingGenerationService::class);
        $first = $service->generate('2026-03', $admin->id, 100);
        $second = $service->generate('2026-03', $admin->id, 100);

        $bill = Billing::query()->firstOrFail();
        $ledger = MemberLedger::query()->where('ref_type', 'BILL')->firstOrFail();

        $this->assertSame('generated', $first['status']);
        $this->assertSame('already_generated', $second['status']);
        $this->assertSame(12, $bill->active_days);
        $this->assertSame('1800.00', number_format((float) $bill->net_payable, 2, '.', ''));
        $this->assertSame($bill->id, $ledger->ref_id);
        $this->assertDatabaseHas('billing_cycles', ['month_cycle' => '2026-03', 'is_closed' => 0]);
    }

    public function test_billing_correction_posts_delta_to_member_ledger(): void
    {
        $admin = $this->adminUser();
        $member = $this->member();

        $billing = Billing::query()->create([
            'month_cycle' => '2026-03',
            'member_id' => $member->id,
            'active_days' => 10,
            'rate_per_day' => 100,
            'base_amount' => 1000,
            'extras_amount' => 0,
            'net_payable' => 1000,
            'is_locked' => true,
            'generated_by_user_id' => $admin->id,
            'billing_status' => 'POSTED',
        ]);

        MemberLedger::query()->create([
            'member_id' => $member->id,
            'entry_date' => '2026-03-31',
            'debit' => 1000,
            'credit' => 0,
            'ref_type' => 'BILL',
            'ref_id' => $billing->id,
            'balance_after' => 1000,
            'reason_code' => 'BILLING_GENERATE',
            'posted_by_user_id' => $admin->id,
        ]);

        app(BillingCorrectionService::class)->correct($billing, 850, 'fix', $admin->id);

        $this->assertDatabaseHas('member_ledgers', [
            'member_id' => $member->id,
            'ref_type' => 'BILL_CORRECTION',
            'ref_id' => $billing->id,
            'credit' => 150,
        ]);
    }

    public function test_hard_reset_removes_billing_ledgers_and_runs(): void
    {
        $admin = $this->adminUser();
        $member = $this->member();

        BillingCycle::query()->create(['month_cycle' => '2026-03', 'status' => 'OPEN', 'is_closed' => false]);
        $billing = Billing::query()->create([
            'month_cycle' => '2026-03',
            'member_id' => $member->id,
            'active_days' => 10,
            'rate_per_day' => 100,
            'base_amount' => 1000,
            'extras_amount' => 0,
            'net_payable' => 1000,
            'is_locked' => true,
            'generated_by_user_id' => $admin->id,
            'billing_status' => 'POSTED',
        ]);
        \App\Models\BillingRun::query()->create([
            'month_cycle' => '2026-03',
            'scope_hash' => 'x',
            'config_hash' => 'y',
            'status' => 'DONE',
            'inserted_count' => 1,
            'skipped_count' => 0,
            'created_by_user_id' => $admin->id,
        ]);
        MemberLedger::query()->create([
            'member_id' => $member->id,
            'entry_date' => '2026-03-31',
            'debit' => 1000,
            'credit' => 0,
            'ref_type' => 'BILL',
            'ref_id' => $billing->id,
            'balance_after' => 1000,
            'reason_code' => 'BILLING_GENERATE',
            'posted_by_user_id' => $admin->id,
        ]);

        app(MonthClosureService::class)->hardReset('2026-03', $admin->id, 'reset');

        $this->assertDatabaseMissing('billings', ['id' => $billing->id]);
        $this->assertDatabaseMissing('member_ledgers', ['ref_type' => 'BILL', 'ref_id' => $billing->id]);
        $this->assertDatabaseCount('billing_runs', 0);
        $this->assertDatabaseHas('month_closures', ['month_cycle' => '2026-03', 'status' => MonthClosure::STATUS_HARD_RESET]);
    }

    public function test_payment_approval_posts_member_ledger_once(): void
    {
        $admin = $this->adminUser();
        $member = $this->member();
        $method = PaymentMethod::query()->create(['code' => 'CASH', 'name' => 'Cash', 'is_manual' => true, 'is_active' => true]);
        $bill = Billing::query()->create([
            'month_cycle' => '2026-03',
            'member_id' => $member->id,
            'active_days' => 10,
            'rate_per_day' => 100,
            'base_amount' => 1000,
            'extras_amount' => 0,
            'net_payable' => 1000,
            'is_locked' => true,
            'generated_by_user_id' => $admin->id,
            'billing_status' => 'POSTED',
        ]);

        $attemptService = app(PaymentAttemptService::class);
        $transactionService = app(PaymentTransactionService::class);
        [$payment, $attempt] = $attemptService->createAttempt($member->id, $bill->id, $method->id, 500, $admin->id);
        $txn = $transactionService->recordFromAttempt($payment, $attempt, ['status' => Payment::STATUS_INITIATED], $admin->id);

        $this->actingAs($admin);
        $this->post("/admin/payments/{$payment->id}/approve")->assertRedirect();
        $this->post("/admin/payments/{$payment->id}/approve")->assertRedirect();

        $this->assertSame(1, MemberLedger::query()->where('ref_type', 'PAYMENT')->where('ref_id', $payment->id)->count());
        $this->assertDatabaseHas('payment_reconciliations', ['payment_id' => $payment->id]);
        $this->assertDatabaseHas('payment_transactions', ['id' => $txn->id, 'status' => Payment::STATUS_SUCCESS]);
    }

    public function test_guest_approval_creates_department_chargeback_entry(): void
    {
        $department = Department::query()->create(['name' => 'IT', 'code' => 'IT', 'is_active' => true]);
        $mess = Mess::query()->create(['name' => 'Main Mess', 'code' => 'MAIN', 'department_id' => $department->id, 'is_active' => true]);
        $guest = Guest::query()->create(['name' => 'Guest One', 'department' => 'IT']);
        $meal = GuestMeal::query()->create([
            'guest_id' => $guest->id,
            'meal_date' => '2026-03-14',
            'meal_type' => 'Lunch',
            'quantity' => 2,
            'rate' => 120,
            'amount' => 240,
        ]);

        $admin = $this->adminUser();
        $this->actingAs($admin);
        $this->post("/admin/guests/meals/{$meal->id}/approve")->assertRedirect();

        $this->assertDatabaseHas('department_ledgers', [
            'department_id' => $department->id,
            'mess_id' => $mess->id,
            'reference_type' => GuestMeal::class,
            'reference_id' => $meal->id,
            'entry_type' => 'DEBIT',
            'amount' => 240,
        ]);
    }

    public function test_dashboard_has_real_bound_metrics(): void
    {
        $admin = $this->adminUser();
        $member = $this->member();
        BillingCycle::query()->create(['month_cycle' => '2026-03', 'status' => 'OPEN', 'is_closed' => false]);
        Billing::query()->create([
            'month_cycle' => '2026-03',
            'member_id' => $member->id,
            'active_days' => 10,
            'rate_per_day' => 100,
            'base_amount' => 1000,
            'extras_amount' => 0,
            'net_payable' => 1000,
            'is_locked' => true,
            'generated_by_user_id' => $admin->id,
            'billing_status' => 'POSTED',
        ]);
        MemberLedger::query()->create([
            'member_id' => $member->id,
            'entry_date' => '2026-03-31',
            'debit' => 0,
            'credit' => 400,
            'ref_type' => 'PAYMENT',
            'ref_id' => 1,
            'balance_after' => -400,
            'reason_code' => 'PAYMENT_MANUAL_VERIFIED',
            'posted_by_user_id' => $admin->id,
        ]);
        Payment::query()->create([
            'member_id' => $member->id,
            'bill_id' => 1,
            'payment_date' => '2026-03-10',
            'amount' => 400,
            'currency' => 'PKR',
            'method' => 'CASH',
            'status' => Payment::STATUS_PENDING,
            'posted_by_user_id' => $admin->id,
        ]);

        $this->actingAs($admin);
        $response = $this->get('/admin/dashboard');
        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats) {
            return $stats['users'] >= 1
                && $stats['members'] >= 1
                && $stats['open_cycles'] >= 1
                && array_key_exists('pending_payments', $stats)
                && array_key_exists('collections', $stats)
                && array_key_exists('collected', $stats)
                && array_key_exists('recent_cycles', $stats)
                && array_key_exists('recentCycles', $stats)
                && array_key_exists('recent_activity', $stats)
                && array_key_exists('recentActivity', $stats)
                && array_key_exists('billable', $stats)
                && array_key_exists('outstanding', $stats);
        });
        $response->assertSee('400.00');
        $response->assertSee('M001 PAYMENT #1');
    }

    public function test_billing_correction_recomputes_downstream_ledger_balances(): void
    {
        $admin = $this->adminUser();
        $member = $this->member();

        $billing = Billing::query()->create([
            'month_cycle' => '2026-03',
            'member_id' => $member->id,
            'active_days' => 10,
            'rate_per_day' => 100,
            'base_amount' => 1000,
            'extras_amount' => 0,
            'net_payable' => 1000,
            'is_locked' => true,
            'generated_by_user_id' => $admin->id,
            'billing_status' => 'POSTED',
        ]);

        MemberLedger::query()->create([
            'member_id' => $member->id,
            'entry_date' => '2026-03-01',
            'debit' => 1000,
            'credit' => 0,
            'ref_type' => 'BILL',
            'ref_id' => $billing->id,
            'balance_after' => 1000,
            'reason_code' => 'BILLING_GENERATE',
            'posted_by_user_id' => $admin->id,
        ]);

        MemberLedger::query()->create([
            'member_id' => $member->id,
            'entry_date' => '2026-03-15',
            'debit' => 0,
            'credit' => 200,
            'ref_type' => 'PAYMENT',
            'ref_id' => 22,
            'balance_after' => 800,
            'reason_code' => 'PAYMENT_MANUAL_VERIFIED',
            'posted_by_user_id' => $admin->id,
        ]);

        app(BillingCorrectionService::class)->correct($billing, 850, 'fix', $admin->id);

        $paymentLedger = MemberLedger::query()->where('ref_type', 'PAYMENT')->where('ref_id', 22)->firstOrFail();
        $correctionLedger = MemberLedger::query()->where('ref_type', 'BILL_CORRECTION')->where('ref_id', $billing->id)->firstOrFail();

        $this->assertSame('800.00', number_format((float) $paymentLedger->balance_after, 2, '.', ''));
        $this->assertSame('650.00', number_format((float) $correctionLedger->balance_after, 2, '.', ''));
    }

    public function test_hard_reset_recomputes_remaining_member_ledgers(): void
    {
        $admin = $this->adminUser();
        $member = $this->member();

        $billing = Billing::query()->create([
            'month_cycle' => '2026-03',
            'member_id' => $member->id,
            'active_days' => 10,
            'rate_per_day' => 100,
            'base_amount' => 1000,
            'extras_amount' => 0,
            'net_payable' => 1000,
            'is_locked' => true,
            'generated_by_user_id' => $admin->id,
            'billing_status' => 'POSTED',
        ]);

        MemberLedger::query()->create([
            'member_id' => $member->id,
            'entry_date' => '2026-03-01',
            'debit' => 1000,
            'credit' => 0,
            'ref_type' => 'BILL',
            'ref_id' => $billing->id,
            'balance_after' => 1000,
            'reason_code' => 'BILLING_GENERATE',
            'posted_by_user_id' => $admin->id,
        ]);

        $paymentLedger = MemberLedger::query()->create([
            'member_id' => $member->id,
            'entry_date' => '2026-03-20',
            'debit' => 0,
            'credit' => 300,
            'ref_type' => 'PAYMENT',
            'ref_id' => 23,
            'balance_after' => 700,
            'reason_code' => 'PAYMENT_MANUAL_VERIFIED',
            'posted_by_user_id' => $admin->id,
        ]);

        app(MonthClosureService::class)->hardReset('2026-03', $admin->id, 'reset');

        $paymentLedger->refresh();
        $this->assertSame('-300.00', number_format((float) $paymentLedger->balance_after, 2, '.', ''));
    }

    public function test_reports_index_uses_current_paid_statuses(): void
    {
        $admin = $this->adminUser();
        $member = $this->member();
        Billing::query()->create([
            'month_cycle' => '2026-03',
            'member_id' => $member->id,
            'active_days' => 10,
            'rate_per_day' => 100,
            'base_amount' => 1000,
            'extras_amount' => 0,
            'net_payable' => 1000,
            'is_locked' => true,
            'generated_by_user_id' => $admin->id,
            'billing_status' => 'POSTED',
        ]);

        Payment::query()->create([
            'member_id' => $member->id,
            'bill_id' => 1,
            'payment_date' => '2026-03-10',
            'amount' => 400,
            'currency' => 'PKR',
            'method' => 'CASH',
            'status' => Payment::STATUS_SUCCESS,
            'posted_by_user_id' => $admin->id,
        ]);

        $this->actingAs($admin);
        $response = $this->get('/admin/reports?month_cycle=2026-03');
        $response->assertOk();
        $response->assertViewHas('recoveryRows', function (array $rows) {
            return count($rows) === 1
                && (float) $rows[0]['paid'] === 400.0
                && (float) $rows[0]['outstanding'] === 600.0;
        });
    }

    public function test_kitchen_and_procurement_approvals_are_explicit_about_schema_limits(): void
    {
        $admin = $this->adminUser();
        $item = Item::query()->create(['name' => 'Rice', 'sku' => 'RICE-1', 'unit' => 'kg', 'is_active' => true]);
        $menuId = DB::table('menus')->insertGetId([
            'name' => 'Lunch Menu',
            'meal_type' => 'Lunch',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $plan = MealPlan::query()->create(['plan_date' => '2026-03-11', 'menu_id' => $menuId, 'planned_servings' => 10]);
        $issue = KitchenIssue::query()->create(['issue_date' => '2026-03-11', 'item_id' => $item->id, 'quantity' => 5, 'remarks' => 'initial']);
        $vendor = Vendor::query()->create(['name' => 'Vendor A']);
        $po = PurchaseOrder::query()->create(['vendor_id' => $vendor->id, 'po_number' => 'PO-1', 'po_date' => '2026-03-11', 'status' => 'ISSUED']);
        $grn = GoodsReceipt::query()->create(['purchase_order_id' => $po->id, 'grn_number' => 'GRN-1', 'received_date' => '2026-03-11']);

        $this->actingAs($admin);
        $this->post("/admin/kitchen/plans/{$plan->id}/approve")->assertRedirect();
        $this->post("/admin/kitchen/issues/{$issue->id}/approve")->assertRedirect();
        $this->post("/admin/procurement/po/{$po->id}/approve")->assertRedirect();
        $this->post("/admin/procurement/grn/{$grn->id}/approve")->assertRedirect();

        $po->refresh();
        $this->assertSame('RECEIVED', $po->status);
    }
}
