<x-filament-panels::page>
    <div class="w-full">
        <style>
            .timetable-blur {
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
            }

            .timetable-card-glow:hover {
                box-shadow: 0 0 30px -5px var(--glow-color);
            }

            .timetable-grid-shadow {
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
            }

            .sticky-col {
                position: sticky;
                left: 0;
                z-index: 20;
            }

            .sticky-row {
                position: sticky;
                top: 0;
                z-index: 30;
            }
        </style>

        <div class="space-y-10 max-w-[1700px] mx-auto pb-10">
            {{-- High-End Header Card --}}
            <div
                class="relative overflow-hidden bg-white dark:bg-gray-900 rounded-[2rem] p-8 shadow-2xl border border-gray-100 dark:border-gray-800">
                {{-- Minimal Header --}}

                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-8">
                    <div class="space-y-2">
                        <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">
                            Master <span class="text-indigo-500">Schedule</span>
                        </h1>
                        <p class="text-gray-500 dark:text-gray-400 font-medium">Coordinate your university operations
                            with
                            precision and style.</p>
                    </div>
                    <div
                        class="bg-gray-50/80 dark:bg-gray-800/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-700/50 flex-grow max-w-2xl">
                        {{ $this->form }}
                    </div>
                </div>
            </div>

            {{-- Main Dashboard Grid --}}
            <div
                class="timetable-grid-shadow bg-white/70 dark:bg-gray-900/40 timetable-blur rounded-[2.5rem] border border-white dark:border-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <div class="inline-block min-w-full align-middle">
                        <div
                            class="grid grid-cols-[160px_repeat(6,minmax(280px,1fr))] border-b border-gray-100 dark:border-gray-800">
                            {{-- Timeline Header Cell --}}
                            <div
                                class="sticky-col sticky-row bg-gray-50/95 dark:bg-gray-900/95 p-6 border-r border-gray-100 dark:border-gray-800 flex items-center justify-center">
                                <div class="w-1.5 h-1.5 rounded-full bg-indigo-500 opacity-20"></div>
                            </div>

                            {{-- Time Slot Headers --}}
                            @foreach ($this->getTimeSlots() as $slot)
                                <div
                                    class="sticky-row bg-gray-50/95 dark:bg-gray-900/95 p-6 border-r border-gray-100 dark:border-gray-800 last:border-r-0 flex flex-col items-center">
                                    <span
                                        class="text-[10px] font-black uppercase tracking-widest text-indigo-500 dark:text-indigo-400 mb-2">Slot</span>
                                    <div
                                        class="px-4 py-2 bg-indigo-500/10 dark:bg-indigo-500/20 rounded-xl border border-indigo-500/20 text-indigo-700 dark:text-indigo-300 font-mono font-bold tracking-tight">
                                        {{ \Carbon\Carbon::parse($slot)->format('H:i') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Schedule Body --}}
                        @foreach ($this->getTimetableData() as $day => $sections)
                            <div
                                class="grid grid-cols-[160px_repeat(6,minmax(280px,1fr))] min-h-[160px] border-b border-gray-100 dark:border-gray-800 last:border-b-0 group/row">
                                {{-- Day Label --}}
                                <div
                                    class="sticky-col bg-gray-50/95 dark:bg-gray-900/95 p-6 border-r border-gray-100 dark:border-gray-800 flex flex-col justify-center transition-colors group-hover/row:bg-indigo-50/50 dark:group-hover/row:bg-indigo-500/5">
                                    <span
                                        class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ substr($day, 0, 3) }}</span>
                                    <span
                                        class="text-xl font-black text-gray-900 dark:text-white tracking-tight">{{ $day }}</span>
                                </div>

                                {{-- Cells --}}
                                @php $timeSlots = $this->getTimeSlots(); @endphp
                                @foreach ($timeSlots as $index => $slotStart)
                                    @php
                                        $slotEnd = $index < count($timeSlots) - 1 ? $timeSlots[$index + 1] : '18:00:00';
                                        $slotSections = $sections->filter(function ($section) use ($slotStart, $slotEnd) {
                                            return $section->start_time >= $slotStart && $section->start_time < $slotEnd;
                                        });
                                    @endphp
                                    <div
                                        class="p-4 border-r border-gray-100 dark:border-gray-800 last:border-r-0 relative group/cell min-h-[180px]">
                                        {{-- Cell Background on Hover --}}
                                        <div
                                            class="absolute inset-0 bg-indigo-500/[0.02] opacity-0 group-hover/cell:opacity-100 transition-opacity">
                                        </div>

                                        <div class="relative z-10 space-y-4">
                                            @foreach ($slotSections as $section)
                                                @php $style = $this->getCourseColor($section->course_id); @endphp
                                                <div class="timetable-card-glow group/card relative p-5 rounded-[2rem] border-2 transition-all duration-500 hover:-translate-y-2 hover:scale-[1.02] active:scale-95 shadow-xl {{ $style[0] }} {{ $style[1] }} {{ $style[4] }}"
                                                    style="--glow-color: hsla({{ (200 + $section->course_id * 40) % 360 }}, 70%, 50%, 0.3)">

                                                    {{-- Accent Bar --}}
                                                    <div
                                                        class="absolute top-6 left-0 w-1.5 h-10 rounded-r-full {{ $style[3] }} shadow-[0_0_15px_-2px_rgba(0,0,0,0.1)]">
                                                    </div>

                                                    <div class="space-y-4">
                                                        {{-- Header --}}
                                                        <div
                                                            class="p-0.5 px-1.5 bg-white/80 dark:bg-black/20 rounded-full border border-black/5 dark:border-white/10 text-[8px] font-black uppercase tracking-[0.2em] {{ $style[2] }}">
                                                            {{ $section->course->code }}
                                                        </div>
                                                    </div>

                                                    {{-- Body --}}
                                                    <div class="space-y-1 justify-center align-middle">
                                                        <div class="flex items-center gap-1.5 pt-1">
                                                            <div
                                                                class="w-1 h-1 rounded-full bg-gray-400 dark:bg-gray-600 opacity-40">
                                                            </div>
                                                            <span
                                                                class="text-[9px] font-bold text-gray-400 dark:text-gray-500 truncate tracking-tight">
                                                                {{ $section->instructor->user?->name ?: 'Instructor: ' . $section->instructor_id }}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <div
                                                        class="flex items-center justify-between pt-2 border-t border-black/5 dark:border-white/5">
                                                        <div class="font-mono text-[8px] font-black opacity-30 text-gray-500">
                                                            {{ \Carbon\Carbon::parse($section->start_time)->format('H:i') }}
                                                        </div>
                                                        <div
                                                            class="px-1.5 py-0.5 rounded-md bg-black/5 dark:bg-white/10 text-[8px] font-black uppercase tracking-widest opacity-30">
                                                            {{ $section->course->hours ?? 3 }}H
                                                        </div>
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

            <div class="px-6 py-3 bg-indigo-500/5 border border-indigo-500/10 rounded-2xl flex items-center gap-3">
                <div class="w-1 h-1 rounded-full bg-indigo-500"></div>
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Day Pairing Active</div>
            </div>
            <div class="px-6 py-3 bg-fuchsia-500/5 border border-fuchsia-500/10 rounded-2xl flex items-center gap-3">
                <div class="w-1 h-1 rounded-full bg-fuchsia-500"></div>
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Precision Timings</div>
            </div>
            <div class="px-6 py-3 bg-amber-500/5 border border-amber-500/10 rounded-2xl flex items-center gap-3">
                <div class="w-1 h-1 rounded-full bg-amber-500"></div>
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Dynamic Palette</div>
            </div>
        </div>
    </div>
</x-filament-panels::page>