<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use App\Services\Scheduling\SchedulerService;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request, SchedulerService $scheduler)
    {
        $user = auth()->user();
        $isAdmin = $user->role === 'admin';
        $viewAll = $request->boolean('all', false);

        $semesters = Semester::orderByDesc('start_date')->get();
        $selectedSemesterId = $request->input('semester_id') ?? $semesters->first()?->id;

        $selectedSemester = $selectedSemesterId
            ? Semester::with(['sections.course.department', 'sections.instructor.user', 'sections.room'])
                ->find($selectedSemesterId)
            : null;

        $query = $selectedSemester ? $selectedSemester->sections() : null;

        if ($query) {
            $query->with(['course.department', 'instructor.user', 'room'])
                ->orderBy('start_time');

            // If not admin OR if admin hasn't requested "all", filter by the current user's instructor record
            if (!$isAdmin || !$viewAll) {
                $instructor = $user->instructor;
                if ($instructor) {
                    $query->where('instructor_id', $instructor->id);
                } else if (!$isAdmin) {
                    // Non-admin with no instructor record shouldn't see anything
                    $query->whereRaw('1 = 0');
                }
            }

            $sections = $query->get();
        } else {
            $sections = collect();
        }

        // Only show report if viewing all (Admins only)
        $report = ($selectedSemester && $isAdmin && $viewAll)
            ? $scheduler->getScheduleReport($selectedSemester)
            : [
                'total_sections' => $sections->count(),
                'total_instructors' => $sections->pluck('instructor_id')->filter()->unique()->count(),
                'instructor_loads' => collect(),
                'underloaded' => collect(),
            ];

        return view('schedule', [
            'semesters' => $semesters,
            'selectedSemester' => $selectedSemester,
            'sections' => $sections,
            'report' => $report,
            'isAdmin' => $isAdmin,
            'viewAll' => $viewAll,
        ]);
    }
}
