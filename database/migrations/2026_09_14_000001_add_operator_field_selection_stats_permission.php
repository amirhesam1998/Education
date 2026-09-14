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

        $now = now();
        DB::table('permissions')->updateOrInsert(
            ['name' => 'view_operator_field_selection_stats', 'guard_name' => 'web'],
            ['created_at' => $now, 'updated_at' => $now],
        );

        if (Schema::hasTable('role_has_permissions')) {
            $permissionId = DB::table('permissions')->where('name', 'view_operator_field_selection_stats')->value('id');
            $roleIds = DB::table('roles')->whereIn('name', ['Super Admin', 'Admin / Branch Manager', 'Creator'])->pluck('id');

            foreach ($roleIds as $roleId) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Intentionally non-destructive: permissions may have been assigned manually.
    }
};
