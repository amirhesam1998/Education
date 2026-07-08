<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('slot_id')->nullable()->constrained('reservation_slots')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('draft')->index();
            $table->boolean('prepayment_required')->default(false)->index();
            $table->unsignedBigInteger('prepayment_amount')->nullable();
            $table->timestamp('payment_deadline_at')->nullable()->index();
            $table->string('public_token', 128)->nullable()->unique();
            $table->timestamp('public_token_expires_at')->nullable()->index();
            $table->timestamp('public_token_used_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_note')->nullable();
            $table->text('student_note')->nullable();
            $table->timestamps();

            $table->index(['slot_id', 'status']);
            $table->index(['advisor_id', 'status']);
            $table->index(['created_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
