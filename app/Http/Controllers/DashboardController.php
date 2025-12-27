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

        // --- Active Semester ---
        $activeSemester = Semester::whereIn('status', ['Open', 'Running'])->first()
            ?? Semester::orderBy('id', 'desc')->first();

        // --- Statistics ---

        // Fetch sections assigned to the instructor for the active semester
        $assignedSections = ($instructor && $activeSemester)
            ? $instructor->sections()
                ->where('semester_id', $activeSemester->id)
                ->with(['course', 'semester'])
                ->get()
            : collect();

        // Fetch preferences for the active semester
        $preferences = ($instructor && $activeSemester)
            ? InstructorPreference::where('instructor_id', $instructor->id)
                ->where('semester_id', $activeSemester->id)
                ->with('course')
                ->get()
            : collect();

        // Total assigned sections
        $totalSections = $assignedSections->count();

        if ($totalSections > 0) {
            // If sections are assigned, show data based on assignments
            $totalCourses = $assignedSections->pluck('course_id')->unique()->count();
            $currentLoad = $assignedSections->sum(function ($section) {
                return $section->course->credits ?? 0;
            });
        } else {
            // Otherwise show data based on preferences
            $totalCourses = $preferences->pluck('course_id')->unique()->count();
            $currentLoad = $preferences->sum(function ($pref) {
                return $pref->course->credits ?? 0;
            });
        }

        // --- Recent Preferences ---
        $recentPreferences = collect();
        if ($instructor) {
            // Fetch latest submissions
            $recentPreferences = InstructorPreference::where('instructor_id', $instructor->id)
                ->with(['semester'])
                ->orderBy('submission_time', 'desc')
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
