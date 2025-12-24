<x-layout title="Instructor Home">
    <x-bar.topbar> </x-bar.topbar>

    <div class="d-flex">
        <x-bar.sidebar></x-bar.sidebar>

        <main class="flex-grow-1 main">
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @auth
                @if(in_array($user->role, ['instructor', 'admin']))
                    {{-- Instructor/Admin Dashboard --}}
                    <div class="page-title">Instructor Dashboard</div>

                    <!-- Active Semester Banner -->
                    @if($activeSemester)
                        <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                            <i class="bi bi-calendar-check fs-4 me-3"></i>
                            <div>
                                <strong>Active Semester:</strong> {{ $activeSemester->name }} - {{ ucfirst($activeSemester->type) }}
                                <div class="small">Make sure to submit your preferences for the upcoming semester if available.
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle fs-4 me-3"></i>
                            <div>
                                <strong>No Active Semester</strong>
                                <div class="small">There is currently no active semester. Please contact the administrator.</div>
                            </div>
                        </div>
                    @endif

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-4">
                            <div class="card card-custom p-3 h-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted">Total Courses</div>
                                        <div class="kpi">{{ $totalCourses }}</div>
                                    </div>
                                    <div class="fs-3 text-primary"><i class="bi bi-journal-bookmark"></i></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="card card-custom p-3 h-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted">Assigned Sections</div>
                                        <div class="kpi">{{ $totalSections > 0 ? $totalSections : '-' }}</div>
                                    </div>
                                    <div class="fs-3 text-success"><i class="bi bi-people"></i></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="card card-custom p-3 h-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted">Current Load (C.H.)</div>
                                        <div class="kpi">{{ $currentLoad }}</div>
                                    </div>
                                    <div class="fs-3 text-warning"><i class="bi bi-clock-history"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="card card-custom mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5>Notification Center</h5>
                            <button class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-gear"></i> Settings
                            </button>
                        </div>

                        <div class="mt-3">
                            <ul class="list-group">
                                @forelse($notifications as $notification)
                                    <li class="list-group-item d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="fw-bold">{{ $notification->data['title'] ?? 'Notification' }}</div>
                                            {{ $notification->data['message'] ?? '' }}
                                            <div class="small text-muted">{{ $notification->created_at->diffForHumans() }}</div>
                                        </div>
                                        @if(!$notification->read_at)
                                            <span class="badge bg-primary rounded-pill">New</span>
                                        @endif
                                    </li>
                                @empty
                                    <li class="list-group-item text-center text-muted py-3">
                                        <i class="bi bi-bell-slash fs-4 d-block mb-2"></i>
                                        No new notifications
                                    </li>
                                @endforelse
                            </ul>
                        </div>
                    </div>



                    <div class="card card-custom mb-3">
                        <h5>My Courses Schedule</h5>

                        <div class="table-responsive mt-3">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th>Section</th>
                                        <th>Day(s)</th>
                                        <th>Time</th>
                                        <th>Room</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($assignedSections as $section)
                                        <tr>
                                            <td>
                                                <div class="fw-bold">{{ $section->course->code }}</div>
                                                <div class="small text-muted">{{ $section->course->name }}</div>
                                            </td>
                                            <td>{{ $section->id }}</td> {{-- Or section number if you have a column for it --}}
                                            <td>{{ $section->days }}</td>
                                            <td>{{ \Carbon\Carbon::parse($section->start_time)->format('H:i') }} -
                                                {{ \Carbon\Carbon::parse($section->end_time)->format('H:i') }}</td>
                                            <td>{{ $section->room ?? 'TBA' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                <i class="bi bi-calendar-x fs-4 d-block mb-2"></i>
                                                No sections assigned for this semester yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Recent Preferences -->
                    <div class="card card-custom">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="mb-0">Recent Preferences Submissions</h5>
                            <div>
                                <a href="{{ route('instructor.preferences') }}" class="btn btn-primary-custom btn-sm">
                                    <i class="bi bi-gear me-1"></i>
                                    <span class="d-none d-sm-inline">Manage </span>Preferences
                                </a>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>Semester</th>
                                            <th>Submitted On</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentPreferences as $pref)
                                            <tr>
                                                <td>{{ $pref->semester->name }} - {{ ucfirst($pref->semester->type) }}</td>
                                                <td>{{ \Carbon\Carbon::parse($pref->submission_time)->format('Y-m-d H:i') }}</td>
                                                <td><span class="badge bg-success">Submitted</span></td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted py-3">
                                                    No preferences submitted yet.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Non-Instructor/Admin Users --}}
                    <div class="page-title">Access Denied</div>
                    <div class="card card-custom">
                        <div class="text-center py-5">
                            <i class="bi bi-shield-exclamation text-danger" style="font-size: 4rem;"></i>
                            <h3 class="mt-4">Access Restricted</h3>
                            <p class="text-muted mb-4">
                                You must be an instructor or administrator to access this portal.
                            </p>
                            <p class="text-muted">
                                Your current role: <strong>{{ ucfirst($user->role ?? 'Unknown') }}</strong>
                            </p>
                            <div class="mt-4">
                                <a href="/logout" class="btn btn-outline-secondary">Logout</a>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                {{-- Guest Users --}}
                <div class="page-title">Welcome to HTU Portal</div>
                <div class="card card-custom">
                    <div class="text-center py-5">
                        <i class="bi bi-person-circle text-primary" style="font-size: 4rem;"></i>
                        <h3 class="mt-4">Welcome!</h3>
                        <p class="text-muted mb-4">
                            Please login to access the instructor portal.
                        </p>
                        <div class="mt-4">
                            <a href="/auth/login" class="btn btn-primary-custom">Login</a>
                        </div>
                    </div>
                </div>
            @endauth
        </main>
    </div>
</x-layout>
