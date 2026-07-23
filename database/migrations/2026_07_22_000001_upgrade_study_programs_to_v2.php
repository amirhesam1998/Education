<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('institution_campuses')) {
            Schema::create('institution_campuses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
                $table->foreignId('province_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('normalized_name');
                $table->timestamps();
                $table->unique(['institution_id', 'normalized_name']);
                $table->index(['province_id', 'city_id']);
            });
        }

        Schema::table('study_programs', function (Blueprint $table) {
            $this->addIfMissing($table, 'institution_campus_id', fn () => $table->foreignId('institution_campus_id')->nullable()->after('institution_id')->constrained()->nullOnDelete());
            $this->addIfMissing($table, 'accepts_male', fn () => $table->boolean('accepts_male')->nullable()->after('original_admission_type'));
            $this->addIfMissing($table, 'accepts_female', fn () => $table->boolean('accepts_female')->nullable()->after('accepts_male'));
            $this->addIfMissing($table, 'first_semester_capacity', fn () => $table->unsignedInteger('first_semester_capacity')->nullable()->after('accepts_female'));
            $this->addIfMissing($table, 'second_semester_capacity', fn () => $table->unsignedInteger('second_semester_capacity')->nullable()->after('first_semester_capacity'));
            $this->addIfMissing($table, 'booklet_section', fn () => $table->string('booklet_section')->nullable()->after('booklet_page'));
            $this->addIfMissing($table, 'identity_hash', fn () => $table->char('identity_hash', 64)->nullable()->after('source_hash'));
            $this->addIfMissing($table, 'validation_status', fn () => $table->string('validation_status')->default('validated')->after('identity_hash'));
        });

        $this->dropIndexIfExists('study_programs', 'study_programs_exam_year_id_exam_group_id_code_unique', fn () => Schema::table('study_programs', fn (Blueprint $table) => $table->dropUnique('study_programs_exam_year_id_exam_group_id_code_unique')));
        $this->indexIfMissing('study_programs', 'study_programs_identity_hash_unique', fn () => Schema::table('study_programs', fn (Blueprint $table) => $table->unique('identity_hash', 'study_programs_identity_hash_unique')));
        $this->indexIfMissing('study_programs', 'study_programs_source_hash_index', fn () => Schema::table('study_programs', fn (Blueprint $table) => $table->index('source_hash', 'study_programs_source_hash_index')));
        $this->indexIfMissing('study_programs', 'sp_year_group_code_idx', fn () => Schema::table('study_programs', fn (Blueprint $table) => $table->index(['exam_year_id', 'exam_group_id', 'code'], 'sp_year_group_code_idx')));
        $this->indexIfMissing('study_programs', 'sp_year_group_city_idx', fn () => Schema::table('study_programs', fn (Blueprint $table) => $table->index(['exam_year_id', 'exam_group_id', 'city_id'], 'sp_year_group_city_idx')));
        $this->indexIfMissing('study_programs', 'sp_validation_status_idx', fn () => Schema::table('study_programs', fn (Blueprint $table) => $table->index('validation_status', 'sp_validation_status_idx')));
        $this->indexIfMissing('study_programs', 'sp_campus_idx', fn () => Schema::table('study_programs', fn (Blueprint $table) => $table->index('institution_campus_id', 'sp_campus_idx')));
        $this->indexIfMissing('study_programs', 'sp_institution_field_idx', fn () => Schema::table('study_programs', fn (Blueprint $table) => $table->index(['institution_id', 'academic_field_id'], 'sp_institution_field_idx')));
        $this->indexIfMissing('study_programs', 'sp_group_course_idx', fn () => Schema::table('study_programs', fn (Blueprint $table) => $table->index(['exam_group_id', 'course_type_id'], 'sp_group_course_idx')));
        $this->indexIfMissing('study_programs', 'sp_year_validation_idx', fn () => Schema::table('study_programs', fn (Blueprint $table) => $table->index(['exam_year_id', 'validation_status'], 'sp_year_validation_idx')));

        if (! Schema::hasTable('study_program_review_records')) {
            Schema::create('study_program_review_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exam_year_id')->constrained()->cascadeOnDelete();
                $table->foreignId('exam_group_id')->constrained()->cascadeOnDelete();
                $table->string('code', 32)->nullable();
                $table->string('source_file');
                $table->unsignedInteger('source_row')->nullable();
                $table->char('source_hash', 64)->nullable();
                $table->char('identity_hash', 64)->nullable()->index();
                $table->text('review_reason')->nullable();
                $table->json('raw_data');
                $table->string('status')->default('pending')->index();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('rejected_at')->nullable();
                $table->text('review_note')->nullable();
                $table->timestamps();
                $table->index(['exam_year_id', 'exam_group_id']);
                $table->index(['source_file', 'source_row']);
                $table->unique('source_hash', 'study_program_review_source_hash_unique');
            });
        }

        Schema::table('study_program_imports', function (Blueprint $table) {
            $this->addIfMissing($table, 'review_rows', fn () => $table->unsignedInteger('review_rows')->default(0)->after('unchanged_rows'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_program_review_records');
        Schema::dropIfExists('institution_campuses');
    }

    private function addIfMissing(Blueprint $table, string $column, callable $callback): void
    {
        if (! Schema::hasColumn($table->getTable(), $column)) {
            $callback();
        }
    }

    private function indexIfMissing(string $table, string $index, callable $callback): void
    {
        $indexes = collect(Schema::getIndexes($table))->pluck('name');

        if (! $indexes->contains($index)) {
            $callback();
        }
    }

    private function dropIndexIfExists(string $table, string $index, callable $callback): void
    {
        $indexes = collect(Schema::getIndexes($table))->pluck('name');

        if ($indexes->contains($index)) {
            $callback();
        }
    }
};
