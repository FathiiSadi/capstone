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
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class Timetable extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = ScheduleResource::class;
    protected string $view = 'filament.resources.schedule-resource.pages.timetable';
    protected static ?string $title = 'Master Schedule';

    // We only need the data array, not individual properties
    public ?array $data = [];

    public function mount(): void
    {
        // 1. Find the best default semester
        $activeSemesterId = Section::latest('created_at')->value('semester_id') 
            ?? Semester::first()?->id;

        // 2. Initialize the form data directly
        $this->form->fill([
            'semester_id' => $activeSemesterId,
            'department_id' => null,
            'instructor_id' => null,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Export to PDF')
                ->icon('heroicon-m-arrow-down-tray')
                ->action(function () {
                    set_time_limit(300);
                    ini_set('memory_limit', '512M');

                    $sections = $this->sections;

                    if ($sections->isEmpty()) {
                        Notification::make()
                            ->title('No sections found')
                            ->body('Please check your filters.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Get semester ID from the form data
                    $semesterId = $this->data['semester_id'] ?? null;

                    $pdf = Pdf::loadView('filament.resources.schedule-resource.pages.timetable-pdf', [
                        'sections' => $sections,
                        'semester' => Semester::find($semesterId)
                    ]);

                    return response()->streamDownload(
                        fn() => print ($pdf->output()),
                        "schedule.pdf"
                    );
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('semester_id')
                    ->label('Semester')
                    ->options(Semester::all()->pluck('name', 'id'))
                    ->live() // This automatically updates $this->data['semester_id']
                    ->required(),
                
                Select::make('department_id')
                    ->label('Department')
                    ->options(Department::all()->pluck('name', 'id'))
                    ->live()
                    ->placeholder('All Departments'),
                
                Select::make('instructor_id')
                    ->label('Instructor')
                    ->options(Instructor::with('user')->get()->pluck('user.name', 'id'))
                    ->live()
                    ->placeholder('All Instructors'),
            ])
            ->statePath('data') // Binds inputs to $this->data
            ->columns(3);
    }

    public function getSectionsProperty(): Collection
    {
        // READ DIRECTLY FROM $this->data
        $semesterId = $this->data['semester_id'] ?? null;
        $departmentId = $this->data['department_id'] ?? null;
        $instructorId = $this->data['instructor_id'] ?? null;

        if (!$semesterId) return collect();

        $query = Section::query()
            ->where('semester_id', $semesterId)
            ->with(['course.department', 'instructor.user']);

        if ($departmentId) {
            $query->whereHas('course', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        if ($instructorId) {
            $query->where('instructor_id', $instructorId);
        }

        return $query->get();
    }

    public function getTimetableData(): array
    {
        $days = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
        $slots = $this->getTimeSlots();
        $sections = $this->sections;

        $data = [];
        // Initialize grid
        foreach ($days as $day) {
            foreach ($slots as $slot) {
                $data[$day][$slot] = collect();
            }
        }

        foreach ($sections as $section) {
            $sectionDays = is_array($section->days) ? $section->days : (json_decode($section->days, true) ?? []);
            
            if (!is_array($sectionDays)) continue;

            $secStart = substr($section->start_time, 0, 5);
            $secEnd = substr($section->end_time, 0, 5);

            foreach ($sectionDays as $day) {
                if (!isset($data[$day])) continue;

                foreach ($slots as $slotStart) {
                    $slotEndTimestamp = strtotime($slotStart) + (90 * 60);
                    $slotEnd = date('H:i', $slotEndTimestamp);
                    $slotStartShort = substr($slotStart, 0, 5);

                    if ($secStart < $slotEnd && $secEnd > $slotStartShort) {
                        $data[$day][$slotStart]->push($section);
                    }
                }
            }
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