<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixSchedulerDataSeeder extends Seeder
{
    /**
     * Fix scheduler data issues:
     * 1. Assign instructors to departments
     * 2. Fix time slot format and add start times
     */
    public function run(): void
    {
        echo "🔧 Fixing Scheduler Data...\n\n";

        // 1. Assign all instructors to department 1 (adjust as needed)
        $instructors = DB::table('instructors')->pluck('id');
        $assigned = 0;

        foreach ($instructors as $instructorId) {
            $exists = DB::table('department_instructors')
                ->where('instructor_id', $instructorId)
                ->exists();

            if (!$exists) {
                DB::table('department_instructors')->insert([
                    'instructor_id' => $instructorId,
                    'department_id' => 1, // Change this if needed
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $assigned++;
            }
        }

        echo "✓ Assigned {$assigned} instructors to departments\n";

        // 2. Fix time slots
        $dayMap = [
            'Sat' => 'Saturday',
            'Sun' => 'Sunday',
            'Mon' => 'Monday',
            'Tue' => 'Tuesday',
            'Wed' => 'Wednesday',
            'Thu' => 'Thursday',
            'Fri' => 'Friday',
        ];

        $timeMap = [
            'Morning' => '08:30:00',
            'Noon' => '12:00:00',
            'Afternoon' => '14:00:00',
        ];

        $slots = DB::table('preference_time_slots')->get();
        $updated = 0;

        foreach ($slots as $slot) {
            // Skip if already in correct format
            if ($slot->start_time !== null && str_starts_with($slot->days, '[')) {
                continue;
            }

            $parts = explode(' - ', $slot->days);
            $daysPart = $parts[0] ?? '';
            $timePart = trim($parts[1] ?? 'Morning');

            // Handle multiple time periods (take first one)
            if (str_contains($timePart, ',')) {
                $timePart = explode(',', $timePart)[0];
                $timePart = trim($timePart);
            }

            // Extract days
            $days = [];
            foreach ($dayMap as $abbr => $fullDay) {
                if (str_contains($daysPart, $abbr)) {
                    $days[] = $fullDay;
                }
            }

            // Default to Sunday if no days found
            if (empty($days)) {
                $days = ['Sunday'];
            }

            // Get start time
            $startTime = $timeMap[$timePart] ?? '08:30:00';

            // Update the record
            DB::table('preference_time_slots')
                ->where('id', $slot->id)
                ->update([
                    'days' => json_encode($days),
                    'start_time' => $startTime,
                ]);

            $updated++;
        }

        echo "✓ Fixed {$updated} time slots\n";
        echo "\n✅ All data fixed! You can now run the scheduler.\n";
        echo "\nRun: php artisan schedule:run-semester 1\n";
    }
}
