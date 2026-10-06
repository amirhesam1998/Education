<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Islamic Azad University (دانشگاه آزاد اسلامی) programmes and the student selections made from
 * them. They are kept apart from the state catalogue (study_programs) and its field-selection
 * plans: the Azad booklets list programmes by unit code + field code, not by one programme code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('azad_programs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->string('booklet', 40);
            $table->string('admission', 10);
            $table->string('level', 40)->nullable();
            $table->string('province', 60);
            $table->string('city', 80);
            $table->string('unit_code', 10);
            $table->string('unit_name');
            $table->boolean('self_funded')->default(false);
            $table->string('field_code', 10);
            $table->string('field_name');
            $table->boolean('part_time')->default(false);
            $table->string('gender', 20);
            $table->string('exam_group', 20)->nullable();
            $table->string('education_group', 20)->nullable();
            $table->unsignedSmallInteger('capacity_first')->nullable();
            $table->unsignedSmallInteger('capacity_second')->nullable();
            $table->unsignedSmallInteger('booklet_page')->nullable();
            $table->text('search_text');
            $table->string('source_type', 20)->default('booklet');
            $table->string('source_hash', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['year', 'booklet', 'unit_code', 'field_code', 'part_time'], 'azad_programs_identity_unique');
            $table->index(['unit_code', 'field_code'], 'azad_programs_codes_index');
            $table->index(['province', 'city'], 'azad_programs_place_index');
            $table->index(['year', 'booklet'], 'azad_programs_booklet_index');
        });

        Schema::create('azad_program_imports', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->string('programs_sha256', 64);
            $table->string('status', 20);
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('inserted_rows')->default(0);
            $table->unsignedInteger('updated_rows')->default(0);
            $table->unsignedInteger('unchanged_rows')->default(0);
            $table->unsignedInteger('kept_manual_rows')->default(0);
            $table->unsignedInteger('removed_rows')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['year', 'status']);
        });

        Schema::create('azad_selection_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->string('status')->default('draft')->index();
            $table->boolean('is_public_visible')->default(false);
            $table->timestamp('student_visible_at')->nullable();
            $table->timestamp('student_hidden_at')->nullable();
            $table->foreignId('visibility_changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['reservation_id', 'version'], 'azad_plans_reservation_version_unique');
            $table->index(['reservation_id', 'status'], 'azad_plans_reservation_status_index');
        });

        Schema::create('azad_selection_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('azad_selection_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('azad_program_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('priority_order');
            // What the student chose, as it was in the catalogue when it was added.
            $table->string('booklet', 40);
            $table->string('admission', 10);
            $table->string('level', 40)->nullable();
            $table->string('unit_code', 10);
            $table->string('unit_name');
            $table->string('field_code', 10);
            $table->string('field_name');
            $table->boolean('part_time')->default(false);
            $table->string('province', 60);
            $table->string('city', 80);
            $table->string('gender', 20);
            $table->string('exam_group', 20)->nullable();
            $table->unsignedSmallInteger('capacity_first')->nullable();
            $table->unsignedSmallInteger('capacity_second')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['azad_selection_plan_id', 'priority_order'], 'azad_items_plan_priority_unique');
            $table->index(['azad_selection_plan_id', 'azad_program_id'], 'azad_items_plan_program_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('azad_selection_items');
        Schema::dropIfExists('azad_selection_plans');
        Schema::dropIfExists('azad_program_imports');
        Schema::dropIfExists('azad_programs');
    }
};
