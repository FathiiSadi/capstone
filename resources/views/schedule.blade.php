<x-layout title="{{ $viewAll ? 'Full University Schedule' : 'My Teaching Schedule' }}">
    <x-bar.topbar></x-bar.topbar>

    <div class="d-flex">
        <x-bar.sidebar></x-bar.sidebar>

        <main class="flex-grow-1 main bg-light">
            <style>
                :root {
                    --premium-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
                    --glass-bg: rgba(255, 255, 255, 0.7);
                    --glass-border: rgba(255, 255, 255, 0.3);
                }

                .schedule-hero {
                    background: var(--premium-gradient);
                    color: white;
                    padding: 2.5rem 2rem;
                    border-radius: 1.5rem;
                    margin-bottom: 2rem;
                    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                    position: relative;
                    overflow: hidden;
                }

                .schedule-hero::after {
                    content: '';
                    position: absolute;
                    top: -50%;
                    right: -10%;
                    width: 300px;
                    height: 300px;
                    background: radial-gradient(circle, rgba(14, 165, 233, 0.2) 0%, transparent 70%);
                    z-index: 1;
                }

                .stat-card {
                    background: var(--glass-bg);
                    backdrop-filter: blur(10px);
                    border: 1px solid var(--glass-border);
                    border-radius: 1.25rem;
                    padding: 1.5rem;
                    transition: all 0.3s ease;
                    height: 100%;
                }

                .stat-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.05);
                }

                .stat-icon {
                    width: 48px;
                    height: 48px;
                    border-radius: 12px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-bottom: 1rem;
                    background: var(--brand-accent);
                    color: white;
                    font-size: 1.5rem;
                }

                .schedule-table-container {
                    background: white;
                    border-radius: 1.5rem;
                    padding: 2rem;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
                    border: 1px solid #f1f5f9;
                }

                .table thead th {
                    background: #f8fafc;
                    color: #64748b;
                    font-weight: 600;
                    text-transform: uppercase;
                    font-size: 0.75rem;
                    letter-spacing: 0.05em;
                    border-bottom: 2px solid #f1f5f9;
                    padding: 1.25rem 1rem;
                }

                .table tbody td {
                    padding: 1.25rem 1rem;
                    border-bottom: 1px solid #f8fafc;
                    color: #334155;
                    font-size: 0.95rem;
                }

                .table tbody tr:hover {
                    background-color: #f8fafc;
                }

                .instructor-badge {
                    background: #e0f2fe;
                    color: #0369a1;
                    padding: 0.25rem 0.75rem;
                    border-radius: 2rem;
                    font-size: 0.85rem;
                    font-weight: 500;
                }

                .days-badge {
                    background: #f1f5f9;
                    color: #475569;
                    padding: 0.25rem 0.75rem;
                    border-radius: 0.5rem;
                    font-size: 0.85rem;
                    font-weight: 500;
                    margin-right: 0.25rem;
                }

                .time-text {
                    color: #0ea5e9;
                    font-weight: 600;
                }

                /* Premium Toggle Link Styles */
                .visibility-toggle {
                    background: rgba(255, 255, 255, 0.1);
                    padding: 0.5rem;
                    border-radius: 0.75rem;
                    display: inline-flex;
                    gap: 0.25rem;
                }

                .visibility-toggle .nav-link {
                    padding: 0.4rem 1rem;
                    border-radius: 0.5rem;
                    color: white;
                    font-size: 0.9rem;
                    font-weight: 600;
                    transition: all 0.2s;
                }

                .visibility-toggle .nav-link.active {
                    background: white;
                    color: var(--brand-primary);
                    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
                }

                .visibility-toggle .nav-link:not(.active):hover {
                    background: rgba(255, 255, 255, 0.2);
                }
            </style>

            <div class="schedule-hero">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 position-relative" style="z-index: 2;">
                    <div>
                        <span class="badge bg-info bg-opacity-25 text-info mb-2 px-3 py-2 text-uppercase fw-bold" style="letter-spacing: 0.1em; font-size: 0.7rem;">Dashboard</span>
                        <h1 class="h2 fw-bold mb-1">
                            @if($viewAll)
                                University Master Schedule
                            @else
                                My Teaching Schedule
                            @endif
                        </h1>
                        <p class="mb-0 opacity-75">
                            Showing assignments for <strong>{{ $selectedSemester?->name ?? 'Current Semester' }}</strong>
                        </p>
                    </div>

                    <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center">
                        @if($isAdmin)
                            <div class="visibility-toggle">
                                <a href="{{ route('schedule.index', ['semester_id' => $selectedSemester?->id, 'all' => 0]) }}"
                                   class="nav-link {{ !$viewAll ? 'active' : '' }}">
                                    <i class="bi bi-person-check me-1"></i> My Schedule
                                </a>
                                <a href="{{ route('schedule.index', ['semester_id' => $selectedSemester?->id, 'all' => 1]) }}"
                                   class="nav-link {{ $viewAll ? 'active' : '' }}">
                                    <i class="bi bi-grid-3x3-gap me-1"></i> Full Schedule
                                </a>
                            </div>
                        @endif

                        <form method="GET" class="ms-md-2">
                            @if($viewAll) <input type="hidden" name="all" value="1"> @endif
                            <select name="semester_id" class="form-select border-0 shadow-sm" style="min-width: 180px; height: 45px; border-radius: 0.75rem;" onchange="this.form.submit()">
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester->id }}"
                                        {{ $selectedSemester && $selectedSemester->id === $semester->id ? 'selected' : '' }}>
                                        {{ $semester->name }} ({{ $semester->type }})
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary">
                            <i class="bi bi-journal-bookmark-fill"></i>
                        </div>
                        <p class="text-muted small text-uppercase fw-bold mb-1" style="letter-spacing: 0.05em;">Total Courses</p>
                        <h2 class="mb-0 fw-bold">{{ $sections->pluck('course_id')->unique()->count() }}</h2>
                        <div class="mt-2 small text-primary">
                            Across {{ $sections->count() }} sections
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-success">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <p class="text-muted small text-uppercase fw-bold mb-1" style="letter-spacing: 0.05em;">Weekly Hours</p>
                        @php
                            $totalHours = $sections->sum(function($s) {
                                return $s->course->hours ?? 3;
                            });
                        @endphp
                        <h2 class="mb-0 fw-bold">{{ $totalHours }}</h2>
                        <div class="mt-2 small text-success">
                            Teaching workload
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                        <p class="text-muted small text-uppercase fw-bold mb-1" style="letter-spacing: 0.05em;">Locations</p>
                        <h2 class="mb-0 fw-bold">{{ $sections->pluck('room_id')->filter()->unique()->count() ?: 0 }}</h2>
                        <div class="mt-2 small text-warning">
                             Assigned rooms
                        </div>
                    </div>
                </div>
            </div>

            <div class="schedule-table-container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Scheduled Lectures</h5>
                    <button class="btn btn-sm btn-outline-secondary d-none d-md-flex align-items-center gap-2" onclick="window.print()">
                        <i class="bi bi-printer"></i> Export PDF
                    </button>
                </div>

                @if($sections->isEmpty())
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="bi bi-calendar-x text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
                        </div>
                        <h4 class="text-muted fw-bold">No schedule found</h4>
                        <p class="text-muted mx-auto" style="max-width: 400px;">
                            There are currently no sections assigned for the selected semester. Please check back later or contact the admin.
                        </p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Course Identity</th>
                                    @if($viewAll) <th>Instructor</th> @endif
                                    <th>Weekly Days</th>
                                    <th>Time Duration</th>
                                    <th>Venue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sections as $index => $section)
                                    @php
                                        $days = $section->days ?: [];
                                        $start = $section->start_time ? \Carbon\Carbon::parse($section->start_time)->format('h:i A') : 'TBD';
                                        $end = $section->end_time ? \Carbon\Carbon::parse($section->end_time)->format('h:i A') : 'TBD';
                                    @endphp
                                    <tr>
                                        <td class="text-muted small fw-bold">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="bg-light p-2 rounded text-primary fw-bold small" style="width: 50px; text-align: center;">
                                                    {{ $section->course?->code }}
                                                </div>
                                                <div>
                                                    <div class="fw-bold">{{ $section->course?->name }}</div>
                                                    <small class="text-muted">{{ $section->section_number }} • {{ $section->course?->department?->name }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        @if($viewAll)
                                            <td>
                                                <span class="instructor-badge">
                                                    <i class="bi bi-person-fill me-1"></i>
                                                    {{ $section->instructor?->user?->name ?? 'Pending' }}
                                                </span>
                                            </td>
                                        @endif
                                        <td>
                                            @forelse((array)$days as $day)
                                                <span class="days-badge">{{ $day }}</span>
                                            @empty
                                                <span class="text-muted italic">TBD</span>
                                            @endforelse
                                        </td>
                                        <td>
                                            <div class="time-text">
                                                <i class="bi bi-clock me-1"></i> {{ $start }} - {{ $end }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                                    <i class="bi bi-door-open text-muted small"></i>
                                                </div>
                                                <span>{{ $section->room?->name ?? 'TBD' }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            @if($viewAll && $report['underloaded']->isNotEmpty())
                <div class="alert alert-warning border-0 shadow-sm mt-5 rounded-4 p-4">
                    <div class="d-flex gap-3">
                        <i class="bi bi-exclamation-triangle-fill h4 mb-0"></i>
                        <div>
                            <h5 class="fw-bold">Faculty Workload Notice</h5>
                            <p class="mb-0 opacity-75">There are {{ $report['underloaded']->count() }} instructors currently underloaded. Consider assigning them more sections.</p>
                        </div>
                    </div>
                </div>
            @endif

        </main>
    </div>
</x-layout>
