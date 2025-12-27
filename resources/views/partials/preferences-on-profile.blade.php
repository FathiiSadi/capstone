@if(isset($preferenceData))
    <div class="card card-custom mt-4">
        <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-bold">Preferences Details</h5>
                    <p class="text-muted small mb-0">
                        {{ $preferenceData['semester']->name }} - {{ ucfirst($preferenceData['semester']->type) }}
                    </p>
                </div>
                <div class="text-end">
                    <span class="badge bg-light text-secondary border">
                        <i class="bi bi-clock me-1"></i>
                        Submitted: {{ \Carbon\Carbon::parse($preferenceData['submission_time'])->format('M d, Y h:i A') }}
                    </span>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <!-- Courses Column -->
                <div class="col-md-7">
                    <h6 class="text-uppercase text-secondary fw-bold fs-xs mb-3">Selected Courses</h6>
                    @if($preferenceData['courses']->isEmpty())
                        <div class="text-center py-4 bg-light rounded-3">
                            <i class="bi bi-journal-x text-muted opacity-50 display-6"></i>
                            <p class="text-muted small mt-2 mb-0">No courses selected</p>
                        </div>
                    @else
                        <div class="d-flex flex-column gap-3">
                            @foreach($preferenceData['courses'] as $course)
                                <div class="d-flex align-items-center bg-light p-3 rounded-3 border-start border-4 border-primary">
                                    <div class="rounded-circle bg-white text-primary p-2 me-3 shadow-sm d-flex align-items-center justify-content-center"
                                        style="width: 40px; height: 40px;">
                                        <i class="bi bi-journal-bookmark-fill"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold text-dark">{{ $course->code }}</h6>
                                        <small class="text-muted">{{ $course->name }}</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Time Slots Column -->
                <div class="col-md-5">
                    <h6 class="text-uppercase text-secondary fw-bold fs-xs mb-3">Time Preferences</h6>
                    @if($preferenceData['time_slots']->isEmpty())
                        <div class="text-center py-4 bg-light rounded-3">
                            <i class="bi bi-clock-history text-muted opacity-50 display-6"></i>
                            <p class="text-muted small mt-2 mb-0">No time preferences</p>
                        </div>
                    @else
                        <div class="d-flex flex-column gap-3">
                            @foreach($preferenceData['time_slots'] as $slot)
                                @php
                                    $days = $slot->days ?? $slot->preferred_days ?? 'Any Day';
                                    $time = $slot->time ?? $slot->preferred_time ?? '';
                                    // Compatibility check for concatenated format
                                    if ($days && str_contains($days, ' - ') && empty($time)) {
                                        $parts = explode(' - ', $days);
                                        $days = $parts[0];
                                        $time = $parts[1] ?? '';
                                    }
                                @endphp
                                <div class="bg-light p-3 rounded-3 border">
                                    <div class="d-flex align-items-start mb-2">
                                        <i class="bi bi-calendar-check text-primary mt-1 me-2"></i>
                                        <div>
                                            <span class="fs-xs fw-bold text-uppercase text-muted d-block"
                                                style="font-size: 0.75rem;">Days</span>
                                            <span class="fw-bold text-dark">{{ $days }}</span>
                                        </div>
                                    </div>
                                    @if($time)
                                        <div class="d-flex align-items-start">
                                            <i class="bi bi-clock text-info mt-1 me-2"></i>
                                            <div>
                                                <span class="fs-xs fw-bold text-uppercase text-muted d-block"
                                                    style="font-size: 0.75rem;">Time</span>
                                                <span class="text-dark">{{ $time }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-4 pt-3 border-top text-end">
                <a href="{{ route('instructor.preferences') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to List
                </a>
            </div>
        </div>
    </div>
@endif