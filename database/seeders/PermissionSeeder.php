<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $codes = [
            'auth.login', 'user.manage', 'users.manage', 'users.toggle', 'member.manage', 'attendance.manage', 'rate.manage', 'rates.manage',
            'billing.generate', 'billing.correct', 'payment.create', 'payment.approve',
            'payments.view_own', 'payments.initiate_own', 'payments.view_admin', 'payments.verify_admin',
            'payments.reconcile_admin', 'payments.manual_record_admin', 'payments.refund_admin', 'payments.override_status_admin',
            'ledger.adjust', 'ledger.recompute', 'month.close', 'month.reopen', 'month.reset_hard',
            'inventory.manage', 'procurement.manage', 'kitchen.manage', 'guest.manage', 'accounting.manage',
            'report.view', 'report.export', 'settings.manage', 'settings.dangerous', 'audit.view',
            'complaint.submit_own', 'complaint.view_own', 'complaint.manage', 'complaint.close', 'complaint.view_all', 'complaint.export',
            'menu.view', 'menu.manage', 'menu.approve', 'menu.export',
            'member.self_register', 'otp.request', 'otp.verify',
            'superadmin.member_account_create', 'superadmin.member_account_reset',
            'superadmin.member_account_activate', 'superadmin.member_account_deactivate',
            'fleet.dashboard.view',
            'fleet.vehicles.view', 'fleet.vehicles.manage',
            'fleet.drivers.view', 'fleet.drivers.manage',
            'fleet.fuel.view', 'fleet.fuel.manage',
            'fleet.maintenance.view', 'fleet.maintenance.manage', 'fleet.maintenance.approve',
            'fleet.documents.view', 'fleet.documents.manage',
            'fleet.trips.view', 'fleet.trips.manage',
            'fleet.tyres_batteries.view', 'fleet.tyres_batteries.manage',
            'fleet.incidents.view', 'fleet.incidents.manage',
            'fleet.challans.view', 'fleet.challans.manage',
            'fleet.reports.view', 'fleet.reports.export',
            'fleet.settings.view', 'fleet.settings.manage',
        ];

        foreach ($codes as $code) {
            Permission::query()->updateOrCreate(['code' => $code], ['name' => strtoupper(str_replace('.', ' ', $code))]);
        }

        $all = Permission::query()->pluck('id')->all();
        $superAdmin = Role::query()->where('code', 'SUPER_ADMIN')->first();
        if ($superAdmin) {
            $superAdmin->permissions()->sync($all);
        }

        $this->syncRolePermissions('ADMIN', [
            'users.manage', 'users.toggle',
            'member.manage', 'attendance.manage', 'billing.generate', 'billing.correct', 'payment.create',
            'payments.view_admin', 'payments.verify_admin', 'payments.manual_record_admin', 'payments.reconcile_admin',
            'ledger.adjust', 'ledger.recompute', 'inventory.manage', 'procurement.manage', 'kitchen.manage',
            'guest.manage', 'accounting.manage', 'report.view', 'report.export', 'audit.view',
            'rates.manage', 'complaint.manage', 'complaint.close', 'complaint.view_all', 'complaint.export',
            'menu.view', 'menu.manage', 'menu.approve', 'menu.export', 'month.close', 'month.reopen',
            'fleet.dashboard.view',
            'fleet.vehicles.view', 'fleet.vehicles.manage',
            'fleet.drivers.view', 'fleet.drivers.manage',
            'fleet.fuel.view', 'fleet.fuel.manage',
            'fleet.maintenance.view', 'fleet.maintenance.manage', 'fleet.maintenance.approve',
            'fleet.documents.view', 'fleet.documents.manage',
            'fleet.trips.view', 'fleet.trips.manage',
            'fleet.tyres_batteries.view', 'fleet.tyres_batteries.manage',
            'fleet.incidents.view', 'fleet.incidents.manage',
            'fleet.challans.view', 'fleet.challans.manage',
            'fleet.reports.view', 'fleet.reports.export',
            'fleet.settings.view', 'fleet.settings.manage',
        ]);

        $this->syncRolePermissions('DATA_ENTRY', [
            'member.manage', 'attendance.manage', 'payment.create', 'inventory.manage', 'procurement.manage',
            'kitchen.manage', 'guest.manage', 'complaint.manage', 'menu.view',
            'fleet.dashboard.view', 'fleet.vehicles.view', 'fleet.drivers.view', 'fleet.fuel.view',
            'fleet.maintenance.view', 'fleet.documents.view', 'fleet.trips.view',
            'fleet.tyres_batteries.view', 'fleet.incidents.view', 'fleet.challans.view', 'fleet.reports.view',
        ]);

        $this->syncRolePermissions('AUDITOR', [
            'report.view', 'report.export', 'audit.view', 'payments.view_admin',
            'complaint.view_all', 'complaint.export', 'menu.view', 'menu.export',
            'fleet.dashboard.view', 'fleet.vehicles.view', 'fleet.drivers.view', 'fleet.fuel.view',
            'fleet.maintenance.view', 'fleet.documents.view', 'fleet.trips.view',
            'fleet.tyres_batteries.view', 'fleet.incidents.view', 'fleet.challans.view',
            'fleet.reports.view', 'fleet.reports.export',
        ]);

        $this->syncRolePermissions('MEMBER', [
            'payments.view_own', 'payments.initiate_own', 'complaint.submit_own', 'complaint.view_own', 'menu.view',
        ]);
    }

    private function syncRolePermissions(string $roleCode, array $permissionCodes): void
    {
        $role = Role::query()->where('code', $roleCode)->first();
        if (! $role) {
            return;
        }

        $permissionIds = Permission::query()->whereIn('code', $permissionCodes)->pluck('id')->all();
        $role->permissions()->sync($permissionIds);
    }
}
