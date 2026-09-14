<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservation_requests', function (Blueprint $table): void {
            $table->string('region')->nullable()->after('score');
            $table->string('special_quota')->nullable()->after('region');
            $table->string('special_quota_other')->nullable()->after('special_quota');
        });
    }

    public function down(): void
    {
        // Intentionally non-destructive: submitted form data must be retained.
    }
};
