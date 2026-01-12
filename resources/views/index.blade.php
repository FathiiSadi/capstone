<x-layout title="Instructor Home">
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.5);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        body {
            background: #f0f2f5;
            background-image:
                radial-gradient(at 0% 0%, hsla(253,16%,7%,1) 0, transparent 50%),
                radial-gradient(at 50% 0%, hsla(225,39%,30%,1) 0, transparent 50%),
                radial-gradient(at 100% 0%, hsla(339,49%,30%,1) 0, transparent 50%);
            background-attachment: fixed;
            font-family: 'Open Sans', sans-serif;
            color: #333;
        }

        .main {
            padding: 2rem;
        }

        /* Glassmorphism Classes */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            box-shadow: var(--glass-shadow);
            padding: 2rem;
            margin-bottom: 2rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.25);
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 1.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        /* Stats Cards */
        .stat-card {
            color: white;
            border: none;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            padding: 1.5rem;
            height: 100%;
        }

        .bg-purple { background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%); }
        .bg-blue { background: linear-gradient(135deg, #5b86e5 0%, #36d1dc 100%); }
        .bg-green { background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%); }

        .stat-icon {
            font-size: 3rem;
            opacity: 0.3;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
        }

        /* Modern Tables */
        .table-modern {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
        }

        .table-modern thead th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            color: #4a5568;
            border: none;
            padding: 1rem;
        }

        .table-modern tbody tr {
            background: rgba(255, 255, 255, 0.6);
            transition: all 0.2s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }

        .table-modern tbody tr:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: scale(1.01);
        }

        .table-modern td {
            border: none;
            padding: 1rem;
        }

        .table-modern td:first-child { border-radius: 12px 0 0 12px; }
        .table-modern td:last-child { border-radius: 0 12px 12px 0; }

        /* Buttons */
        .btn-glass {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.4);
            color: #4a5568;
            backdrop-filter: blur(4px);
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-glass:hover {
            background: rgba(255, 255, 255, 0.9);
            color: #667eea;
            transform: translateY(-2px);
        }

        /* Utilities */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
        }
    </style>

    <x-bar.topbar> </x-bar.topbar>

    <div class="d-flex">
        <x-bar.sidebar></x-bar.sidebar>

        <main class="flex-grow-1 main">
            @if(session('error'))
                <div class="alert alert-danger border-0 rounded-3 shadow-sm mb-4">
                    <i class="bi bi-exclamation-circle-fill me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close float-end" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @auth
                @if(in_array($user->role, ['instructor', 'admin']))

                    <div class="d-flex justify-content-between align-items-end mb-5">
                        <div class="page-title mb-0">Hello, {{ explode(' ', $user->name)[0] }}!</div>
                        <div class="text-white text-opacity-75">
                            {{ now()->format('l - F j, Y') }}
                        </div>
                    </div>

                    <!-- Active Semester Banner -->
                    @if($activeSemester)
                        <div class="glass-card d-flex align-items-center mb-5 py-3" style="background: rgba(255,255,255,0.9); border-left: 5px solid #667eea;">
                            <i class="bi bi-calendar-check fs-2 me-4 text-primary"></i>
                            <div>
                                <h5 class="mb-1 fw-bold text-dark">Active Semester: {{ $activeSemester->name }}</h5>
                                <p class="mb-0 text-muted small">{{ ucfirst($activeSemester->type) }} Session</p>
                            </div>
                        </div>
                    @else
                        <div class="glass-card mb-5 py-3 border-start border-warning border-5">
                            <i class="bi bi-exclamation-triangle fs-4 me-2 text-warning"></i>
                            <strong>No Active Semester:</strong> System is currently paused.
                        </div>
                    @endif

                    <!-- KPI Cards-->
                    <div class="row g-4 mb-5">
                        <div class="col-12 col-md-4">
                            <div class="stat-card bg-purple shadow-lg">
                                <div class="stat-icon"><i class="fas fa-book-open"></i></div>
                                <h6 class="text-uppercase text-white-50">Total Courses</h6>
                                <div class="stat-value">{{ $totalCourses }}</div>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="stat-card bg-blue shadow-lg">
                                <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                                <h6 class="text-uppercase text-white-50">Assigned Sections</h6>
                                <div class="stat-value">{{ $totalSections > 0 ? $totalSections : '0' }}</div>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="stat-card bg-green shadow-lg">
                                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                                <h6 class="text-uppercase text-white-50">Credit Hours</h6>
                                <div class="stat-value">{{ $currentLoad }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Left Column: Schedule -->
                        <div class="col-lg-8 mb-4">
                            <div class="glass-card h-100">
                                <div class="section-header">
                                    <h5 class="section-title"><i class="bi bi-calendar-week me-2"></i>My Schedule</h5>
                                </div>

                                <div class="table-responsive">
                                    <table class="table-modern">
                                        <thead>
                                            <tr>
                                                <th>Course</th>
                                                <th>Timing</th>
                                                <th>Room</th>
                                                <th>Days</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($assignedSections as $section)
                                                <tr>
                                                    <td>
                                                        <div class="fw-bold text-dark">{{ $section->course->code }}</div>
                                                        <div class="small text-muted">{{ Str::limit($section->course->name, 25) }}</div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border">
                                                            {{ \Carbon\Carbon::parse($section->start_time)->format('H:i') }} -
                                                            {{ \Carbon\Carbon::parse($section->end_time)->format('H:i') }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($section->room)
                                                            <span class="badge bg-info bg-opacity-10 text-info">
                                                                <i class="bi bi-geo-alt-fill me-1"></i>{{ $section->room->name }}
                                                            </span>
                                                        @else
                                                            <span class="text-muted fst-italic">TBA</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="small text-secondary">{{ is_array($section->days) ? implode(', ', $section->days) : $section->days }}</span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center py-5">
                                                        <img src="https://cdni.iconscout.com/illustration/premium/thumb/empty-state-2130362-1800926.png" alt="Empty" style="width: 150px; opacity: 0.6;">
                                                        <p class="text-muted mt-3">No classes scheduled yet.</p>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Notifications & Preferences -->
                        <div class="col-lg-4 mb-4">
                            <!-- Notifications -->
                            <div class="glass-card mb-4 p-4">
                                <div class="section-header mb-3">
                                    <h5 class="section-title fs-5">Notifications</h5>
                                    <span class="badge bg-danger rounded-pill">{{ count($notifications) }}</span>
                                </div>
                                <div class="list-group list-group-flush bg-transparent">
                                    @forelse($notifications->take(3) as $notification)
                                        <div class="list-group-item bg-transparent border-bottom px-0 py-3">
                                            <div class="d-flex w-100 justify-content-between mb-1">
                                                <strong class="text-dark">{{ $notification->data['title'] ?? 'Notice' }}</strong>
                                                <small class="text-muted">{{ $notification->created_at->shortAbsoluteDiffForHumans() }}</small>
                                            </div>
                                            <p class="mb-0 small text-secondary lh-sm">{{ Str::limit($notification->data['message'] ?? '', 60) }}</p>
                                        </div>
                                    @empty
                                        <div class="text-center text-muted py-3 small">No new notifications</div>
                                    @endforelse
                                </div>
                            </div>

                            <!-- Preferences -->
                            <div class="glass-card p-4 bg-white bg-opacity-50">
                                <div class="section-header mb-3">
                                    <h5 class="section-title fs-5">Preferences</h5>
                                    <a href="{{ route('instructor.preferences') }}" class="btn-glass btn-sm text-decoration-none">
                                        Manage
                                    </a>
                                </div>
                                @if($recentPreferences->count() > 0)
                                    <div class="d-flex align-items-center justify-content-between p-3 bg-white rounded-3 shadow-sm">
                                        <div>
                                            <div class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i> Submitted</div>
                                            <div class="small text-muted">{{ $recentPreferences->first()->semester->name }}</div>
                                        </div>
                                        <div class="small text-end text-muted">
                                            {{ $recentPreferences->first()->submission_time->format('M d') }}
                                        </div>
                                    </div>
                                @else
                                    <div class="text-center py-3">
                                        <a href="{{ route('instructor.preferences') }}" class="btn btn-primary w-100 shadow-sm rounded-pill">
                                            Submit Preferences
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <div class="glass-card text-center py-5">
                        <i class="bi bi-shield-lock text-secondary display-1 mb-4"></i>
                        <h2 class="text-dark">Access Restricted</h2>
                        <p class="text-muted mb-4">This portal is for instructors and administrators only.</p>
                        <a href="/logout" class="btn btn-outline-danger rounded-pill px-4">Log Out</a>
                    </div>
                @endif
            @else
                <div class="glass-card text-center py-5 mt-5">
                    <div class="mb-4">
                        <i class="bi bi-mortarboard-fill text-primary" style="font-size: 5rem; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));"></i>
                    </div>
                    <h1 class="fw-bold text-dark mb-3">Academic Portal</h1>
                    <p class="text-muted lead mb-5">Welcome back, Professor. Please sign in to continue.</p>
                    <a href="/auth/login" class="btn btn-primary btn-lg rounded-pill px-5 shadow-lg" style="background: var(--primary-gradient); border: none;">
                        Login to Dashboard
                    </a>
                </div>
            @endauth
        </main>
    </div>
</x-layout>
