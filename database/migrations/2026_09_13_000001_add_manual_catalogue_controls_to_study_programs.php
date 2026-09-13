<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_programs', function (Blueprint $table): void {
            if (! Schema::hasColumn('study_programs', 'is_active')) {
                $table->boolean('is_active')->default(true)->index()->after('validation_status');
            }

            if (! Schema::hasColumn('study_programs', 'source_type')) {
                $table->string('source_type')->default('imported')->index()->after('source_file');
            }
        });

        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        $now = now();
        $permissions = [
            'view_study_programs',
            'create_study_programs',
            'update_study_programs',
            'delete_study_programs',
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                ['created_at' => $now, 'updated_at' => $now],
            );
        }

        if (! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')->whereIn('name', $permissions)->pluck('id');
        $roleIds = DB::table('roles')->whereIn('name', ['Super Admin', 'Admin / Branch Manager'])->pluck('id');
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
    }
};
