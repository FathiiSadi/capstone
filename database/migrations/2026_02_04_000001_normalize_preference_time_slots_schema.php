<?php

use App\Support\PreferenceTimeSlotFormatter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('preference_time_slots', function (Blueprint $table) {
            $table->json('days')->nullable()->change();
            $table->time('start_time')->nullable()->change();

            if (!Schema::hasColumn('preference_time_slots', 'end_time')) {
                $table->time('end_time')->nullable()->after('start_time');
            }
        });

        DB::table('preference_time_slots')
            ->orderBy('id')
            ->chunkById(200, function ($slots) {
                foreach ($slots as $slot) {
                    $days = PreferenceTimeSlotFormatter::normalizeDaysValue($slot->days);
                    $time = PreferenceTimeSlotFormatter::normalizeTimeValue($slot->start_time);

                    DB::table('preference_time_slots')
                        ->where('id', $slot->id)
                        ->update([
                            'days' => $days ? json_encode($days) : null,
                            'start_time' => $time['start'],
                            'end_time' => $time['end'],
                        ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('preference_time_slots')
            ->orderBy('id')
            ->chunkById(200, function ($slots) {
                foreach ($slots as $slot) {
                    $daysValue = null;
                    if (!empty($slot->days)) {
                        $decoded = is_string($slot->days) ? json_decode($slot->days, true) : $slot->days;
                        if (is_array($decoded) && !empty($decoded)) {
                            $daysValue = implode('/', $decoded);
                        }
                    }

                    $startValue = null;
                    if (!empty($slot->start_time)) {
                        $startValue = json_encode([$slot->start_time]);
                    }

                    DB::table('preference_time_slots')
                        ->where('id', $slot->id)
                        ->update([
                            'days' => $daysValue,
                            'start_time' => $startValue,
                        ]);
                }
            });

        Schema::table('preference_time_slots', function (Blueprint $table) {
            $table->string('days', 1000)->nullable()->change();
            $table->json('start_time')->nullable()->change();
            if (Schema::hasColumn('preference_time_slots', 'end_time')) {
                $table->dropColumn('end_time');
            }
        });
    }
};
