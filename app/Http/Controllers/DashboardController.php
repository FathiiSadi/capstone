<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\InstructorPreference;
use App\Models\Semester;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the instructor dashboard.
     */
    public function index()
    {
        $user = auth()->user();

        if (!$user) {
            return view('index', [
                'user' => null,
                'totalCourses' => 0,
                'totalSections' => 0,
                'currentLoad' => 0,
                'activeSemester' => null,
                'recentPreferences' => collect(),
                'notifications' => collect(),
                'assignedSections' => collect(),
            ]);
        }

        // Eager load instructor if not already loaded to prevent N+1
        if (!$user->relationLoaded('instructor')) {
            $user->load('instructor');
        }

        $instructor = $user->instructor;

        // --- Statistics ---

        // Fetch sections assigned to the instructor with necessary relations
        $assignedSections = $instructor
            ? $instructor->sections()->with(['course', 'semester'])->get()
            : collect();

        // Calculate total unique courses
        $totalCourses = $assignedSections->pluck('course_id')->unique()->count();

        // Total assigned sections
        $totalSections = $assignedSections->count();

        // Calculate current load (sum of credit hours)
        $currentLoad = $assignedSections->sum(function ($section) {
            return $section->course->credits ?? 0;
        });

        // --- Active Semester ---
        // Fetch the currently open or running semester
        $activeSemester = Semester::whereIn('status', ['Open', 'Running'])->first();

        // --- Recent Preferences ---
        $recentPreferences = collect();
        if ($instructor) {
            // Fetch latest submissions, taking more than 5 to account for duplicates per semester
            $recentPreferences = InstructorPreference::where('instructor_id', $instructor->id)
                ->with(['semester'])
                ->orderBy('submission_time', 'desc')
                ->take(20)
                ->get()
                ->unique('semester_id') // Keep only the latest submission per semester
                ->take(5);
        }

        // --- Notifications ---
        $notifications = $user->unreadNotifications->take(5);

        return view('index', compact(
            'user',
            'totalCourses',
            'totalSections',
            'currentLoad',
            'activeSemester',
            'recentPreferences',
            'notifications',
            'assignedSections'
        ));
    }
}
