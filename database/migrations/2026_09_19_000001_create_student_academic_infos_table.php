<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_academic_infos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reservation_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('rank', 50)->nullable();
            $table->string('national_rank', 50)->nullable();
            $table->string('total_score', 50)->nullable();
            $table->string('final_score', 50)->nullable();
            $table->string('accepted_national_field')->nullable();
            $table->string('accepted_azad_other_field')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Intentionally non-destructive: academic records must be retained.
    }
};
