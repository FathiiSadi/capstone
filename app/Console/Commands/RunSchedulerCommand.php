<?php

namespace App\Console\Commands;

use App\Jobs\RunSemesterScheduler;
use App\Models\Semester;
use App\Services\Scheduling\SchedulerService;
use Illuminate\Console\Command;

class RunSchedulerCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'schedule:run-semester 
                            {semester_id : The ID of the semester to schedule}
                            {--async : Run the scheduler asynchronously via job queue}
                            {--clear : Clear existing schedule before generation}
                            {--no-least-chosen : Disable least-chosen course assignment}
                            {--strict : Enable strict mode (fail on any constraint violation)}';

    /**
     * The console command description.
     */
    protected $description = 'Run the FIFO-based course scheduling algorithm for a semester';

    /**
     * Execute the console command.
     */
    public function handle(SchedulerService $schedulerService): int
    {
        $semesterId = $this->argument('semester_id');

        // Validate semester exists
        $semester = Semester::find($semesterId);
        if (!$semester) {
            $this->error("Semester with ID {$semesterId} not found.");
            return Command::FAILURE;
        }

        $this->info("Starting schedule generation for: {$semester->name}");
        $this->newLine();

        // Build options
        $options = [
            'enable_least_chosen' => !$this->option('no-least-chosen'),
            'clear_existing' => $this->option('clear'),
            'notify_admin' => true,
            'strict_mode' => $this->option('strict'),
        ];

        // Display options
        $this->table(
            ['Option', 'Value'],
            [
                ['Clear Existing', $options['clear_existing'] ? 'Yes' : 'No'],
                ['Least-Chosen Assignment', $options['enable_least_chosen'] ? 'Enabled' : 'Disabled'],
                ['Strict Mode', $options['strict_mode'] ? 'Enabled' : 'Disabled'],
                ['Execution', $this->option('async') ? 'Async (Queued)' : 'Synchronous'],
            ]
        );
        $this->newLine();

        // Confirm before proceeding
        if (!$this->confirm('Do you want to proceed?', true)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        try {
            if ($this->option('async')) {
                // Run asynchronously
                RunSemesterScheduler::dispatch($semesterId, $options);
                $this->info('✓ Scheduler job dispatched to queue.');
                $this->info('  Monitor logs or job queue for progress.');
            } else {
                // Run synchronously
                $this->info('Running scheduler...');
                $progressBar = $this->output->createProgressBar();
                $progressBar->start();

                $result = $schedulerService->generateSchedule($semester, $options);

                $progressBar->finish();
                $this->newLine(2);

                // Display results
                $this->displayResults($result);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Schedule generation failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Display scheduling results.
     */
    protected function displayResults($result): void
    {
        $this->info('=== Scheduling Results ===');
        $this->newLine();

        // Allocation summary
        $this->table(
            ['Metric', 'Count'],
            [
                ['Sections Assigned', $result->allocationResult->totalSectionsAssigned],
                ['Preferences Processed', $result->allocationResult->totalPreferencesProcessed],
                ['Preferences Skipped', $result->allocationResult->totalPreferencesSkipped],
                ['Unassigned Courses', $result->allocationResult->unassignedCourses->count()],
                ['Underloaded Instructors', $result->underloadedInstructors->count()],
            ]
        );
        $this->newLine();

        // Status
        if ($result->isValid) {
            $this->info('✓ Schedule is valid');
        } else {
            $this->warn('⚠ Schedule validation failed: ' . ($result->errorMessage ?? 'Unknown error'));
        }

        // Underloaded instructors
        if ($result->underloadedInstructors->isNotEmpty()) {
            $this->warn('⚠ Underloaded Instructors:');
            $this->table(
                ['Instructor', 'Assigned', 'Required', 'Short By'],
                $result->underloadedInstructors->map(function ($status) {
                    return [
                        $status->instructor->name,
                        $status->totalAssignedCredits,
                        $status->minimumRequiredCredits,
                        $status->minimumRequiredCredits - $status->totalAssignedCredits,
                    ];
                })->toArray()
            );
        }

        // Admin notifications
        if (!empty($result->adminNotifications)) {
            $this->newLine();
            $this->info('Admin Notifications:');
            foreach ($result->adminNotifications as $notification) {
                $icon = match ($notification['type']) {
                    'warning' => '⚠',
                    'error' => '✗',
                    default => 'ℹ',
                };
                $this->line("  {$icon} {$notification['title']}: {$notification['message']}");
            }
        }

        $this->newLine();

        if ($result->requiresAdminIntervention()) {
            $this->warn('⚠ Admin intervention required!');
        } else {
            $this->info('✓ Scheduling completed successfully!');
        }
    }
}

