<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Schedule Status
        </x-slot>

        <div class="space-y-4">
            <!-- Active Semester -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Active Semester
                        </h3>
                        @if($activeSemester)
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ $activeSemester->name }} - {{ ucfirst($activeSemester->type) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                Status: <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    Active
                                </span>
                            </p>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                No active semester
                            </p>
                        @endif
                    </div>
                    <div class="text-right">
                        <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Upcoming Semesters -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                    Upcoming Semesters
                </h3>
                @if($upcomingSemesters->count() > 0)
                    <div class="space-y-2">
                        @foreach($upcomingSemesters as $semester)
                            <div
                                class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $semester->name }} - {{ ucfirst($semester->type) }}
                                    </p>
                                </div>
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    Upcoming
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        No upcoming semesters scheduled
                    </p>
                @endif
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $completedCount }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Completed Semesters
                    </p>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $totalSemesters }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Total Semesters
                    </p>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>