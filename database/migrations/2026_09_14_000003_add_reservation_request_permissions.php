<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        $permissions = [
            'view_reservation_requests', 'approve_reservation_requests',
            'reject_reservation_requests', 'convert_reservation_requests',
        ];
        $now = now();

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                ['created_at' => $now, 'updated_at' => $now],
            );
        }

        if (Schema::hasTable('role_has_permissions')) {
            $allRoleIds = DB::table('roles')->whereIn('name', ['Super Admin', 'Admin / Branch Manager'])->pluck('id');
            $operatorRoleIds = DB::table('roles')->where('name', 'Operator')->pluck('id');

            foreach ($allRoleIds as $roleId) {
                foreach (DB::table('permissions')->whereIn('name', $permissions)->pluck('id') as $permissionId) {
                    DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);
                }
            }

            foreach (DB::table('permissions')->whereIn('name', ['view_reservation_requests', 'convert_reservation_requests'])->pluck('id') as $permissionId) {
                foreach ($operatorRoleIds as $roleId) {
                    DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Intentionally non-destructive: role permissions may have been changed manually.
    }
};
