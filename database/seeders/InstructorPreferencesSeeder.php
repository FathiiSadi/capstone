<?php

namespace Database\Seeders;

use App\InstructorPosition;
use App\Models\Course;
use App\Models\Department;
use App\Models\Instructor;
use App\Models\InstructorPreference;
use App\Models\PreferenceTimeSlot;
use App\Models\Semester;
use App\Models\User;
use App\Support\PreferenceTimeSlotFormatter;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class InstructorPreferencesSeeder extends Seeder
{
    public function run()
    {
        // 0. Cleanup Preferences only (Preserve setup if possible, or clean slate for preferences)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        InstructorPreference::truncate();
        PreferenceTimeSlot::truncate();
        // We don't truncate Users/Courses to avoid breaking existing data, we firstOrCreate them.
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Ensure Department
        $csDept = Department::firstOrCreate(
            ['name' => 'Computer Science'],
            ['code' => 'CS']
        );

        // 2. Ensure Semester (Open)
        $semester = Semester::firstOrCreate(
            ['name' => 'Spring 2026', 'type' => 'Spring'],
            [
                'status' => 'Open',
                'preferences_open_at' => Carbon::now()->subDays(5),
                'preferences_closed_at' => Carbon::now()->addDays(10),
            ]
        );

        // 3. Ensure Courses (Mix of Core and Electives)
        $coursesList = [
            ['code' => 'CS101', 'name' => 'Introduction to Programming', 'hours' => 3],
            ['code' => 'CS102', 'name' => 'Object Oriented Programming', 'hours' => 3],
            ['code' => 'CS201', 'name' => 'Data Structures', 'hours' => 3],
            ['code' => 'CS202', 'name' => 'Algorithms', 'hours' => 3],
            ['code' => 'CS301', 'name' => 'Database Systems', 'hours' => 3],
            ['code' => 'CS302', 'name' => 'Operating Systems', 'hours' => 3],
            ['code' => 'CS401', 'name' => 'Artificial Intelligence', 'hours' => 3],
            ['code' => 'CS402', 'name' => 'Computer Networks', 'hours' => 3],
            ['code' => 'CS403', 'name' => 'Cyber Security', 'hours' => 3],
            ['code' => 'CS404', 'name' => 'Software Engineering', 'hours' => 3],
            ['code' => 'CS405', 'name' => 'Web Development', 'hours' => 3],
            ['code' => 'CS499', 'name' => 'Graduation Project', 'hours' => 3],
        ];

        $allCourses = collect();
        foreach ($coursesList as $c) {
            $course = Course::firstOrCreate(
                ['code' => $c['code']],
                [
                    'name' => $c['name'],
                    'hours' => $c['hours'],
                    'credits' => $c['hours'],
                    'department_id' => $csDept->id,
                ]
            );
            $allCourses->push($course);
        }

        // 4. Define Doctors and Preference Patterns
        $doctors = [
            'Safaa' => ['strategy' => 'Complex', 'load' => 5],
            'Orwa' => ['strategy' => 'Complex', 'load' => 5],
            'Qutaiba' => ['strategy' => 'CoursesOnly', 'load' => 4],
            'Hanaa' => ['strategy' => 'CoursesOnly', 'load' => 4],
            'Dania' => ['strategy' => 'TimeFocused', 'load' => 3], // Specific Times, Any Day
            'Samer' => ['strategy' => 'TimeFocused', 'load' => 3],
            'Yazan' => ['strategy' => 'DayFocused', 'load' => 3], // Specific Days, Any Time
            'Lina' => ['strategy' => 'DayFocused', 'load' => 3],
            'Mohammad' => ['strategy' => 'Minimal', 'load' => 1],
            'Rami' => ['strategy' => 'Minimal', 'load' => 2],
        ];

        foreach ($doctors as $name => $config) {
            // Create/Get User
            $email = strtolower($name) . '@htu.edu.jo';
            // Exception for Mohammad who might be moh@gmail.com from UserSeeder
            if ($name === 'Mohammad') {
                $user = User::where('email', 'moh@gmail.com')->first();
                if (!$user) {
                    $user = User::firstOrCreate(
                        ['email' => $email],
                        ['name' => $name, 'password' => Hash::make('password'), 'role' => 'instructor']
                    );
                }
            } else {
                $user = User::firstOrCreate(
                    ['email' => $email],
                    ['name' => $name, 'password' => Hash::make('password'), 'role' => 'instructor']
                );
            }

            // Create/Get Instructor Profile
            $instructor = Instructor::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'position' => InstructorPosition::Doctor, // As requested "Doctor names"
                    'min_credits' => 12,
                ]
            );

            // Assign Department
            $instructor->departments()->syncWithoutDetaching([$csDept->id]);

            // Assign Preferences
            $this->assignPreferences($instructor, $config, $semester, $allCourses);
        }
    }

    protected function assignPreferences($instructor, $config, $semester, $allCourses)
    {
        // Shuffle courses to pick random ones for this instructor
        $myCourses = $allCourses->shuffle()->take($config['load']);
        $strategy = $config['strategy'];

        foreach ($myCourses as $course) {
            $pref = InstructorPreference::create([
                'instructor_id' => $instructor->id,
                'course_id' => $course->id,
                'semester_id' => $semester->id,
                'submission_time' => now()->subMinutes(rand(1, 10000)), // Random submission times
            ]);

            // Strategy Implementation
            switch ($strategy) {
                case 'CoursesOnly':
                    // NO Time slots created (Instructor matches Course Only)
                    break;

                case 'TimeFocused':
                    // Specific Times (e.g. Morning only), Any Days
                    // Days = null (Any), Time = '08:30' or '10:00'
                    $times = ['08:30:00', '10:00:00', '11:30:00'];
                    $chosenTime = $times[array_rand($times)];

                    $this->createPreferenceSlot($pref, null, $chosenTime);
                    break;

                case 'DayFocused':
                    // Specific Days (e.g. Sun/Wed), Any Time
                    $patterns = [['Sunday', 'Wednesday'], ['Monday', 'Thursday'], ['Tuesday', 'Saturday']];
                    $chosenPattern = $patterns[array_rand($patterns)];

                    $this->createPreferenceSlot($pref, $chosenPattern, null);
                    break;

                case 'Minimal':
                    // Just 1 or 2 courses, maybe simple preference or none
                    if (rand(0, 1)) {
                        // Some have simple preference
                        $this->createPreferenceSlot($pref, ['Sunday', 'Wednesday'], '08:30:00');
                    }
                    break;

                case 'Complex':
                default:
                    // Multi-slot preferences: "I can do Sun/Wed Morning OR Mon/Thu Afternoon"

                    // Slot 1: Sun/Wed Morning
                    $this->createPreferenceSlot($pref, ['Sunday', 'Wednesday'], '08:30:00');

                    // Slot 2: Mon/Thu Afternoon (Alternative option)
                    $this->createPreferenceSlot($pref, ['Monday', 'Thursday'], '13:00:00');

                    // Slot 3: Tuesday all day?
                    $this->createPreferenceSlot($pref, ['Tuesday'], null);
                    break;
            }
        }
    }

    protected function createPreferenceSlot(InstructorPreference $preference, ?array $days, ?string $startTime): void
    {
        PreferenceTimeSlot::create([
            'instructor_preference_id' => $preference->id,
            'days' => $days,
            'start_time' => $startTime,
            'end_time' => $startTime ? PreferenceTimeSlotFormatter::calculateEndFromStart($startTime) : null,
        ]);
    }
}
