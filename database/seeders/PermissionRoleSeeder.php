<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionRoleSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    public const PERMISSIONS = [
        'view_dashboard',
        'view_slots',
        'create_slots',
        'update_slots',
        'delete_slots',
        'view_reservations',
        'create_reservations',
        'update_reservations',
        'manage_field_selection',
        'view_field_selection',
        'view_reservation_sensitive_info',
        'view_student_personal_data',
        'view_reservation_payment_info',
        'view_prepayment_receipts',
        'view_student_public_link',
        'view_reservation_documents',
        'view_consultant_stats',
        'cancel_reservations',
        'change_reservation_slot',
        'confirm_reservations',
        'view_payments',
        'approve_payments',
        'reject_payments',
        'view_reports',
        'manage_users',
        'manage_roles',
        'manage_settings',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $all = self::PERMISSIONS;

        $roles = [
            'Super Admin' => $all,
            'Admin / Branch Manager' => $all,
            'Operator' => [
                'view_dashboard',
                'view_slots',
                'view_reservations',
                'create_reservations',
                'update_reservations',
                'view_reservation_sensitive_info',
                'view_student_personal_data',
                'view_reservation_payment_info',
                'view_prepayment_receipts',
                'view_student_public_link',
                'view_reservation_documents',
                'change_reservation_slot',
                'view_field_selection',
            ],
            'Accountant' => [
                'view_dashboard',
                'view_reservations',
                'view_payments',
                'view_reservation_payment_info',
                'view_prepayment_receipts',
                'approve_payments',
                'reject_payments',
            ],
            'Consultant' => [
                'view_dashboard',
                'view_slots',
                'view_reservations',
                'confirm_reservations',
                'view_field_selection',
                'manage_field_selection',
            ],
        ];

        foreach ($roles as $name => $permissions) {
            $role = Role::query()->firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($permissions);
        }
    }
}
