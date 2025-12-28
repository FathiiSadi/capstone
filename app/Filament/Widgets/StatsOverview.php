<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use App\Models\Department;
use App\Models\Instructor;
use App\Models\User;
use App\Models\Section;
use App\Models\Semester;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $underloadedCount = Instructor::with(['sections.course'])
            ->get()
            ->filter(fn($i) => $i->sections->sum(fn($s) => $s->course->credits ?? 0) < $i->min_credits)
            ->count();

        $totalCredits = Section::join('courses', 'sections.course_id', '=', 'courses.id')
            ->sum('courses.credits');

        return [
            Stat::make('Academic Staff', Instructor::count())
                ->description('Active educators')
                ->descriptionIcon('heroicon-o-academic-cap')
                ->chart([5, 8, 12, 10, 15, 18, 17])
                ->color('info'),

            Stat::make('Scheduled Sections', Section::count())
                ->description('Active in current semester')
                ->descriptionIcon('heroicon-o-squares-2x2')
                ->chart([2, 5, 8, 12, 18, 15, 20])
                ->color('success'),

            Stat::make('Underloaded', $underloadedCount)
                ->description('Staff needing assignment')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->chart([8, 10, 7, 5, 4, 3, 2])
                ->color($underloadedCount > 0 ? 'warning' : 'success'),

            Stat::make('Total Courses', Course::count())
                ->description('Cataloged courses')
                ->descriptionIcon('heroicon-o-book-open')
                ->color('primary'),

            Stat::make('Assigned Credits', $totalCredits)
                ->description('Total credit hour load')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('success'),

            Stat::make('Departments', Department::count())
                ->description('Academic units')
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('info'),
        ];
    }
}
