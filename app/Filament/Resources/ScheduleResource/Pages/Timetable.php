<?php

namespace App\Filament\Resources\ScheduleResource\Pages;

use App\Filament\Resources\ScheduleResource;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Instructor;
use App\Models\Department;
use Filament\Actions\Action; // <--- Import for the button
use Filament\Resources\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf; // <--- Import for PDF

class Timetable extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = ScheduleResource::class;
    protected string $view = 'filament.resources.schedule-resource.pages.timetable';
    protected static ?string $title = 'Master Schedule';

    public ?int $semester_id = null;
    public ?int $instructor_id = null;
    public ?int $department_id = null;
    public ?array $data = [];

    // --- 1. HEADER ACTIONS (The PDF Button) ---
    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Export to PDF')
                ->icon('heroicon-m-arrow-down-tray')
                ->action(function () {
                    // Get the filtered sections using the property below
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

    public function mount(): void
    {
        $this->semester_id = Semester::first()?->id;
        $this->form->fill(['semester_id' => $this->semester_id]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('semester_id')
                    ->label('Semester')
                    ->options(Semester::all()->pluck('name', 'id'))
                    ->live()
                    ->required()
                    ->afterStateUpdated(fn ($state) => $this->semester_id = $state),
                Select::make('department_id')
                    ->label('Department')
                    ->options(Department::all()->pluck('name', 'id'))
                    ->live()
                    ->placeholder('All Departments')
                    ->afterStateUpdated(fn ($state) => $this->department_id = $state),
                Select::make('instructor_id')
                    ->label('Instructor')
                    ->options(Instructor::with('user')->get()->pluck('user.name', 'id'))
                    ->live()
                    ->placeholder('All Instructors')
                    ->afterStateUpdated(fn ($state) => $this->instructor_id = $state),
            ])
            ->statePath('data')
            ->columns(3);
    }

    // --- Data Fetching Logic ---

    public function getSectionsProperty(): Collection
    {
        if (!$this->semester_id) return collect();

        $query = Section::query()
            ->where('semester_id', $this->semester_id)
            ->with(['course.department', 'instructor.user', 'room']);

        if ($this->instructor_id) $query->where('instructor_id', $this->instructor_id);
        
        if ($this->department_id) {
            $query->whereHas('course', fn($q) => $q->where('department_id', $this->department_id));
        }

        return $query->get();
    }

    public function getTimetableData(): array
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $slots = $this->getTimeSlots(); 
        $sections = $this->sections;

        $data = [];

        foreach ($days as $day) {
            $daySlots = [];
            for ($i = 0; $i < count($slots); $i++) {
                $slotStartStr = $slots[$i];
                $slotStart = Carbon::parse($slotStartStr);
                $slotEnd = $slotStart->copy()->addMinutes(90); 

                $daySlots[$slotStartStr] = $sections->filter(function ($section) use ($day, $slotStart, $slotEnd) {
                    $sectionDays = is_array($section->days) ? $section->days : (json_decode($section->days, true) ?? []);
                    if (!in_array($day, $sectionDays)) return false;

                    $secStart = Carbon::parse($section->start_time);
                    $secEnd = Carbon::parse($section->end_time);

                    return $secStart->lt($slotEnd) && $secEnd->gt($slotStart);
                });
            }
            $data[$day] = $daySlots;
        }

        return $data;
    }

    public function getTimeSlots(): array
    {
        return ['08:30:00', '10:00:00', '11:30:00', '13:00:00', '14:30:00', '16:00:00'];
    }

    public function getCourseColor(int $courseId): array
    {
        $palettes = [
            ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'text' => '#1e3a8a'],
            ['bg' => '#f0fdf4', 'border' => '#bbf7d0', 'text' => '#14532d'],
            ['bg' => '#faf5ff', 'border' => '#e9d5ff', 'text' => '#581c87'],
            ['bg' => '#fff1f2', 'border' => '#fecdd3', 'text' => '#881337'],
            ['bg' => '#fff7ed', 'border' => '#fed7aa', 'text' => '#7c2d12'],
        ];
        return $palettes[$courseId % count($palettes)];
    }
}