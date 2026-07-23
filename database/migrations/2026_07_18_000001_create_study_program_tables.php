<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('exam_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('normalized_name')->unique();
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('normalized_name');
            $table->timestamps();
            $table->unique(['province_id', 'normalized_name']);
            $table->index('normalized_name');
        });

        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('normalized_name')->unique();
            $table->foreignId('province_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('academic_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('normalized_name')->unique();
            $table->timestamps();
        });

        Schema::create('course_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('admission_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('study_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_group_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32);
            $table->foreignId('province_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('institution_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_field_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admission_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_course_type')->nullable();
            $table->string('original_admission_type')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('booklet_page')->nullable();
            $table->string('city_detection_method')->nullable();
            $table->string('source_file');
            $table->unsignedInteger('source_row')->nullable();
            $table->string('source_hash', 64);
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->unique(['exam_year_id', 'exam_group_id', 'code']);
            $table->index('code');
            $table->index(['exam_year_id', 'exam_group_id']);
            $table->index(['exam_group_id', 'province_id']);
            $table->index(['province_id', 'city_id']);
            $table->index(['academic_field_id', 'course_type_id']);
            $table->index(['exam_year_id', 'exam_group_id', 'course_type_id'], 'sp_year_group_course_idx');
            $table->index(['exam_year_id', 'exam_group_id', 'province_id'], 'sp_year_group_province_idx');
            $table->index('booklet_page');
        });

        Schema::create('study_program_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_year_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('exam_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_path');
            $table->string('source_filename');
            $table->string('file_hash', 64)->index();
            $table->string('status')->index();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('inserted_rows')->default(0);
            $table->unsignedInteger('updated_rows')->default(0);
            $table->unsignedInteger('unchanged_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['exam_year_id', 'exam_group_id']);
        });

        Schema::create('study_program_import_failures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('study_program_imports')->cascadeOnDelete();
            $table->string('sheet_name')->nullable();
            $table->unsignedInteger('source_row')->nullable();
            $table->string('code', 32)->nullable();
            $table->string('reason');
            $table->json('raw_data')->nullable();
            $table->timestamps();
            $table->index(['import_id', 'source_row']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_program_import_failures');
        Schema::dropIfExists('study_program_imports');
        Schema::dropIfExists('study_programs');
        Schema::dropIfExists('admission_types');
        Schema::dropIfExists('course_types');
        Schema::dropIfExists('academic_fields');
        Schema::dropIfExists('institutions');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('provinces');
        Schema::dropIfExists('exam_groups');
        Schema::dropIfExists('exam_years');
    }
};
