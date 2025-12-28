<?php

namespace App\Filament\Widgets;

use App\Models\Department;
use App\Models\Section;
use Filament\Widgets\ChartWidget;

class DepartmentsSectionsChart extends ChartWidget
{
    protected ?string $heading = 'Sections by Department';

    protected static ?int $sort = 5;

    protected function getData(): array
    {
        $departments = Department::withCount('courses')->get();

        $data = [];
        $labels = [];

        foreach ($departments as $department) {
            $sectionsCount = Section::whereHas('course', function ($query) use ($department) {
                $query->where('department_id', $department->id);
            })->count();

            if ($sectionsCount > 0) {
                $data[] = $sectionsCount;
                $labels[] = $department->name;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Sections',
                    'data' => $data,
                    'backgroundColor' => [
                        'rgba(139, 92, 246, 0.8)', // Violet
                        'rgba(6, 182, 212, 0.8)', // Cyan
                        'rgba(16, 185, 129, 0.8)', // Emerald
                        'rgba(245, 158, 11, 0.8)', // Amber
                        'rgba(244, 63, 94, 0.8)', // Rose
                        'rgba(59, 130, 246, 0.8)', // Blue
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
