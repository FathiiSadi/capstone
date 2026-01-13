<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Department;
use App\Models\Instructor;
use App\Models\Room;
use App\Models\Section;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImportCourses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-courses {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import courses from an Excel file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File not found: $file");
            return;
        }

        $this->info("Importing from $file...");

        $data = Excel::toCollection(new class implements \Maatwebsite\Excel\Concerns\ToCollection, \Maatwebsite\Excel\Concerns\WithHeadingRow {
            public function collection(\Illuminate\Support\Collection $rows)
            {
                return $rows;
            }
        }, $file)[0];

        // Headers are processed, so data starts from row 0
        $rows = $data;

        $this->withProgressBar($rows, function ($row) {
            $this->processRow($row);
        });

        $this->newLine();
        $this->info('Import completed successfully.');
    }

    private function processRow($row): void
    {
        // row is now an associative array (key = slugged header)

        $departmentName = $row['college'] ?? null;
        $courseCode = $row['course_number'] ?? null;
        $courseName = $row['course_name'] ?? null;
        $sectionNumber = $row['section_number'] ?? null;
        $hours = (int) ($row['hours'] ?? 3);
        $instructorName = $row['instructor_name'] ?? null;
        $timeClassroom = $row['time_classroom'] ?? null; // Slug might vary: 'time_classroom', 'time_classroom'

        if (empty($courseCode) || empty($courseName)) {
            return;
        }

        // 1. Department
        $department = Department::firstOrCreate(['name' => $departmentName], ['code' => Str::slug($departmentName)]);

        // 2. Course
        $course = Course::firstOrCreate(
            ['code' => $courseCode],
            [
                'name' => $courseName,
                'department_id' => $department->id,
                'hours' => $hours,
                'credits' => $hours, // Assuming credits = hours
            ]
        );

        // Update name if needed (optional)
        if ($course->name !== $courseName) {
            $course->update(['name' => $courseName]);
        }

        // 3. Instructor (User + Instructor model)
        $instructor = null;
        if (!empty($instructorName)) {
            $instructorUser = User::where('name', $instructorName)->first();
            if (!$instructorUser) {
                // Try reasonable email guess
                $email = Str::slug($instructorName) . '@example.com';
                $instructorUser = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $instructorName,
                        'password' => Hash::make('password'),
                        'role' => 'instructor',
                    ]
                );
            }
            // Ensure Instructor model matches
            $instructor = $instructorUser->instructor;
            if (!$instructor) {
                $instructor = Instructor::create(['user_id' => $instructorUser->id]);
            }
        }

        // 4. Time / Classroom Parsing
        // Example: "08,30 - 11,30  ح         /  المبنى الجنوبي الجديد S-212"
        $days = [];
        $startTime = null;
        $endTime = null;
        $roomName = null;

        if (!empty($timeClassroom)) {
            // Check for "Blended"
            if (stripos($timeClassroom, 'Blended') !== false) {
                // Logic for Blended? Maybe no specific time/room, or special room?
            } else {
                // Split by '/' to separate Time and Room
                $parts = explode('/', $timeClassroom);
                $timePart = trim($parts[0] ?? '');
                $roomPart = trim($parts[1] ?? 'TBA');

                // Normalize time format: replace ',' with ':' in times like "08,30"
                $timePart = preg_replace('/(\d{1,2}),(\d{2})/', '$1:$2', $timePart);

                // Extract Time Range
                if (preg_match('/(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})/', $timePart, $matches)) {
                    $startTime = $matches[1];
                    $endTime = $matches[2];
                }

                // Parse Days (Arabic or English)
                $arabicDayMap = [
                    'ح' => 'Sunday',
                    'ن' => 'Monday',
                    'ث' => 'Tuesday',
                    'ر' => 'Wednesday',
                    'خ' => 'Thursday',
                    'س' => 'Saturday',
                ];
                $englishDayMap = [
                    'MON' => 'Monday',
                    'TUE' => 'Tuesday',
                    'WED' => 'Wednesday',
                    'THU' => 'Thursday',
                    'FRI' => 'Friday',
                    'SAT' => 'Saturday',
                    'SUN' => 'Sunday',
                    'M' => 'Monday',
                    'T' => 'Tuesday',
                    'W' => 'Wednesday',
                    'TH' => 'Thursday',
                    'F' => 'Friday',
                ];

                // Check for Arabic characters
                foreach ($arabicDayMap as $char => $day) {
                    if (mb_strpos($timePart, $char) !== false) {
                        $days[] = $day;
                    }
                }

                // If no Arabic days found, try English
                if (empty($days)) {
                    $rawDays = preg_split('/[\s,]+/', $timePart);
                    foreach ($rawDays as $rd) {
                        $rd = strtoupper(trim($rd));
                        if (isset($englishDayMap[$rd])) {
                            $days[] = $englishDayMap[$rd];
                        }
                    }
                }

                $roomName = $roomPart;
                if (!empty($roomName)) {
                    $room = Room::firstOrCreate(['name' => $roomName]);
                }
            }
        }

        // 6. Semester
        // Need a semester. Let's find current or create default.
        $semester = Semester::firstOrCreate(['name' => 'Spring 2025'], [
            'type' => 'Spring',
            'preferences_open_at' => now(),
            'preferences_closed_at' => now()->addMonth(),
            'status' => 'Open',
        ]);

        // 7. Section
        // Find existing to avoid dupes?
        // Key: course_id, semester_id, section_number
        Section::updateOrCreate(
            [
                'course_id' => $course->id,
                'semester_id' => $semester->id,
                'section_number' => $sectionNumber,
            ],
            [
                'instructor_id' => $instructor ? $instructor->id : null,
                'room_id' => isset($room) ? $room->id : null,
                'days' => !empty($days) ? $days : [], // Casts to array/json
                'start_time' => $startTime ? date('H:i:s', strtotime($startTime)) : '00:00:00',
                'end_time' => $endTime ? date('H:i:s', strtotime($endTime)) : '00:00:00',
                'status' => 'Active',
            ]
        );
    }
}
