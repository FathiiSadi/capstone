<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\Section;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Sections', Section::count())
                ->description('Total sections managed')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),

            Stat::make('Unassigned Sections', Section::whereNull('instructor_id')->count())
                ->description('Sections needing instructors')
                ->descriptionIcon('heroicon-m-user-minus')
                ->chart([3, 5, 2, 8, 4, 9, 1])
                ->color('danger'),

            Stat::make('Active Instructors', Instructor::count())
                ->description('Total instructors registered')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
        ];
    }
}
