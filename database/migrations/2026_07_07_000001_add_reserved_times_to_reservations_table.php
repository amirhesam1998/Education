<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->time('reserved_start_time')->nullable()->after('advisor_id');
            $table->time('reserved_end_time')->nullable()->after('reserved_start_time');
            $table->index(['slot_id', 'reserved_start_time', 'reserved_end_time'], 'reservations_slot_reserved_time_index');
        });

        DB::table('reservations')
            ->whereNotNull('slot_id')
            ->whereNull('reserved_start_time')
            ->orderBy('id')
            ->chunkById(100, function ($reservations): void {
                foreach ($reservations as $reservation) {
                    $slot = DB::table('reservation_slots')->where('id', $reservation->slot_id)->first(['start_time', 'end_time']);

                    if (! $slot) {
                        continue;
                    }

                    DB::table('reservations')
                        ->where('id', $reservation->id)
                        ->update([
                            'reserved_start_time' => $slot->start_time,
                            'reserved_end_time' => $slot->end_time,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('reservations_slot_reserved_time_index');
            $table->dropColumn(['reserved_start_time', 'reserved_end_time']);
        });
    }
};
