<?php

namespace Tests\Feature;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\Item;
use App\Models\KitchenIssue;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\StockTransaction;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KitchenIssueReportTest extends TestCase
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

    private function item(string $name, string $sku, string $uom = 'kg'): Item
    {
        return Item::query()->create([
            'name' => $name,
            'sku' => $sku,
            'uom' => $uom,
            'category' => 'Kitchen',
            'reorder_level' => 0,
            'is_active' => true,
        ]);
    }

    private function postInbound(Item $item, string $date, float $qty, float $unitCost): void
    {
        $vendor = Vendor::query()->create(['name' => 'Vendor '.$item->id]);
        $po = PurchaseOrder::query()->create([
            'vendor_id' => $vendor->id,
            'po_number' => 'PO-'.$item->id.'-'.str_replace('-', '', $date).'-'.rand(100, 999),
            'po_date' => $date,
            'status' => 'RECEIVED',
        ]);
        $grn = GoodsReceipt::query()->create([
            'purchase_order_id' => $po->id,
            'grn_number' => 'GRN-'.$item->id.'-'.str_replace('-', '', $date).'-'.rand(100, 999),
            'received_date' => $date,
        ]);
        GoodsReceiptLine::query()->create([
            'goods_receipt_id' => $grn->id,
            'item_id' => $item->id,
            'qty_received' => $qty,
            'unit_cost' => $unitCost,
        ]);
        StockTransaction::query()->create([
            'item_id' => $item->id,
            'txn_type' => 'GRN',
            'quantity' => $qty,
            'unit_cost' => $unitCost,
            'reference_type' => GoodsReceipt::class,
            'reference_id' => $grn->id,
            'txn_at' => $date,
            'remarks' => 'GRN posting',
        ]);
    }

    private function approvedIssue(Item $item, string $date, float $qty, string $remarks = null): KitchenIssue
    {
        return KitchenIssue::query()->create([
            'issue_date' => $date,
            'item_id' => $item->id,
            'quantity' => $qty,
            'status' => 'approved',
            'remarks' => $remarks,
        ]);
    }

    private function draftIssue(Item $item, string $date, float $qty): KitchenIssue
    {
        return KitchenIssue::query()->create([
            'issue_date' => $date,
            'item_id' => $item->id,
            'quantity' => $qty,
            'status' => 'draft',
        ]);
    }

    public function test_report_page_loads(): void
    {
        $admin = $this->adminUser();
        $item = $this->item('Rice', 'RICE-001');
        $this->approvedIssue($item, '2026-04-05', 5);

        $this->actingAs($admin)
            ->get('/admin/kitchen/reports/issues?from_date=2026-04-01&to_date=2026-04-30')
            ->assertOk()
            ->assertSee('Approved Kitchen Issue Report');
    }

    public function test_only_approved_kitchen_issues_are_included_and_draft_issues_are_excluded(): void
    {
        $admin = $this->adminUser();
        $item = $this->item('Dal', 'DAL-001');
        $this->approvedIssue($item, '2026-04-05', 5);
        $this->draftIssue($item, '2026-04-06', 7);

        $this->actingAs($admin)
            ->get('/admin/kitchen/reports/issues?from_date=2026-04-01&to_date=2026-04-30')
            ->assertOk()
            ->assertSee('5.000')
            ->assertDontSee('12.000');
    }

    public function test_date_filtering_works(): void
    {
        $admin = $this->adminUser();
        $item = $this->item('Oil', 'OIL-001', 'ltr');
        $this->approvedIssue($item, '2026-04-01', 3);
        $this->approvedIssue($item, '2026-04-20', 4);

        $this->actingAs($admin)
            ->get('/admin/kitchen/reports/issues?from_date=2026-04-10&to_date=2026-04-30')
            ->assertOk()
            ->assertSee('4.000')
            ->assertDontSee('7.000');
    }

    public function test_item_filter_works(): void
    {
        $admin = $this->adminUser();
        $rice = $this->item('Rice', 'RICE-001');
        $dal = $this->item('Dal', 'DAL-001');
        $this->approvedIssue($rice, '2026-04-05', 5);
        $this->approvedIssue($dal, '2026-04-05', 8);

        $response = $this->actingAs($admin)
            ->get('/admin/kitchen/reports/issues?from_date=2026-04-01&to_date=2026-04-30&item_id='.$rice->id);

        $response->assertOk()->assertSee('Rice');
        $response->assertSee('RICE-001');
        $response->assertSee('5.000');
        $response->assertDontSee('8.000');
    }

    public function test_aggregation_by_item_works(): void
    {
        $admin = $this->adminUser();
        $item = $this->item('Flour', 'FLOUR-001');
        $this->approvedIssue($item, '2026-04-05', 2);
        $this->approvedIssue($item, '2026-04-06', 3);

        $this->actingAs($admin)
            ->get('/admin/kitchen/reports/issues?from_date=2026-04-01&to_date=2026-04-30')
            ->assertOk()
            ->assertSee('5.000')
            ->assertSee('2');
    }

    public function test_avg_rate_calculation_works_for_weighted_inbound_history(): void
    {
        $admin = $this->adminUser();
        $item = $this->item('Sugar', 'SUGAR-001');
        $this->postInbound($item, '2026-04-01', 10, 100);
        $this->postInbound($item, '2026-04-10', 30, 200);
        $this->approvedIssue($item, '2026-04-15', 8);

        $this->actingAs($admin)
            ->get('/admin/kitchen/reports/issues?from_date=2026-04-01&to_date=2026-04-30')
            ->assertOk()
            ->assertSee('175.00')
            ->assertSee('1,400.00');
    }

    public function test_missing_rate_history_returns_zero_safely(): void
    {
        $admin = $this->adminUser();
        $item = $this->item('Salt', 'SALT-001');
        $this->approvedIssue($item, '2026-04-05', 6);

        $this->actingAs($admin)
            ->get('/admin/kitchen/reports/issues?from_date=2026-04-01&to_date=2026-04-30')
            ->assertOk()
            ->assertSee('0.00');
    }

    public function test_csv_export_works(): void
    {
        $admin = $this->adminUser();
        $item = $this->item('Tea', 'TEA-001');
        $this->postInbound($item, '2026-04-01', 5, 300);
        $this->approvedIssue($item, '2026-04-05', 2);

        $this->actingAs($admin)
            ->get('/admin/kitchen/reports/issues/export?from_date=2026-04-01&to_date=2026-04-30')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_grand_total_matches_row_sums(): void
    {
        $admin = $this->adminUser();
        $rice = $this->item('Rice', 'RICE-001');
        $oil = $this->item('Oil', 'OIL-001', 'ltr');
        $this->postInbound($rice, '2026-04-01', 10, 100);
        $this->postInbound($oil, '2026-04-01', 10, 50);
        $this->approvedIssue($rice, '2026-04-05', 2);
        $this->approvedIssue($oil, '2026-04-05', 4);

        $response = $this->actingAs($admin)
            ->get('/admin/kitchen/reports/issues?from_date=2026-04-01&to_date=2026-04-30');

        $response->assertOk();
        $response->assertSee('400.00');
    }
}
