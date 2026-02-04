<?php

namespace App\Services\Scheduling;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\Section;
use App\Models\Semester;
use Illuminate\Support\Facades\DB;

class SectionQuotaService
{
    /**
     * Determine the global cap for how many sections a course can have in a semester.
     * Respects both the course definition and the semester pivot, using the strictest value.
     */
    public static function getGlobalSectionCap(Course $course, Semester $semester): int
    {
        $semesterCourse = self::getPivotRecord($course, $semester);

        $caps = array_filter([
            isset($course->sections) ? (int) $course->sections : 0,
            $semesterCourse?->sections_required ? (int) $semesterCourse->sections_required : 0,
        ]);

        if (!empty($caps)) {
            return max(1, min($caps));
        }

        return 999;
    }

    public static function hasAvailableSectionQuota(Course $course, Semester $semester, int $needed = 1): bool
    {
        $cap = self::getGlobalSectionCap($course, $semester);
        $current = Section::where('semester_id', $semester->id)
            ->where('course_id', $course->id)
            ->count();

        return ($current + $needed) <= $cap;
    }

    public static function getPerInstructorSectionLimit(Course $course, Semester $semester): int
    {
        $semesterCourse = self::getPivotRecord($course, $semester);

        if ($semesterCourse && (int) $semesterCourse->sections_per_instructor > 0) {
            return (int) $semesterCourse->sections_per_instructor;
        }

        return 2;
    }

    public static function getInstructorCourseCount(Instructor $instructor, Course $course, Semester $semester): int
    {
        return Section::where('semester_id', $semester->id)
            ->where('course_id', $course->id)
            ->where('instructor_id', $instructor->id)
            ->count();
    }

    protected static function getPivotRecord(Course $course, Semester $semester): ?object
    {
        return DB::table('semester_courses')
            ->where('semester_id', $semester->id)
            ->where('course_id', $course->id)
            ->first();
    }
}
