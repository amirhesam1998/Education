<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('field_selection_plans')) {
            return;
        }

        Schema::table('field_selection_plans', function (Blueprint $table): void {
            if (! Schema::hasColumn('field_selection_plans', 'exam_type_key')) {
                $table->string('exam_type_key')->nullable()->after('student_id');
                $table->index(['reservation_id', 'exam_type_key'], 'fs_plans_reservation_exam_type_index');
            }

            if (! Schema::hasColumn('field_selection_plans', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        // Intentionally non-destructive: existing field-selection records must be retained.
    }
};
