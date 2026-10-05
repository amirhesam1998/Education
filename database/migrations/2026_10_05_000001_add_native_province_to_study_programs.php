<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The province whose natives a program is reserved for or gives priority to (service-commitment,
     * native-quota and Farhangian programs). It can differ from the province the program is studied in,
     * and a province filter matches either one.
     */
    public function up(): void
    {
        if (Schema::hasColumn('study_programs', 'native_province_id')) {
            return;
        }

        Schema::table('study_programs', function (Blueprint $table): void {
            $table->foreignId('native_province_id')->nullable()->after('province_id')->constrained('provinces')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('study_programs', 'native_province_id')) {
            return;
        }

        Schema::table('study_programs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('native_province_id');
        });
    }
};
