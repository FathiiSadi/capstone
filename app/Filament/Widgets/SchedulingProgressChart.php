<?php

namespace App\Filament\Widgets;

use App\Models\Section;
use App\Models\Semester;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SchedulingProgressChart extends ChartWidget
{
    protected ?string $heading = 'Sections by Day Pattern';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $sections = Section::all();

        $data = [
            'Sunday/Wednesday' => 0,
            'Monday/Thursday' => 0,
            'Tuesday/Saturday' => 0,
            'Other' => 0,
        ];

        foreach ($sections as $section) {
            $days = $section->days;
            if (is_array($days)) {
                $days = implode(',', $days);
            }

            if (str_contains($days, 'Sunday') && str_contains($days, 'Wednesday')) {
                $data['Sunday/Wednesday']++;
            } elseif (str_contains($days, 'Monday') && str_contains($days, 'Thursday')) {
                $data['Monday/Thursday']++;
            } elseif (str_contains($days, 'Tuesday') && str_contains($days, 'Saturday')) {
                $data['Tuesday/Saturday']++;
            } else {
                $data['Other']++;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Sections',
                    'data' => array_values($data),
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)', // Blue
                        'rgba(16, 185, 129, 0.8)', // Emerald
                        'rgba(245, 158, 11, 0.8)', // Amber
                        'rgba(107, 114, 128, 0.8)', // Slate/Gray
                    ],
                    'borderColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                        'rgb(107, 114, 128)',
                    ],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
