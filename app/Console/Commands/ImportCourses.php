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

        $departmentName = $row['department'] ?? $row['college'] ?? null;
        $departmentCode = $row['code'] ?? null;
        $managerName = $row['manager_name'] ?? null;

        $roomName = $row['room'] ?? $row['name'] ?? null;
        $building = $row['building'] ?? null;
        $capacity = $row['capacity'] ?? null;
        $type = $row['type'] ?? null;

        $courseCode = $row['code'] ?? $row['course_number'] ?? null;
        $courseName = $row['name'] ?? $row['course_name'] ?? null;
        // ... (rest of vars)
        $sectionNumber = $row['section_number'] ?? null;
        $hours = (int) ($row['ch'] ?? $row['c_h'] ?? $row['hours'] ?? 3);
        $credits = (int) ($row['credits'] ?? $hours);
        $sectionsCount = (int) ($row['sections'] ?? 1);
        $officeHours = filter_var($row['office_hours'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $instructorName = $row['instructor_name'] ?? $row['name'] ?? null; // name if user import
        $userEmail = $row['email'] ?? null;
        $userPassword = $row['password'] ?? 'password';
        $userRole = $row['role'] ?? 'instructor';
        $userPosition = $row['position'] ?? null;
        $userMinCredits = $row['min_credits'] ?? null;

        $timeClassroom = $row['time_classroom'] ?? null;

        // Detection logic
        if (empty($courseCode) || empty($courseName) || !is_string($courseCode)) {
            // Check if it's a User row
            if (!empty($userEmail)) {
                $this->processUserOnly($row);
                return;
            }
            // Check if it's a Room row
            if (!empty($roomName) && !empty($building)) {
                $this->processRoomOnly($row);
                return;
            }
            // Check if it's a Department row
            if (!empty($departmentName)) {
                $this->processDepartmentOnly($row);
                return;
            }
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
                'credits' => $credits,
                'sections' => $sectionsCount,
                'office_hours' => $officeHours,
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
            if (!$instructorUser && !empty($userEmail)) {
                $instructorUser = User::where('email', $userEmail)->first();
            }

            if (!$instructorUser) {
                $email = $userEmail ?? (Str::slug($instructorName) . '@example.com');
                $instructorUser = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $instructorName,
                        'password' => Hash::make($userPassword),
                        'role' => $userRole,
                        'department_id' => $department->id,
                    ]
                );
            }

            // Ensure Instructor model matches
            $instructor = $instructorUser->instructor;
            if (!$instructor && ($instructorUser->role === 'instructor' || $instructorUser->role === 'admin')) {
                $instructor = Instructor::create([
                    'user_id' => $instructorUser->id,
                    'position' => $userPosition,
                    'min_credits' => $userMinCredits,
                ]);
            } elseif ($instructor) {
                // Update instructor properties if provided
                if ($userPosition)
                    $instructor->update(['position' => $userPosition]);
                if ($userMinCredits)
                    $instructor->update(['min_credits' => $userMinCredits]);
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
        // 7. Section

        // Define default values
        $finalDays = !empty($days) ? $days : [];
        $finalStartTime = $startTime ? date('H:i:s', strtotime($startTime)) : null;
        $finalEndTime = $endTime ? date('H:i:s', strtotime($endTime)) : null;
        $finalRoomId = isset($room) ? $room->id : null;

        // Logic for Office Hours or Missing Time
        if ($course->office_hours) {
            // Force null for office hours courses
            $finalDays = null;
            $finalStartTime = null;
            $finalEndTime = null;
            $finalRoomId = null; // Usually no room or virtual
        } elseif (empty($finalStartTime) && $instructor) {
            // Standard course but NO time provided in CSV
            // Attempt smart assignment using our service
            // We need to resolve dependency. ideally inject, but in command we can instantiate.
            $assigner = new \App\Services\Scheduling\SlotAssignmentService(
                new \App\Services\Scheduling\CreditHourCalculator(),
                new \App\Services\Scheduling\TimeConflictChecker()
            );

            // We only simulate assignment here to get values? 
            // Or we actually let the service create it?
            // The service creates it. But we have logic below to updateOrCreate.
            // Let's check if section exists first.

            $existingSection = Section::where('course_id', $course->id)
                ->where('semester_id', $semester->id)
                ->where('section_number', $sectionNumber)
                ->first();

            $ignoreSectionId = $existingSection ? $existingSection->id : null;

            // Always attempt smart assignment if no manual time provided, overwriting previous bad assignments
            if (empty($finalStartTime) && $instructor) {
                // Instantiating services directly since we are in a command
                $calculator = new \App\Services\Scheduling\CreditHourCalculator();
                $checker = new \App\Services\Scheduling\TimeConflictChecker();

                $assigner = new \App\Services\Scheduling\SlotAssignmentService($calculator, $checker);

                // Use the refined logic that includes DAY PATTERN balancing and randomization
                $slot = $assigner->findOptimalSlot($instructor, $course, $semester, $ignoreSectionId);
                if ($slot) {
                    $finalDays = $slot['days'];
                    $finalStartTime = $slot['start_time'];
                    $finalEndTime = $slot['end_time'];
                }
            }
        }

        Section::updateOrCreate(
            [
                'course_id' => $course->id,
                'semester_id' => $semester->id,
                'section_number' => $sectionNumber,
            ],
            [
                'instructor_id' => $instructor ? $instructor->id : null,
                'room_id' => $finalRoomId,
                'days' => $finalDays,
                'start_time' => $finalStartTime,
                'end_time' => $finalEndTime,
                'status' => 'Active',
            ]
        );
    }

    private function processUserOnly($row): void
    {
        $name = $row['name'] ?? null;
        $email = $row['email'] ?? null;
        $password = $row['password'] ?? 'password';
        $role = $row['role'] ?? 'instructor';
        $departmentName = $row['department'] ?? $row['college'] ?? null;
        $position = $row['position'] ?? null;
        $minCredits = $row['min_credits'] ?? null;

        if (empty($name) || empty($email)) {
            return;
        }

        $department = null;
        if (!empty($departmentName)) {
            $department = Department::firstOrCreate(['name' => $departmentName], ['code' => Str::slug($departmentName)]);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => $role,
                'department_id' => $department?->id,
            ]);
        } else {
            $updateData = ['name' => $name, 'role' => $role];
            if ($department)
                $updateData['department_id'] = $department->id;
            $user->update($updateData);
        }

        if ($user->role === 'instructor' || $user->role === 'admin') {
            $instructor = $user->instructor;
            if (!$instructor) {
                Instructor::create([
                    'user_id' => $user->id,
                    'position' => $position,
                    'min_credits' => $minCredits,
                ]);
            } else {
                $updateData = [];
                if ($position)
                    $updateData['position'] = $position;
                if ($minCredits)
                    $updateData['min_credits'] = $minCredits;
                if (!empty($updateData))
                    $instructor->update($updateData);
            }
        }
    }

    private function processRoomOnly($row): void
    {
        $name = $row['name'] ?? $row['room'] ?? null;
        $building = $row['building'] ?? null;
        $capacity = $row['capacity'] ?? null;
        $type = $row['type'] ?? 'Lecture';

        if (empty($name))
            return;

        Room::updateOrCreate(
            ['name' => $name],
            [
                'building' => $building,
                'capacity' => $capacity,
                'type' => $type,
            ]
        );
    }

    private function processDepartmentOnly($row): void
    {
        $name = $row['name'] ?? $row['department'] ?? $row['college'] ?? null;
        $code = $row['code'] ?? Str::slug($name);
        $managerName = $row['manager_name'] ?? null;

        if (empty($name))
            return;

        $managerId = null;
        if (!empty($managerName)) {
            $manager = User::where('name', $managerName)->first();
            if ($manager) {
                $managerId = $manager->id;
            }
        }

        Department::updateOrCreate(
            ['name' => $name],
            [
                'code' => $code,
                'manager_id' => $managerId,
            ]
        );
    }
}
