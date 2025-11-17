@php use Illuminate\Support\Facades\URL; @endphp

<x-layout title="Instructor Preferences">
    <!-- Topbar -->
    <x-bar.topbar> </x-bar.topbar>

    <!-- Layout with Sidebar -->
    <div class="d-flex">
        <!-- Sidebar component -->
        <x-bar.sidebar></x-bar.sidebar>

        <!-- Main content -->
        <main class="flex-grow-1 main">
            <div class="page-title">Instructor Preferences</div>

            <div class="card card-custom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5>Set Your Preferences</h5>
                    <small class="text-muted">Choose preferred courses, times, and priorities</small>
                </div>

                <form class="row mt-3 g-3">
                    <!-- Course -->
                    <div class="col-md-6">
                        <label class="form-label">Course</label>
                        <select class="form-select">
                            <option>CS101 - Intro to Programming</option>
                            <option>CS201 - Data Structures</option>
                            <option>CS301 - Algorithms</option>
                        </select>
                    </div>

                    <!-- Priority -->
                    <div class="col-md-6">
                        <label class="form-label">Priority (1 = highest)</label>
                        <select class="form-select">
                            <option>1</option>
                            <option>2</option>
                            <option>3</option>
                        </select>
                    </div>

                    <!-- Preferred Days -->
                    <div class="col-md-6">
                        <label class="form-label">Preferred Days</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="mon">
                            <label class="form-check-label" for="mon">Mon</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="tue">
                            <label class="form-check-label" for="tue">Tue</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="wed">
                            <label class="form-check-label" for="wed">Wed</label>
                        </div>
                    </div>

                    <!-- Preferred Time Slots -->
                    <div class="col-md-6">
                        <label class="form-label">Preferred Time Slots</label>
                        <select class="form-select" multiple>
                            <option>08:00 - 10:00</option>
                            <option>10:00 - 12:00</option>
                            <option>14:00 - 16:00</option>
                        </select>
                    </div>

                    <!-- Submit -->
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary-custom">Submit Preferences</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</x-layout>
