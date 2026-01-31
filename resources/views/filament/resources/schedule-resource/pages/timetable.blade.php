    <x-filament-panels::page>

        {{-- Filter Container --}}
        <div style="background: inherit; padding: 20px; margin-bottom: 20px; border: 2px solid rgba(200, 200, 200, 0.4); border-radius: 8px;">
            {{ $this->form }}
        </div>

        {{-- Main Table Container --}}
        <div style="background: inherit; padding: 20px; border-radius: 8px; overflow-x: auto; border: 2px solid rgba(200, 200, 200, 0.4);">
            <table style="width: 100%; border-collapse: separate; border-spacing: 0; min-width: 1400px;">
                <thead>
                    <tr>
                        {{-- Day Column Header --}}
                        <th style="
                            padding: 12px; 
                            width: 100px; 
                            background: inherit;
                            border-bottom: 2px solid rgba(200, 200, 200, 0.4); 
                            position: sticky; top: 0; z-index: 10; text-align: left;">
                            Day
                        </th>
                        
                        {{-- Time Slot Headers --}}
                        @foreach($this->getTimeSlots() as $slot)
                            @php
                                $startTime = \Carbon\Carbon::parse($slot);
                                $endTime = $startTime->copy()->addMinutes(90);
                            @endphp
                            <th style="
                                padding: 12px; 
                                min-width: 260px; 
                                background: inherit; 
                                border-bottom: 2px solid rgba(200, 200, 200, 0.4); 
                                border-left: 2px solid rgba(200, 200, 200, 0.4); 
                                text-align: center;">
                                <div style="font-weight: bold; font-size: 14px; opacity: 0.8;">
                                    {{ $startTime->format('H:i') }} - {{ $endTime->format('H:i') }}
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                
                <tbody>
                    @foreach($this->getTimetableData() as $day => $timeSlots)
                        <tr>
                            {{-- Day Name Cell --}}
                            <td style="
                                padding: 15px; 
                                font-weight: bold; 
                                background: inherit;
                                border-bottom: 2px solid rgba(200, 200, 200, 0.4); 
                                vertical-align: top;
                                opacity: 0.9;">
                                {{ substr($day, 0, 3) }}
                            </td>
                            
                            {{-- Slot Content Cells --}}
                            @foreach($timeSlots as $slotTime => $slotSections)
                                <td style="
                                    padding: 5px; 
                                    border-bottom: 2px solid rgba(200, 200, 200, 0.4); 
                                    border-left: 2px solid rgba(200, 200, 200, 0.4); 
                                    vertical-align: top; 
                                    height: 180px;">
                                    
                                    <div style="display: flex; flex-direction: column; gap: 4px; height: 100%; overflow-y: auto; padding-right: 4px;">
                                        
                                        @if($slotSections->isNotEmpty())
                                            @foreach($slotSections as $section)
                                                @php $colors = $this->getCourseColor($section->course_id); @endphp
                                                
                                                {{-- Compact Card --}}
                                                <div style="
                                                    background: {{ $colors['bg'] }}; 
                                                    border-left: 3px solid {{ $colors['text'] }}; 
                                                    border-radius: 4px; 
                                                    padding: 6px 8px; 
                                                    font-size: 11px;
                                                    box-shadow: 0 1px 1px rgba(0,0,0,0.05);
                                                    flex-shrink: 0;
                                                ">
                                                    {{-- Top Row: Name & Time --}}
                                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2px;">
                                                        <span style="font-weight: bold; color: {{ $colors['text'] }}; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 65%;" title="{{ $section->course->name }}">
                                                            {{ $section->course->name }}
                                                        </span>
                                                        <span style="font-size: 10px; opacity: 0.8; color: {{ $colors['text'] }}; white-space: nowrap;">
                                                            {{ substr($section->start_time, 0, 5) }}-{{ substr($section->end_time, 0, 5) }}
                                                        </span>
                                                    </div>

                                                    {{-- Bottom Row: Code & Instructor --}}
                                                    <div style="display: flex; justify-content: space-between; align-items: center; color: {{ $colors['text'] }}; opacity: 0.9;">
                                                        <span style="font-size: 10px; font-weight: 500;">
                                                            {{ $section->course->code }} (S{{ $section->section_number }})
                                                        </span>
                                                        <div style="display: flex; align-items: center; gap: 3px; max-width: 45%;" title="{{ $section->instructor?->user?->name }}">
                                                            <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 10px;">
                                                                👤 {{ $section->instructor?->user?->name ?? 'TBA' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            {{-- Empty State --}}
                                            <div style="display: flex; align-items: center; justify-content: center; height: 100%; opacity: 0.1; font-size: 20px;">
                                                &bull;
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament-panels::page>