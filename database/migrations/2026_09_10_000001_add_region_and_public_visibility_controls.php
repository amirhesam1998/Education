<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students') && ! Schema::hasColumn('students', 'region')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->string('region')->nullable()->after('major')->index();
            });
        }

        if (Schema::hasTable('field_selection_plans') && ! Schema::hasColumn('field_selection_plans', 'is_public_visible')) {
            Schema::table('field_selection_plans', function (Blueprint $table): void {
                $table->boolean('is_public_visible')->default(false)->after('status');
                $table->timestamp('student_visible_at')->nullable()->after('is_public_visible');
                $table->timestamp('student_hidden_at')->nullable()->after('student_visible_at');
                $table->foreignId('visibility_changed_by')->nullable()->after('student_hidden_at')->constrained('users')->nullOnDelete();
                $table->text('visibility_note')->nullable()->after('visibility_changed_by');
                $table->index(['reservation_id', 'is_public_visible'], 'fs_plans_public_visible_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('field_selection_plans') && Schema::hasColumn('field_selection_plans', 'is_public_visible')) {
            Schema::table('field_selection_plans', function (Blueprint $table): void {
                if ($this->indexExists('field_selection_plans', 'fs_plans_public_visible_index')) {
                    $table->dropIndex('fs_plans_public_visible_index');
                }

                $table->dropConstrainedForeignId('visibility_changed_by');
                $table->dropColumn(['is_public_visible', 'student_visible_at', 'student_hidden_at', 'visibility_note']);
            });
        }

        if (Schema::hasTable('students') && Schema::hasColumn('students', 'region')) {
            Schema::table('students', function (Blueprint $table): void {
                if ($this->indexExists('students', 'students_region_index')) {
                    $table->dropIndex('students_region_index');
                }

                $table->dropColumn('region');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        try {
            return Schema::hasIndex($table, $index);
        } catch (Throwable) {
            return collect(Schema::getIndexes($table))->contains(fn (array $schemaIndex): bool => ($schemaIndex['name'] ?? null) === $index);
        }
    }
};
