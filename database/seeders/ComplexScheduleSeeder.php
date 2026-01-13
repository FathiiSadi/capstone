<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Department;
use App\Models\Instructor;
use App\Models\InstructorPreference;
use App\Models\PreferenceTimeSlot;
use App\Models\Section;
use App\Models\Semester;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ComplexScheduleSeeder extends Seeder
{
    public function run()
    {
        // 1. Cleanup
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Section::truncate();
        PreferenceTimeSlot::truncate();
        InstructorPreference::truncate();
        Instructor::truncate();
        Course::truncate();
        Department::truncate();
        Semester::truncate();
        User::where('email', 'like', '%@htu.edu.jo')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Setup Semester
        $semester = Semester::create([
            'name' => 'Fall 2025',
            'type' => 'Fall',
            'status' => 'Open',
            'preferences_open_at' => Carbon::now()->subDays(10),
            'preferences_closed_at' => Carbon::now()->addDays(10),
        ]);

        // 3. Departments
        $depts = ['Computer Science', 'Mathematics', 'Physics', 'Engineering'];
        $deptModels = [];
        foreach ($depts as $d) {
            $deptModels[$d] = Department::create([
                'name' => $d,
                'code' => strtoupper(substr($d, 0, 3))
            ]);
        }

        // 4. Courses (15 total)
        $courses = [];
        $courseData = [
            ['code' => 'CS101', 'name' => 'Intro to CS', 'dept' => 'Computer Science', 'off' => false, 'hours' => 3],
            ['code' => 'CS102', 'name' => 'Data Structures', 'dept' => 'Computer Science', 'off' => false, 'hours' => 3],
            ['code' => 'CS201', 'name' => 'Algorithms', 'dept' => 'Computer Science', 'off' => false, 'hours' => 3],
            ['code' => 'CS301', 'name' => 'OS', 'dept' => 'Computer Science', 'off' => false, 'hours' => 3],
            ['code' => 'CS401', 'name' => 'AI', 'dept' => 'Computer Science', 'off' => false, 'hours' => 3],
            ['code' => 'CS499', 'name' => 'Capstone (Online)', 'dept' => 'Computer Science', 'off' => true, 'hours' => 1], // Office Hours / Online
            ['code' => 'MATH101', 'name' => 'Calculus I', 'dept' => 'Mathematics', 'off' => false, 'hours' => 3],
            ['code' => 'MATH102', 'name' => 'Calculus II', 'dept' => 'Mathematics', 'off' => false, 'hours' => 3],
            ['code' => 'MATH201', 'name' => 'Linear Algebra', 'dept' => 'Mathematics', 'off' => false, 'hours' => 3],
            ['code' => 'PHYS101', 'name' => 'Physics I', 'dept' => 'Physics', 'off' => false, 'hours' => 3],
            ['code' => 'PHYS102', 'name' => 'Physics II', 'dept' => 'Physics', 'off' => false, 'hours' => 3],
            ['code' => 'ENG101', 'name' => 'Engineering I', 'dept' => 'Engineering', 'off' => false, 'hours' => 3],
            ['code' => 'ENG201', 'name' => 'Statics', 'dept' => 'Engineering', 'off' => false, 'hours' => 3],
            ['code' => 'ENG301', 'name' => 'Dynamics', 'dept' => 'Engineering', 'off' => false, 'hours' => 3],
            ['code' => 'ENG400', 'name' => 'Project', 'dept' => 'Engineering', 'off' => true, 'hours' => 2],
        ];

        foreach ($courseData as $c) {
            $courses[] = Course::create([
                'code' => $c['code'],
                'name' => $c['name'],
                'department_id' => $deptModels[$c['dept']]->id,
                'hours' => $c['hours'],
                'credits' => $c['hours'],
                'office_hours' => $c['off'],
                'sections' => 2, // Default
            ]);
        }

        // 5. Instructors (10)
        $instructors = [];
        $scenarios = [
            'Orwa Ad.' => ['load' => 5, 'pref' => 'Sun/Wed'],
            'Samer Sl.' => ['load' => 3, 'pref' => 'Morning'],
            'Dania Al.' => ['load' => 3, 'pref' => 'Afternoon'],
            'Hanaa Al.' => ['load' => 4, 'pref' => null],
            'Yazan Sh.' => ['load' => 2, 'pref' => 'Tue/Sat'],
            'Qutaiba Al.' => ['load' => 3, 'pref' => 'Specific'],
            'Safaa Hr.' => ['load' => 2, 'pref' => null], // Mainly online courses
            'Sultan Ra.' => ['load' => 2, 'pref' => 'Mon/Thu'],
            'Mohammad Ya.' => ['load' => 4, 'pref' => null],
            'Razan Dm.' => ['load' => 1, 'pref' => 'Sun/Wed'],
        ];

        $i = 1;
        foreach ($scenarios as $name => $data) {
            $user = User::create([
                'name' => $name,
                'email' => strtolower(str_replace(' ', '.', $name)) . '@htu.edu.jo',
                'password' => Hash::make('password'),
                'role' => 'instructor'
            ]);
            $inst = Instructor::create([
                'user_id' => $user->id,
                'position' => 'doctor',
                'min_credits' => 12
            ]);
            $instructors[] = ['model' => $inst, 'data' => $data];
            $i++;
        }

        // 6. Assignments & Preferences
        foreach ($instructors as $instData) {
            $inst = $instData['model'];
            $scenario = $instData['data'];
            $load = $scenario['load'];
            $prefType = $scenario['pref'];

            // Pick random courses
            $pickedCourses = collect($courses)->random($load);

            foreach ($pickedCourses as $course) {
                // Create Preference
                $pref = InstructorPreference::create([
                    'instructor_id' => $inst->id,
                    'course_id' => $course->id,
                    'semester_id' => $semester->id,
                    'submission_time' => now(),
                ]);

                // Create Preference Time Slots based on scenario
                if ($prefType && !$course->office_hours) {
                    $this->createTimeSlot($pref, $prefType);
                }

                // Create Section (Unscheduled)
                // We create ONE section per preference to simulate assignment
                Section::create([
                    'course_id' => $course->id,
                    'section_number' => 'S' . rand(1, 99),
                    'semester_id' => $semester->id,
                    'instructor_id' => $inst->id,
                    // Leave Time/Days NULL to let the scheduler/allocator fill them
                    'days' => null,
                    'start_time' => null,
                    'end_time' => null,
                    'status' => 'Active',
                ]);
            }
        }
    }

    private function createTimeSlot($preference, $type)
    {
        $days = [];
        $time = '08:30:00';

        switch ($type) {
            case 'Sun/Wed':
                $days = ['Sunday', 'Wednesday'];
                $time = '08:30:00';
                break;
            case 'Mon/Thu':
                $days = ['Monday', 'Thursday'];
                $time = '10:00:00';
                break;
            case 'Tue/Sat':
                $days = ['Tuesday', 'Saturday'];
                $time = '13:00:00';
                break;
            case 'Morning':
                $days = ['Sunday', 'Wednesday']; // Could range
                $time = '08:30:00';
                break;
            case 'Afternoon':
                $days = ['Monday', 'Thursday'];
                $time = '14:30:00';
                break;
            case 'Specific':
                $days = ['Tuesday'];
                $time = '11:30:00';
                break;
        }

        if (!empty($days)) {
            PreferenceTimeSlot::create([
                'instructor_preference_id' => $preference->id,
                'days' => $days,
                'start_time' => $time,
            ]);
        }
    }
}
