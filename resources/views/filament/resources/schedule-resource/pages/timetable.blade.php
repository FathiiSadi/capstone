<x-filament-panels::page>
    <div class="w-full">
        <style>
            .schedule-gradient {
                background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(168, 85, 247, 0.05) 100%);
            }

            .glass-effect {
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                background: rgba(255, 255, 255, 0.8);
            }

            .dark .glass-effect {
                background: rgba(17, 24, 39, 0.8);
            }

            .card-hover {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .card-hover:hover {
                transform: translateY(-4px);
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            }

            .time-grid {
                display: grid;
                grid-template-columns: 140px repeat(auto-fit, minmax(240px, 1fr));
            }

            @media (min-width: 1024px) {
                .time-grid {
                    grid-template-columns: 140px repeat(6, minmax(240px, 1fr));
                }
            }
        </style>

        <div class="max-w-[1800px] mx-auto space-y-6 pb-8">
            {{-- Header Section --}}
            <div class="glass-effect rounded-3xl p-6 shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    {{-- Title Section --}}
                    <div class="space-y-1">
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                            Master Schedule
                        </h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            University course timetable overview
                        </p>
                    </div>

                    {{-- Filters --}}
                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-2xl border border-gray-200 dark:border-gray-700 min-w-[320px]">
                        {{ $this->form }}
                    </div>
                </div>
            </div>

            {{-- Main Schedule Grid --}}
            <div class="glass-effect rounded-3xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <div class="inline-block min-w-full">
                        {{-- Header Row --}}
                        <div class="time-grid bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                            {{-- Empty Corner Cell --}}
                            <div class="sticky left-0 z-20 bg-gray-50 dark:bg-gray-800 p-4 border-r border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>

                            {{-- Time Slot Headers --}}
                            @foreach ($this->getTimeSlots() as $slot)
                                <div class="p-4 border-r border-gray-200 dark:border-gray-700 last:border-r-0 text-center">
                                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">
                                        Time Slot
                                    </div>
                                    <div class="inline-flex items-center px-3 py-1.5 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                                        <span class="font-mono text-sm font-bold text-indigo-700 dark:text-indigo-300">
                                            {{ \Carbon\Carbon::parse($slot)->format('H:i') }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Schedule Rows --}}
                        @foreach ($this->getTimetableData() as $day => $sections)
                            <div class="time-grid border-b border-gray-200 dark:border-gray-700 last:border-b-0 hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                                {{-- Day Label --}}
                                <div class="sticky left-0 z-20 bg-white dark:bg-gray-900 p-4 border-r border-gray-200 dark:border-gray-700">
                                    <div class="flex flex-col justify-center h-full">
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ substr($day, 0, 3) }}
                                        </span>

                                    </div>
                                </div>

                                {{-- Time Slot Cells --}}
                                @php $timeSlots = $this->getTimeSlots(); @endphp
                                @foreach ($timeSlots as $index => $slotStart)
                                    @php
                                        $slotEnd = $index < count($timeSlots) - 1 ? $timeSlots[$index + 1] : '18:00:00';
                                        $slotSections = $sections->filter(function ($section) use ($slotStart, $slotEnd) {
                                            return $section->start_time >= $slotStart && $section->start_time < $slotEnd;
                                        });
                                    @endphp

                                    <div class="p-3 border-r border-gray-200 dark:border-gray-700 last:border-r-0 min-h-[140px]">
                                        <div class="space-y-2">
                                            @foreach ($slotSections as $section)
                                                @php $style = $this->getCourseColor($section->course_id); @endphp

                                                <div class="card-hover rounded-2xl p-4 {{ $style[0] }} {{ $style[1] }} border-2 {{ $style[4] }} shadow-md">
                                                    {{-- Course Code Badge --}}
                                                    <div class="inline-flex items-center px-2.5 py-1 rounded-lg bg-white/90 dark:bg-black/20 border border-gray-200 dark:border-gray-700 mb-3">
                                                        <span class="text-xs font-bold {{ $style[2] }} uppercase tracking-wide">
                                                            {{ $section->course->code }}
                                                        </span>
                                                    </div>

                                                    {{-- Instructor Info --}}
                                                    <div class="flex items-center gap-2 mb-3">
                                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                        </svg>
                                                        <span class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                                            {{ $section->instructor->user?->name ?: 'TBA' }}
                                                        </span>
                                                    </div>

                                                    {{-- Footer Info --}}
                                                    <div class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                                                        <span class="text-xs font-mono text-gray-500 dark:text-gray-400">
                                                            {{ \Carbon\Carbon::parse($section->start_time)->format('H:i') }}
                                                        </span>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-800 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                                            {{ $section->course->hours ?? 3 }}h
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
