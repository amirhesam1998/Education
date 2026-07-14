<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_cards', function (Blueprint $table) {
            $table->id();
            $table->string('holder_name');
            $table->string('card_number', 32)->index();
            $table->string('bank_name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('payment_card_id')
                ->nullable()
                ->after('prepayment_amount')
                ->constrained('payment_cards')
                ->nullOnDelete();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['exam_type']);
            $table->text('exam_type')->nullable()->change();
        });

        DB::table('students')
            ->whereNotNull('exam_type')
            ->orderBy('id')
            ->chunkById(100, function ($students): void {
                foreach ($students as $student) {
                    $value = trim((string) $student->exam_type);

                    if ($value === '' || str_starts_with($value, '[')) {
                        continue;
                    }

                    DB::table('students')
                        ->where('id', $student->id)
                        ->update(['exam_type' => json_encode([$value], JSON_UNESCAPED_UNICODE)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_card_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->string('exam_type')->nullable()->index()->change();
        });

        Schema::dropIfExists('payment_cards');
    }
};
