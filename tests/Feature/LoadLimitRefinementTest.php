<?php

use App\Models\Course;
use App\Models\Department;
use App\Models\Instructor;
use App\Models\InstructorPreference;
use App\Models\PreferenceTimeSlot;
use App\Models\Section;
use App\Models\Semester;
use App\Models\User;
use App\Services\Scheduling\SchedulerService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('atomic pair assignment does not exceed minimum load', function () {
    // 1. Setup Data
    $user = User::factory()->create(['name' => 'Dr. Borderline']);
    $department = Department::create(['name' => 'IT', 'code' => 'IT']);
    $instructor = Instructor::create([
        'user_id' => $user->id,
        'type' => 'Full-time',
        'status' => 'Active',
        'min_credits' => 15, // Target: 15 Credits.
    ]);
    $instructor->departments()->attach($department->id);

    $course = Course::create([
        'name' => 'Data Structures',
        'code' => 'IT201',
        'hours' => 3.0,
        'credits' => 3.0,
        'sections' => 2,
        'department_id' => $department->id,
        'office_hours' => false,
    ]);

    $semester = Semester::create([
        'name' => 'Spring 2027',
        'type' => 'Spring',
        'start_date' => now(),
        'end_date' => now()->addMonths(4),
        'status' => 'Open',
        'is_active' => true,
        'preferences_open_at' => now()->subDays(10),
        'preferences_closed_at' => now()->addDays(10),
    ]);

    // Pre-fill instructor with 12 credits (4 sections of 3 credits)
    // We can simulate this by creating sections directly.
    for ($i = 1; $i <= 4; $i++) {
        $dummyCourse = Course::create(['name' => "Dummy $i", 'code' => "DM$i", 'hours' => 3, 'credits' => 3, 'sections' => 1, 'department_id' => $department->id]);
        Section::create([
            'course_id' => $dummyCourse->id,
            'section_number' => "S$i",
            'semester_id' => $semester->id,
            'instructor_id' => $instructor->id,
            'days' => ['Sunday', 'Wednesday'],
            'start_time' => '12:00:00',
            'end_time' => '13:30:00',
            'status' => 'Active',
        ]);
    }

    // Current Load: 12 Credits.
    // Target Load: 15 Credits.
    // Gap: 3 Credits.

    // 2. Create Preference for IT201 (3 Credits)
    // Instructor prefers 08:30 Sun/Wed.
    // Since IT201 has 2 sections quota, system will TRY to assign Atomic Pair (2 sections = 6 credits).
    // Assignment of 2 sections -> 12 + 6 = 18 Credits.
    // 18 > 15 (Target).
    // EXPECTATION: System should only assign 1 section (3 credits) -> Total 15.

    $preference = InstructorPreference::create([
        'instructor_id' => $instructor->id,
        'course_id' => $course->id,
        'semester_id' => $semester->id,
        'submission_time' => now(),
    ]);

    PreferenceTimeSlot::create([
        'instructor_preference_id' => $preference->id,
        'days' => ['Sunday', 'Wednesday'],
        'start_time' => '08:30:00',
        'end_time' => '10:00:00',
    ]);

    // 3. Run Scheduler (Do NOT clear existing, so we keep the 12 credits)
    $scheduler = app(SchedulerService::class);
    $result = $scheduler->generateSchedule($semester, ['clear_existing' => false]);

    // 4. Assertions
    $newSections = Section::where('semester_id', $semester->id)
        ->where('course_id', $course->id)
        ->where('instructor_id', $instructor->id)
        ->get();

    // Should only assign ONE section to hit the target exactly
    expect($newSections)->toHaveCount(1);

    // Verify total load is exactly 15
    $totalCredits = Section::where('semester_id', $semester->id)
        ->where('instructor_id', $instructor->id)
        ->get()
        ->sum(fn($s) => $s->course->credits ?? 3);

    expect($totalCredits)->toEqual(15.0);
});
