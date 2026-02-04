<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\InstructorPreference;
use App\Models\PreferenceTimeSlot;
use App\Models\Semester;
use App\Support\PreferenceTimeSlotFormatter;
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
        $activeSemester = Semester::where('status', 'Open')->first();

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
            'preferred_days.*' => 'nullable|string|max:20',
            'preferred_time' => 'nullable|array',
            'preferred_time.*' => 'in:Morning,Noon,Afternoon',
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

            // Check if schedule is already generated
            $semester = Semester::find($validated['semester_id']);

            if ($semester->status === 'Scheduled' || $semester->sections()->exists()) {
                return back()->with('error', 'Cannot modify preferences because a schedule has already been generated for this semester.');
            }

            // Delete existing preferences for this semester (Only if allowed)
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
                $this->persistPreferenceTimeSlots(
                    $preference,
                    $validated['preferred_days'] ?? [],
                    $validated['preferred_time'] ?? []
                );
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
            'preferred_days.*' => 'nullable|string|max:20',
            'preferred_time' => 'nullable|array',
            'preferred_time.*' => 'in:Morning,Noon,Afternoon',
        ]);

        $user = auth()->user();
        $instructor = $user->instructor;
        if (!$instructor) {
            return back()->with('error', 'Instructor profile not found.');
        }

        try {
            DB::beginTransaction();

            // Check if schedule is already generated
            $semester = Semester::find($semesterId);
            if ($semester->status === 'Scheduled' || $semester->sections()->exists()) {
                return back()->with('error', 'Cannot update preferences because a schedule has already been generated for this semester.');
            }

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
                $this->persistPreferenceTimeSlots(
                    $preference,
                    $validated['preferred_days'] ?? [],
                    $validated['preferred_time'] ?? []
                );
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
            // Check if schedule is already generated
            $semester = Semester::find($semesterId);
            if ($semester->status === 'Scheduled' || $semester->sections()->exists()) {
                return back()->with('error', 'Cannot delete preferences because a schedule has already been generated for this semester.');
            }

            InstructorPreference::where('instructor_id', $instructor->id)
                ->where('semester_id', $semesterId)
                ->delete();

            return redirect()->route('instructor.preferences')
                ->with('success', 'Preferences deleted successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete preferences. Please try again.');
        }
    }

    protected function persistPreferenceTimeSlots(
        InstructorPreference $preference,
        array $preferredDays,
        array $preferredTimes
    ): void {
        $hasDaySelection = collect($preferredDays)->filter(fn($value) => !empty($value))->isNotEmpty();
        $hasTimeSelection = collect($preferredTimes)->filter(fn($value) => !empty($value))->isNotEmpty();

        if (!$hasDaySelection && !$hasTimeSelection) {
            return;
        }

        $dayTokens = $hasDaySelection ? $preferredDays : [null];
        $timeTokens = $hasTimeSelection ? $preferredTimes : [null];

        foreach ($dayTokens as $dayToken) {
            $days = PreferenceTimeSlotFormatter::normalizeDaysValue($dayToken);

            foreach ($timeTokens as $timeToken) {
                $time = PreferenceTimeSlotFormatter::normalizeTimeValue($timeToken);

                PreferenceTimeSlot::create([
                    'instructor_preference_id' => $preference->id,
                    'days' => $days,
                    'start_time' => $time['start'],
                    'end_time' => $time['end'],
                ]);
            }
        }
    }
}
