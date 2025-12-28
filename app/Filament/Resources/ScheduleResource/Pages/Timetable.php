<?php

namespace App\Filament\Resources\ScheduleResource\Pages;

use App\Filament\Resources\ScheduleResource;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Instructor;
use App\Models\Department;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Illuminate\Support\Collection;
use Barryvdh\DomPDF\Facade\Pdf;

class Timetable extends Page implements HasForms
{
    use InteractsWithForms;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Export to PDF')
                ->icon('heroicon-m-arrow-down-tray')
                ->action(function () {
                    $sections = $this->sections;
                    $pdf = Pdf::loadView('filament.resources.schedule-resource.pages.timetable-pdf', [
                        'sections' => $sections,
                        'semester' => Semester::find($this->semester_id)
                    ]);
                    return response()->streamDownload(
                        fn() => print ($pdf->output()),
                        "schedule-semester-{$this->semester_id}.pdf"
                    );
                }),
        ];
    }

    protected static string $resource = ScheduleResource::class;

    protected string $view = 'filament.resources.schedule-resource.pages.timetable';

    public ?int $semester_id = null;
    public ?int $instructor_id = null;
    public ?int $department_id = null;

    public function mount(): void
    {
        $this->semester_id = Semester::first()?->id;
        $this->form->fill([
            'semester_id' => $this->semester_id,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('semester_id')
                    ->label('Semester')
                    ->options(Semester::all()->pluck('name', 'id'))
                    ->live()
                    ->required(),
                Select::make('instructor_id')
                    ->label('Instructor')
                    ->options(Instructor::with('user')->get()->pluck('user.name', 'id'))
                    ->live()
                    ->placeholder('All Instructors'),
                Select::make('department_id')
                    ->label('Department')
                    ->options(Department::all()->pluck('name', 'id'))
                    ->live()
                    ->placeholder('All Departments'),
            ])
            ->columns(3);
    }

    public function getSectionsProperty(): Collection
    {
        if (!$this->semester_id) {
            return collect();
        }

        $query = Section::query()
            ->where('semester_id', $this->semester_id)
            ->with(['course.department', 'instructor.user', 'room']);

        if ($this->instructor_id) {
            $query->where('instructor_id', $this->instructor_id);
        }

        if ($this->department_id) {
            $query->whereHas('course', function ($q) {
                $q->where('department_id', $this->department_id);
            });
        }

        return $query->get();
    }

    public function getTimetableData(): array
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $sections = $this->sections;

        $data = [];
        foreach ($days as $day) {
            $data[$day] = $sections->filter(function ($section) use ($day) {
                $daysArray = is_array($section->days) ? $section->days : [$section->days];
                return in_array($day, $daysArray);
            })->sortBy('start_time');
        }

        return $data;
    }

    public function getConflictsProperty(): Collection
    {
        $sections = $this->sections;
        $conflicts = collect();

        foreach ($sections as $sectionA) {
            foreach ($sections as $sectionB) {
                if ($sectionA->id === $sectionB->id) {
                    continue;
                }

                // Check for Room Conflict
                if ($sectionA->room_id && $sectionB->room_id && $sectionA->room_id === $sectionB->room_id) {
                    if ($this->hasTimeOverlap($sectionA, $sectionB)) {
                        $conflicts->push([
                            'type' => 'Room Conflict',
                            'section_a' => $sectionA,
                            'section_b' => $sectionB,
                            'message' => "Room {$sectionA->room->name} double booked."
                        ]);
                    }
                }
            }
        }

        return $conflicts->unique(function ($item) {
            return $item['section_a']->id < $item['section_b']->id ?
                $item['section_a']->id . '-' . $item['section_b']->id :
                $item['section_b']->id . '-' . $item['section_a']->id;
        });
    }

    protected function hasTimeOverlap($sectionA, $sectionB): bool
    {
        $daysA = is_array($sectionA->days) ? $sectionA->days : [$sectionA->days];
        $daysB = is_array($sectionB->days) ? $sectionB->days : [$sectionB->days];

        $commonDays = array_intersect($daysA, $daysB);

        if (empty($commonDays)) {
            return false;
        }

        return $sectionA->start_time < $sectionB->end_time && $sectionA->end_time > $sectionB->start_time;
    }

    public function getCourseColor(int $courseId): array
    {
        $palettes = [
            // [Background, Border, Text, Accent, Shadow]
            ['bg-indigo-500/10', 'border-indigo-500/20', 'text-indigo-600 dark:text-indigo-400', 'bg-indigo-500', 'shadow-indigo-500/20'],
            ['bg-emerald-500/10', 'border-emerald-500/20', 'text-emerald-600 dark:text-emerald-400', 'bg-emerald-500', 'shadow-emerald-500/20'],
            ['bg-violet-500/10', 'border-violet-500/20', 'text-violet-600 dark:text-violet-400', 'bg-violet-500', 'shadow-violet-500/20'],
            ['bg-rose-500/10', 'border-rose-500/20', 'text-rose-600 dark:text-rose-400', 'bg-rose-500', 'shadow-rose-500/20'],
            ['bg-sky-500/10', 'border-sky-500/20', 'text-sky-600 dark:text-sky-400', 'bg-sky-500', 'shadow-sky-500/20'],
            ['bg-amber-500/10', 'border-amber-500/20', 'text-amber-600 dark:text-amber-400', 'bg-amber-500', 'shadow-amber-500/20'],
            ['bg-fuchsia-500/10', 'border-fuchsia-500/20', 'text-fuchsia-600 dark:text-fuchsia-400', 'bg-fuchsia-500', 'shadow-fuchsia-500/20'],
            ['bg-cyan-500/10', 'border-cyan-500/20', 'text-cyan-600 dark:text-cyan-400', 'bg-cyan-500', 'shadow-cyan-500/20'],
        ];

        return $palettes[$courseId % count($palettes)];
    }

    public function getTimeSlots(): array
    {
        // Define standard time slots for the grid header
        return [
            '08:30:00',
            '10:00:00',
            '11:30:00',
            '13:00:00',
            '14:30:00',
            '16:00:00'
        ];
    }
}
