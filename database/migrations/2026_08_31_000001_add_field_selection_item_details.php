<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('field_selection_items')) {
            return;
        }

        Schema::table('field_selection_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('field_selection_items', 'field_description')) {
                $table->text('field_description')->nullable()->after('field_name');
            }

            if (! Schema::hasColumn('field_selection_items', 'university_name')) {
                $table->string('university_name')->nullable()->after('city');
            }
        });

        if (Schema::hasColumn('field_selection_items', 'university_description')) {
            DB::table('field_selection_items')
                ->whereNull('field_description')
                ->whereNotNull('university_description')
                ->update(['field_description' => DB::raw('university_description')]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('field_selection_items')) {
            return;
        }

        Schema::table('field_selection_items', function (Blueprint $table): void {
            if (Schema::hasColumn('field_selection_items', 'university_name')) {
                $table->dropColumn('university_name');
            }

            if (Schema::hasColumn('field_selection_items', 'field_description')) {
                $table->dropColumn('field_description');
            }
        });
    }
};
