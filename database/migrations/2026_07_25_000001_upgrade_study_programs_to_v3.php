<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('study_program_imports') && ! Schema::hasColumn('study_program_imports', 'source_file')) {
            Schema::table('study_program_imports', function (Blueprint $table): void {
                $table->string('source_file')->nullable()->after('source_filename')->index();
            });
        }

        $this->indexIfMissing('study_programs', 'sp_exam_year_idx', fn () => Schema::table('study_programs', fn (Blueprint $table) => $table->index('exam_year_id', 'sp_exam_year_idx')));
        $this->indexIfMissing('study_programs', 'sp_exam_group_idx', fn () => Schema::table('study_programs', fn (Blueprint $table) => $table->index('exam_group_id', 'sp_exam_group_idx')));
        $this->indexIfMissing('study_programs', 'sp_province_idx', fn () => Schema::table('study_programs', fn (Blueprint $table) => $table->index('province_id', 'sp_province_idx')));
        $this->indexIfMissing('study_programs', 'sp_city_idx', fn () => Schema::table('study_programs', fn (Blueprint $table) => $table->index('city_id', 'sp_city_idx')));
        $this->indexIfMissing('study_programs', 'sp_institution_idx', fn () => Schema::table('study_programs', fn (Blueprint $table) => $table->index('institution_id', 'sp_institution_idx')));
        $this->indexIfMissing('study_programs', 'sp_academic_field_idx', fn () => Schema::table('study_programs', fn (Blueprint $table) => $table->index('academic_field_id', 'sp_academic_field_idx')));
        $this->indexIfMissing('study_programs', 'sp_course_type_idx', fn () => Schema::table('study_programs', fn (Blueprint $table) => $table->index('course_type_id', 'sp_course_type_idx')));
        $this->indexIfMissing('study_programs', 'sp_admission_type_idx', fn () => Schema::table('study_programs', fn (Blueprint $table) => $table->index('admission_type_id', 'sp_admission_type_idx')));
        $this->indexIfMissing('study_programs', 'sp_year_group_province_idx', fn () => Schema::table('study_programs', fn (Blueprint $table) => $table->index(['exam_year_id', 'exam_group_id', 'province_id'], 'sp_year_group_province_idx')));
        $this->indexIfMissing('study_programs', 'sp_field_course_idx', fn () => Schema::table('study_programs', fn (Blueprint $table) => $table->index(['academic_field_id', 'course_type_id'], 'sp_field_course_idx')));
    }

    public function down(): void
    {
    }

    private function indexIfMissing(string $table, string $index, callable $callback): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $indexes = collect(Schema::getIndexes($table))->pluck('name');
        if (! $indexes->contains($index)) {
            $callback();
        }
    }
};
