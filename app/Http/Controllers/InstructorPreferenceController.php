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
            return redirect()->route('instructor.home')->with('error', 'Instructor profile not found.');
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

        // Get available courses for the active semester
        $availableCourses = $activeSemester
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
            'preferred_days' => 'nullable|string',
            'preferred_time' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
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
                    $daysInfo = [];
                    if (!empty($validated['preferred_days'])) {
                        $daysInfo[] = $validated['preferred_days'];
                    }
                    if (!empty($validated['preferred_time'])) {
                        $daysInfo[] = $validated['preferred_time'];
                    }
                    if (!empty($validated['notes'])) {
                        $daysInfo[] = $validated['notes'];
                    }

                    PreferenceTimeSlot::create([
                        'instructor_preference_id' => $preference->id,
                        'days' => implode(' - ', $daysInfo),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('instructor.preferences')
                ->with('success', 'Preferences submitted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to save preferences. Please try again.');
        }
    }

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

        return response()->json([
            'semester' => $preferences->first()->semester,
            'submission_time' => $preferences->first()->submission_time,
            'courses' => $preferences->pluck('course'),
            'time_slots' => $preferences->flatMap->timeSlots->unique('days'),
        ]);
    }

    /**
     * Update existing preferences
     */
    public function update(Request $request, $semesterId)
    {
        $validated = $request->validate([
            'course_ids' => 'required|array|min:1',
            'course_ids.*' => 'exists:courses,id',
            'preferred_days' => 'nullable|string',
            'preferred_time' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
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
                    $daysInfo = [];
                    if (!empty($validated['preferred_days'])) {
                        $daysInfo[] = $validated['preferred_days'];
                    }
                    if (!empty($validated['preferred_time'])) {
                        $daysInfo[] = $validated['preferred_time'];
                    }
                    if (!empty($validated['notes'])) {
                        $daysInfo[] = $validated['notes'];
                    }

                    PreferenceTimeSlot::create([
                        'instructor_preference_id' => $preference->id,
                        'days' => implode(' - ', $daysInfo),
                    ]);
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
