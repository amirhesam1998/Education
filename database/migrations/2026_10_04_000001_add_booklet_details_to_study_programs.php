<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Columns the 1405 booklets print for every program and the catalogue had no place for. */
    private const COLUMNS = ['female_capacity', 'male_capacity', 'admission_period', 'admission_scope', 'service_location', 'section_note'];

    public function up(): void
    {
        Schema::table('study_programs', function (Blueprint $table): void {
            if (! Schema::hasColumn('study_programs', 'female_capacity')) {
                $table->unsignedInteger('female_capacity')->nullable()->after('second_semester_capacity');
            }

            if (! Schema::hasColumn('study_programs', 'male_capacity')) {
                $table->unsignedInteger('male_capacity')->nullable()->after('female_capacity');
            }

            if (! Schema::hasColumn('study_programs', 'admission_period')) {
                $table->string('admission_period')->nullable()->after('male_capacity');
            }

            if (! Schema::hasColumn('study_programs', 'admission_scope')) {
                $table->text('admission_scope')->nullable()->after('admission_period');
            }

            if (! Schema::hasColumn('study_programs', 'service_location')) {
                $table->string('service_location')->nullable()->after('admission_scope');
            }

            if (! Schema::hasColumn('study_programs', 'section_note')) {
                $table->text('section_note')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        $columns = array_values(array_filter(self::COLUMNS, fn (string $column) => Schema::hasColumn('study_programs', $column)));

        if ($columns !== []) {
            Schema::table('study_programs', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
