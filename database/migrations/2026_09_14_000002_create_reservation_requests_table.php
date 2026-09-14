<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('full_name');
            $table->string('phone_1', 30)->index();
            $table->string('phone_2', 30)->nullable();
            $table->string('major')->nullable();
            $table->json('exam_type')->nullable();
            $table->string('score')->nullable();
            $table->string('region_quota', 30)->nullable();
            $table->text('description')->nullable();
            $table->date('preferred_date')->nullable();
            $table->time('preferred_time')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('converted_reservation_id')->nullable()->constrained('reservations')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        // Intentionally non-destructive. Removing submitted public requests is unsafe.
    }
};
