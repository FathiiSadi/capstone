<?php

namespace App\Console\Commands;

use App\Models\Semester;
use App\Models\Section;
use App\Services\Scheduling\SchedulerService;
use Illuminate\Console\Command;

class ViewScheduleCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'schedule:view {semester_id? : The ID of the semester} {--grid : Display in a day-oriented grid format}';

    /**
     * The console command description.
     */
    protected $description = 'Display the current schedule for a semester';

    /**
     * Execute the console command.
     */
    public function handle(SchedulerService $schedulerService): int
    {
        $semesterId = $this->argument('semester_id') ?? Semester::first()?->id;

        if (!$semesterId) {
            $this->error('No semester found.');
            return Command::FAILURE;
        }

        $semester = Semester::find($semesterId);
        if (!$semester) {
            $this->error("Semester with ID {$semesterId} not found.");
            return Command::FAILURE;
        }

        $report = $schedulerService->getScheduleReport($semester);

        $this->info("=== Full Schedule Report for: {$semester->name} ===");
        $this->newLine();

        if ($report['sections']->isEmpty()) {
            $this->warn('No sections scheduled for this semester.');
            return Command::SUCCESS;
        }

        if ($this->option('grid')) {
            $this->displayGrid($report['sections']);
        } else {
            $this->displayInstructorSections($report);
        }

        $this->info("Summary:");
        $this->line("Total Sections: " . $report['total_sections']);
        $this->line("Total Instructors: " . $report['total_instructors']);

        if ($report['conflicts']->isNotEmpty()) {
            $this->warn("⚠ Time Conflicts Detected: " . $report['conflicts']->count());
        }

        return Command::SUCCESS;
    }

    /**
     * Display sections grouped by instructor (default view).
     */
    protected function displayInstructorSections(array $report): void
    {
        $groupedSections = $report['sections']->groupBy('instructor_id');

        foreach ($groupedSections as $instructorId => $sections) {
            $instructor = $sections->first()->instructor;
            $load = $report['instructor_loads'][$instructorId] ?? null;

            $this->info("<fg=cyan>Instructor: " . ($instructor->name ?: 'Unknown (ID: ' . $instructorId . ')') . "</>");
            if ($load) {
                $statusColor = $load->isUnderloaded() ? 'yellow' : 'green';
                $this->line("Load Status: <fg={$statusColor}>" . $load->status->label() . "</> ({$load->totalAssignedCredits} / {$load->minimumRequiredCredits} credits)");
            }

            $tableData = $sections->map(function ($section) {
                $days = is_array($section->days) ? implode(', ', $section->days) : $section->days;
                return [
                    $section->course->name,
                    $days,
                    $section->start_time . ' - ' . $section->end_time,
                    ($section->course->hours ?? 'N/A') . ' hours',
                    $section->status,
                ];
            });

            $this->table(
                ['Course', 'Days', 'Time', 'Duration', 'Status'],
                $tableData->toArray()
            );
            $this->newLine();
        }
    }

    /**
     * Display sections in a day-oriented grid format.
     */
    protected function displayGrid($sections): void
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        foreach ($days as $day) {
            $daySections = $sections->filter(function ($section) use ($day) {
                $daysArray = is_array($section->days) ? $section->days : [$section->days];
                return in_array($day, $daysArray);
            })->sortBy('start_time');

            if ($daySections->isEmpty()) {
                continue;
            }

            $this->info("<fg=yellow;options=bold>{$day}</>");

            $tableData = $daySections->map(function ($section) {
                return [
                    $section->start_time . ' - ' . $section->end_time,
                    $section->course->name . " (" . $section->course->code . ")",
                    $section->instructor->name ?: "ID: " . $section->instructor_id,
                    ($section->course->hours ?? 'N/A') . " Hrs",
                    $section->status,
                ];
            });

            $this->table(
                ['Time Slot', 'Course', 'Instructor', 'Duration', 'Status'],
                $tableData->toArray()
            );
            $this->newLine();
        }
    }
}
