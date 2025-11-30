<x-layout title="Instructor Home">
    <x-bar.topbar> </x-bar.topbar>

    <div class="d-flex">
        <x-bar.sidebar></x-bar.sidebar>

        <main class="flex-grow-1 main">
            <div class="page-title">Instructor Dashboard</div>
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-4">
                    <div class="card card-custom p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted">Total Courses</div>
                                <div class="kpi">2</div>
                            </div>
                            <div class="fs-3"><i class="bi bi-journal-bookmark"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card card-custom p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted">Assigned Sections</div>
                                <div class="kpi">4</div>
                            </div>
                            <div class="fs-3"><i class="bi bi-people"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card card-custom p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted">Current Load (C.H.)</div>
                                <div class="kpi">12</div>
                            </div>
                            <div class="fs-3"><i class="bi bi-clock-history"></i></div>
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

                        <!-- Preferences Submitted -->
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold">Preferences Submitted</div>
                                Courses preferences submitted on <strong>2025-01-12</strong> for <strong>Spring
                                    2025</strong>.
                            </div>
                            <span class="badge bg-success rounded-pill">✔</span>
                        </li>

                        <!-- Existing examples -->
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold">New Schedule Published</div>
                                Your Spring 2025 teaching schedule is now available.
                            </div>
                            <span class="badge bg-primary rounded-pill">New</span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold">Preference Submission Reminder</div>
                                Please submit your preferences before <strong>Jan 20, 2025</strong>.
                            </div>
                            <span class="badge bg-warning text-dark rounded-pill">Reminder</span>
                        </li>

                    </ul>
                </div>
            </div>



            <div class="card card-custom mb-3">
                <h5>My Courses Schedule</h5>

                <div class="table-responsive mt-3">
                    <table class="table table-striped">
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
                            <tr>
                                <td>CS101 - Intro to Programming</td>
                                <td>1</td>
                                <td>Sun / Tue</td>
                                <td>10:00 - 11:30</td>
                                <td>S-207</td>
                            </tr>
                            <tr>
                                <td>CS101 - Intro to Programming</td>
                                <td>3</td>
                                <td>Sun / Tue</td>
                                <td>13:30 - 15:00</td>
                                <td>S-204</td>
                            </tr>
                            <tr>
                                <td>CS202 - Algorithms</td>
                                <td>2</td>
                                <td>Mon / Wed</td>
                                <td>12:00 - 13:30</td>
                                <td>S-205</td>
                            </tr>
                            <tr>
                                <td>CS202 - Algorithms</td>
                                <td>3</td>
                                <td>Mon / Wed</td>
                                <td>13:30 - 15:00</td>
                                <td>W-105</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Office Hours Table -->
            <div class="card card-custom mb-3">
                <h5>Office Hours</h5>

                <div class="table-responsive mt-3">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Office</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Sunday</td>
                                <td>14:00 – 15:00</td>
                                <td>S-310</td>
                            </tr>
                            <tr>
                                <td>Tuesday</td>
                                <td>09:00 – 10:00</td>
                                <td>S-310</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Preferences -->
            <div class="card card-custom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5>Recent Preferences Submissions</h5>
                    <div>
                        <a href="{{ route('instructor.preferences') }}"
                            class="btn btn-primary-custom btn-sm">Preferences</a>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Year/Sem</th>
                                    <th>Submitted On</th>
                                    <th>Priority Count</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>2025 - Spring</td>
                                    <td>2025-05-01</td>
                                    <td>8</td>
                                    <td><span class="badge bg-success">Accepted</span></td>
                                </tr>
                                <tr>
                                    <td>2024 - Fall</td>
                                    <td>2024-11-10</td>
                                    <td>6</td>
                                    <td><span class="badge bg-secondary">Archived</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>
</x-layout>
