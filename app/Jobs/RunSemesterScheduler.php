<?php

namespace App\Jobs;

use App\Models\Semester;
use App\Services\Scheduling\SchedulerService;
use App\Services\Scheduling\DTOs\SchedulingResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class RunSemesterScheduler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $semesterId,
        public array $options = []
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(SchedulerService $schedulerService): void
    {
        Log::info("RunSemesterScheduler job started", [
            'semester_id' => $this->semesterId,
            'options' => $this->options,
        ]);

        try {
            $semester = Semester::findOrFail($this->semesterId);

            // Generate the schedule
            $result = $schedulerService->generateSchedule($semester, $this->options);

            // Log the result
            $this->logResult($result);

            // Send notifications if enabled
            if ($this->options['notify_admin'] ?? true) {
                $this->notifyAdmin($semester, $result);
            }

            Log::info("RunSemesterScheduler job completed successfully", [
                'semester_id' => $this->semesterId,
                'sections_assigned' => $result->allocationResult->totalSectionsAssigned,
            ]);

        } catch (\Exception $e) {
            Log::error("RunSemesterScheduler job failed", [
                'semester_id' => $this->semesterId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Log the scheduling result.
     */
    protected function logResult(SchedulingResult $result): void
    {
        Log::info("Scheduling Result", [
            'sections_assigned' => $result->allocationResult->totalSectionsAssigned,
            'preferences_processed' => $result->allocationResult->totalPreferencesProcessed,
            'preferences_skipped' => $result->allocationResult->totalPreferencesSkipped,
            'underloaded_count' => $result->underloadedInstructors->count(),
            'is_valid' => $result->isValid,
            'requires_intervention' => $result->requiresAdminIntervention(),
        ]);

        if (!$result->isValid) {
            Log::warning("Schedule validation failed", [
                'error' => $result->errorMessage,
            ]);
        }

        if ($result->underloadedInstructors->isNotEmpty()) {
            Log::warning("Underloaded instructors detected", [
                'count' => $result->underloadedInstructors->count(),
                'instructors' => $result->underloadedInstructors->map(
                    fn($status) => $status->toArray()
                )->toArray(),
            ]);
        }
    }

    /**
     * Send notifications to admin users.
     */
    protected function notifyAdmin(Semester $semester, SchedulingResult $result): void
    {
        // TODO: Implement notification logic
        // This could use Laravel's notification system to email/notify admins
        // Example:
        // $admins = User::where('role', 'admin')->get();
        // Notification::send($admins, new SchedulingCompleted($semester, $result));

        Log::info("Admin notification would be sent here", [
            'semester_id' => $semester->id,
            'notifications' => $result->adminNotifications,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("RunSemesterScheduler job failed permanently", [
            'semester_id' => $this->semesterId,
            'exception' => $exception->getMessage(),
        ]);

        // TODO: Notify admin of failure
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['scheduling', 'semester:' . $this->semesterId];
    }
}
