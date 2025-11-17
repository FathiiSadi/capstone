<x-layout title="home page">
    <x-bar.topbar> </x-bar.topbar>

    <div class="d-flex">
        <x-bar.sidebar></x-bar.sidebar>
        <main class="flex-grow-1 main">
            <div class="page-title">Dashboard</div>

            <div class="row">
                <div class="col-md-3">
                    <div class="card-custom">
                        <div class="small text-muted">Courses Assigned</div>
                        <div class="kpi">6</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card-custom">
                        <div class="small text-muted">Pending Preferences</div>
                        <div class="kpi">2</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card-custom">
                        <div class="small text-muted">Schedule Status</div>
                        <div class="kpi text-success">OK</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card-custom">
                        <div class="small text-muted">Notifications</div>
                        <div class="kpi">3</div>
                    </div>
                </div>

            </div>

            <div class="card-custom mt-3">
                <h5>Quick Actions</h5>
                <div class="d-flex gap-2 mt-2">
                    <a href="preferences.html" class="btn btn-primary-custom">Submit Preferences</a>
                    <a href="schedule.html" class="btn btn-outline-secondary">Generate Schedule</a>
                    <a href="courses.html" class="btn btn-outline-secondary">Manage Courses</a>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/app.js"></script>
</x-layout>

