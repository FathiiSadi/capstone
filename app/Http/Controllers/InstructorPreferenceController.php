<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\InstructorPreference;
use App\Models\PreferenceTimeSlot;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstructorPreferenceController extends Controller
{
    /**
     * Display the instructor preferences page with existing submissions
     */
    public function index()
    {
        $user = auth()->user();
        $instructor = $user->instructor;
        if (!$instructor) {
            return redirect()->route('home')->with('error', 'Instructor profile not found.');
        }

        // Get all preferences grouped by semester with submission time
        $preferences = InstructorPreference::where('instructor_id', $instructor->id)
            ->with(['semester', 'course', 'timeSlots'])
            ->get()
            ->groupBy('semester_id')
            ->map(function ($semesterPrefs) {
                $firstPref = $semesterPrefs->first();
                return [
                    'semester' => $firstPref->semester,
                    'submission_time' => $firstPref->submission_time,
                    'courses' => $semesterPrefs->pluck('course'),
                    'time_slots' => $semesterPrefs->flatMap->timeSlots->unique('days'),
                    'preference_ids' => $semesterPrefs->pluck('id'),
                ];
            });

        // Get active semester for new submissions
        $activeSemester = Semester::where('status', 'active')->first();

        // If no active semester, try to get the most recent semester
        if (!$activeSemester) {
            $activeSemester = Semester::orderBy('created_at', 'desc')->first();
        }

        // Get available courses for the active semester
        $availableCourses = $activeSemester && $activeSemester->courses->isNotEmpty()
            ? $activeSemester->courses
            : Course::all();

        return view('instructorPreferences', compact('preferences', 'activeSemester', 'availableCourses'));
    }

    /**
     * Store new preference submission
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'semester_id' => 'required|exists:semesters,id',
            'course_ids' => 'required|array|min:1',
            'course_ids.*' => 'exists:courses,id',
            'preferred_days' => 'nullable|array',
            'preferred_time' => 'nullable|array',
        ]);

        $user = auth()->user();
        $instructor = $user->instructor;
        if (!$instructor) {
            return back()->withInput()->with('error', 'Instructor profile not found.');
        }

        // Check if semester exists
        if (!$validated['semester_id']) {
            return back()->withInput()->with('error', 'No semester selected. Please contact administrator.');
        }

        try {
            DB::beginTransaction();

            // Delete existing preferences for this semester
            InstructorPreference::where('instructor_id', $instructor->id)
                ->where('semester_id', $validated['semester_id'])
                ->delete();

            // Create preferences for each selected course
            foreach ($validated['course_ids'] as $courseId) {
                $preference = InstructorPreference::create([
                    'instructor_id' => $instructor->id,
                    'course_id' => $courseId,
                    'semester_id' => $validated['semester_id'],
                    'submission_time' => now(),
                ]);

                // Create time slot preference if provided
                if (!empty($validated['preferred_days']) || !empty($validated['preferred_time'])) {
                    $preferredDays = $validated['preferred_days'] ?? [];
                    $preferredTimes = $validated['preferred_time'] ?? [];

                    // If we have both, we create a combination?
                    // Or just store them? The current UI seems to allow multiple.
                    // For now, let's store each pattern and each time range combination if they are both provided.
                    // Actually, the current schema is one record per preference.
                    // Let's store days as JSON array and use start_time as a representative for the range.

                    $timeMap = [
                        'Morning' => '08:30:00',
                        'Noon' => '11:30:00',
                        'Afternoon' => '14:30:00',
                    ];

                    foreach ($preferredDays as $dayPattern) {
                        foreach ($preferredTimes as $timeRange) {
                            PreferenceTimeSlot::create([
                                'instructor_preference_id' => $preference->id,
                                'days' => [$dayPattern], // Store as array
                                'start_time' => $timeMap[$timeRange] ?? '08:30:00',
                            ]);
                        }
                    }

                    // If only days or only times provided
                    if (empty($preferredTimes) && !empty($preferredDays)) {
                        foreach ($preferredDays as $dayPattern) {
                            PreferenceTimeSlot::create([
                                'instructor_preference_id' => $preference->id,
                                'days' => [$dayPattern],
                                'start_time' => null,
                            ]);
                        }
                    } elseif (empty($preferredDays) && !empty($preferredTimes)) {
                        foreach ($preferredTimes as $timeRange) {
                            PreferenceTimeSlot::create([
                                'instructor_preference_id' => $preference->id,
                                'days' => null,
                                'start_time' => $timeMap[$timeRange] ?? '08:30:00',
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return redirect()->route('instructor.preferences')
                ->with('success', 'Preferences submitted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to save preferences: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Failed to save preferences. Please try again. Error: ' . $e->getMessage());
        }
    }

    /**
     * Show specific preference details
     */
    /**
     * Show specific preference details
     */
    public function show($semesterId)
    {
        $user = auth()->user();
        $instructor = $user->instructor;

        $preferences = InstructorPreference::where('instructor_id', $instructor->id)
            ->where('semester_id', $semesterId)
            ->with(['semester', 'course', 'timeSlots'])
            ->get();

        if ($preferences->isEmpty()) {
            return back()->with('error', 'Preferences not found.');
        }

        // Format data for the view
        $preferenceData = [
            'semester' => $preferences->first()->semester,
            'submission_time' => $preferences->first()->submission_time,
            'courses' => $preferences->pluck('course'),
            'time_slots' => $preferences->flatMap->timeSlots->unique('days'),
        ];

        return view('profile', compact('user', 'preferenceData'));
    }

    /**
     * Update existing preferences
     */
    public function update(Request $request, $semesterId)
    {
        $validated = $request->validate([
            'course_ids' => 'required|array|min:1',
            'course_ids.*' => 'exists:courses,id',
            'preferred_days' => 'nullable|array',
            'preferred_time' => 'nullable|array',
        ]);

        $user = auth()->user();
        $instructor = $user->instructor;
        if (!$instructor) {
            return back()->with('error', 'Instructor profile not found.');
        }

        try {
            DB::beginTransaction();

            // Delete existing preferences for this semester
            InstructorPreference::where('instructor_id', $instructor->id)
                ->where('semester_id', $semesterId)
                ->delete();

            // Create new preferences
            foreach ($validated['course_ids'] as $courseId) {
                $preference = InstructorPreference::create([
                    'instructor_id' => $instructor->id,
                    'course_id' => $courseId,
                    'semester_id' => $semesterId,
                    'submission_time' => now(),
                ]);

                // Create time slot preference if provided
                if (!empty($validated['preferred_days']) || !empty($validated['preferred_time'])) {
                    $preferredDays = $validated['preferred_days'] ?? [];
                    $preferredTimes = $validated['preferred_time'] ?? [];

                    $timeMap = [
                        'Morning' => '08:30:00',
                        'Noon' => '11:30:00',
                        'Afternoon' => '14:30:00',
                    ];

                    foreach ($preferredDays as $dayPattern) {
                        foreach ($preferredTimes as $timeRange) {
                            PreferenceTimeSlot::create([
                                'instructor_preference_id' => $preference->id,
                                'days' => [$dayPattern],
                                'start_time' => $timeMap[$timeRange] ?? '08:30:00',
                            ]);
                        }
                    }

                    if (empty($preferredTimes) && !empty($preferredDays)) {
                        foreach ($preferredDays as $dayPattern) {
                            PreferenceTimeSlot::create([
                                'instructor_preference_id' => $preference->id,
                                'days' => [$dayPattern],
                                'start_time' => null,
                            ]);
                        }
                    } elseif (empty($preferredDays) && !empty($preferredTimes)) {
                        foreach ($preferredTimes as $timeRange) {
                            PreferenceTimeSlot::create([
                                'instructor_preference_id' => $preference->id,
                                'days' => null,
                                'start_time' => $timeMap[$timeRange] ?? '08:30:00',
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return redirect()->route('instructor.preferences')
                ->with('success', 'Preferences updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update preferences. Please try again.');
        }
    }

    /**
     * Delete preferences for a semester
     */
    public function destroy($semesterId)
    {
        $user = auth()->user();
        $instructor = $user->instructor;

        try {
            InstructorPreference::where('instructor_id', $instructor->id)
                ->where('semester_id', $semesterId)
                ->delete();

            return redirect()->route('instructor.preferences')
                ->with('success', 'Preferences deleted successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete preferences. Please try again.');
        }
    }
}
