<?php

namespace App\Filament\Widgets;

use App\Models\Instructor;
use Filament\Widgets\ChartWidget;

class InstructorLoadChart extends ChartWidget
{
    protected ?string $heading = 'Instructor Credit Load Distribution';

    protected static ?int $sort = 2;

    public function getColumnSpan(): int|string|array
    {
        return 2;
    }

    protected function getData(): array
    {
        $instructors = Instructor::with('sections.course')->get();

        $ranges = [
            '0-3' => 0,
            '4-6' => 0,
            '7-9' => 0,
            '10-12' => 0,
            '13-15' => 0,
            '16+' => 0,
        ];

        foreach ($instructors as $instructor) {
            $totalCredits = $instructor->sections->sum(fn($s) => $s->course->credits ?? 0);

            if ($totalCredits <= 3)
                $ranges['0-3']++;
            elseif ($totalCredits <= 6)
                $ranges['4-6']++;
            elseif ($totalCredits <= 9)
                $ranges['7-9']++;
            elseif ($totalCredits <= 12)
                $ranges['10-12']++;
            elseif ($totalCredits <= 15)
                $ranges['13-15']++;
            else
                $ranges['16+']++;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Number of Instructors',
                    'data' => array_values($ranges),
                    'backgroundColor' => 'rgba(139, 92, 246, 0.8)', // Violet
                    'borderColor' => 'rgb(139, 92, 246)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => array_keys($ranges),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
