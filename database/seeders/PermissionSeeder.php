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
            'auth.login', 'user.manage', 'member.manage', 'attendance.manage', 'rate.manage',
            'billing.generate', 'billing.correct', 'payment.create', 'payment.approve',
            'payments.view_own', 'payments.initiate_own', 'payments.view_admin', 'payments.verify_admin',
            'payments.reconcile_admin', 'payments.manual_record_admin', 'payments.refund_admin', 'payments.override_status_admin',
            'ledger.adjust', 'ledger.recompute', 'month.close', 'month.reopen', 'month.reset_hard',
            'inventory.manage', 'procurement.manage', 'kitchen.manage', 'guest.manage', 'accounting.manage',
            'report.view', 'report.export', 'settings.manage', 'audit.view',
            'member.self_register', 'otp.request', 'otp.verify',
            'superadmin.member_account_create', 'superadmin.member_account_reset',
            'superadmin.member_account_activate', 'superadmin.member_account_deactivate',
        ];

        foreach ($codes as $code) {
            Permission::query()->updateOrCreate(['code' => $code], ['name' => strtoupper(str_replace('.', ' ', $code))]);
        }

        $all = Permission::query()->pluck('id')->all();
        $superAdmin = Role::query()->where('code', 'SUPER_ADMIN')->first();
        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching($all);
        }

        $admin = Role::query()->where('code', 'ADMIN')->first();
        if ($admin) {
            $adminPerms = Permission::query()->whereNotIn('code', ['month.reset_hard'])->pluck('id')->all();
            $admin->permissions()->syncWithoutDetaching($adminPerms);
        }

        $dataEntry = Role::query()->where('code', 'DATA_ENTRY')->first();
        if ($dataEntry) {
            $dataEntryPerms = Permission::query()->whereIn('code', [
                'member.manage', 'attendance.manage', 'billing.generate', 'billing.correct',
                'payment.create', 'payments.view_admin', 'payments.verify_admin', 'payments.manual_record_admin',
                'payments.reconcile_admin', 'ledger.adjust', 'ledger.recompute', 'inventory.manage', 'procurement.manage',
                'kitchen.manage', 'guest.manage', 'accounting.manage', 'report.view', 'report.export',
            ])->pluck('id')->all();
            $dataEntry->permissions()->syncWithoutDetaching($dataEntryPerms);
        }

        $auditor = Role::query()->where('code', 'AUDITOR')->first();
        if ($auditor) {
            $auditorPerms = Permission::query()->whereIn('code', ['report.view', 'audit.view', 'payments.view_admin'])->pluck('id')->all();
            $auditor->permissions()->syncWithoutDetaching($auditorPerms);
        }

        $member = Role::query()->where('code', 'MEMBER')->first();
        if ($member) {
            $memberPerms = Permission::query()->whereIn('code', ['payments.view_own', 'payments.initiate_own'])->pluck('id')->all();
            $member->permissions()->syncWithoutDetaching($memberPerms);
        }
    }
}
