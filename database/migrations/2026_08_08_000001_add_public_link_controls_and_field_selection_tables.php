<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('reservations', 'public_link_disabled_at')) {
            Schema::table('reservations', function (Blueprint $table): void {
                $table->timestamp('public_link_disabled_at')->nullable()->after('public_token_used_at');
            });
        }

        if (! Schema::hasColumn('reservations', 'public_link_disabled_by')) {
            Schema::table('reservations', function (Blueprint $table): void {
                $table->foreignId('public_link_disabled_by')->nullable()->after('public_link_disabled_at')->constrained('users')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('reservations', 'public_link_disabled_reason')) {
            Schema::table('reservations', function (Blueprint $table): void {
                $table->text('public_link_disabled_reason')->nullable()->after('public_link_disabled_by');
            });
        }

        if (! Schema::hasTable('field_selection_plans')) {
            Schema::create('field_selection_plans', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
                $table->foreignId('student_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('version');
                $table->string('status')->default('draft')->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('published_at')->nullable();
                $table->timestamps();

                $table->unique(['reservation_id', 'version'], 'fs_plans_reservation_version_unique');
                $table->index(['reservation_id', 'status'], 'fs_plans_reservation_status_index');
            });
        }

        if (! Schema::hasTable('field_selection_items')) {
            Schema::create('field_selection_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('field_selection_plan_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('priority_order');
                $table->string('field_code', 100);
                $table->string('field_name');
                $table->string('city');
                $table->timestamps();

                $table->unique(['field_selection_plan_id', 'priority_order'], 'fs_items_plan_priority_unique');
            });
        } elseif (! Schema::hasIndex('field_selection_items', 'fs_items_plan_priority_unique')) {
            Schema::table('field_selection_items', function (Blueprint $table): void {
                $table->unique(['field_selection_plan_id', 'priority_order'], 'fs_items_plan_priority_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('field_selection_items');
        Schema::dropIfExists('field_selection_plans');

        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('public_link_disabled_by');
            $table->dropColumn(['public_link_disabled_at', 'public_link_disabled_reason']);
        });
    }
};
