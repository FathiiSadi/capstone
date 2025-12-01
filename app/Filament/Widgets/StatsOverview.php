<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use App\Models\Department;
use App\Models\Instructor;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Users', User::count())
                ->description('All registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart([7, 12, 15, 18, 22, 25, 28]),

            Stat::make('Courses', Course::count())
                ->description('Available courses')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary')
                ->chart([10, 12, 14, 16, 18, 20, 22]),

            Stat::make('Instructors', Instructor::count())
                ->description('Active instructors')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning')
                ->chart([5, 7, 9, 11, 13, 15, 17]),

            Stat::make('Departments', Department::count())
                ->description('Academic departments')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info')
                ->chart([3, 4, 5, 6, 7, 8, 9]),
        ];
    }
}
