<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('slot_id')->constrained('reservation_slots')->cascadeOnDelete();
            $table->foreignId('advisor_id')->constrained()->cascadeOnDelete();
            $table->date('follow_up_date');
            $table->time('reserved_start_time');
            $table->time('reserved_end_time');
            $table->string('status')->default('scheduled')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['advisor_id', 'follow_up_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_follow_ups');
    }
};
