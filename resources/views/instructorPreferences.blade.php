<x-layout title="Instructor Preferences">
    <x-bar.topbar> </x-bar.topbar>

    <div class="d-flex">
        <x-bar.sidebar></x-bar.sidebar>
        <main class="flex-grow-1 main">
            <div class="page-title">Instructor Preferences</div>
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card card-custom">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">Preferences Submissions</h5>
                    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addPrefModal">
                        <i class="bi bi-plus-circle me-1"></i>
                        <span class="d-none d-sm-inline">Add </span>Preferences
                    </button>
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Academic Term</th>
                                <th>Courses</th>
                                <th>Time Preferences</th>
                                <th>Submitted On</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($preferences as $semesterId => $pref)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $pref['semester']->name ?? 'N/A' }} -
                                        {{ ucfirst($pref['semester']->type ?? '') }}
                                    </td>
                                    <td>
                                        @if($pref['courses']->isNotEmpty())
                                            <small>
                                                @foreach($pref['courses']->take(2) as $course)
                                                    <span class="badge bg-info text-dark me-1">{{ $course->code }}</span>
                                                @endforeach
                                                @if($pref['courses']->count() > 2)
                                                    <span class="badge bg-secondary">+{{ $pref['courses']->count() - 2 }}
                                                        more</span>
                                                @endif
                                            </small>
                                        @else
                                            <small class="text-muted">No courses</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($pref['time_slots']->isNotEmpty())
                                            <small class="text-muted">{{ $pref['time_slots']->first()->days }}</small>
                                        @else
                                            <small class="text-muted">No preference</small>
                                        @endif
                                    </td>
                                    <td>{{ $pref['submission_time']->format('Y-m-d H:i') }}</td>
                                    <td><span class="badge bg-success">Submitted</span></td>
                                    <td>
                                        <div class="d-flex flex-column flex-sm-row gap-1">

                                            <form action="{{ route('instructor.preferences.destroy', $semesterId) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete these preferences?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                    <span class="d-none d-md-inline ms-1">Delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No preferences submitted yet.</td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>

            </div>


            <!-- ADD PREFERENCES MODAL -->
            <div class="modal fade" id="addPrefModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        @if($activeSemester)
                            <x-instructor-preference-form formId="addPrefForm"
                                :action="route('instructor.preferences.store')" method="POST"
                                :availableCourses="$availableCourses" :selectedCourseIds="old('course_ids', [])"
                                :preferredDays="old('preferred_days', '')" :preferredTime="old('preferred_time', '')"
                                :semesterId="$activeSemester->id" :semesterName="$activeSemester->name . ' - ' . ucfirst($activeSemester->type)" :isEdit="false" />
                        @else
                            <div class="modal-header bg-warning">
                                <h5 class="mb-0">No Active Semester</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center py-5">
                                <i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">No Active Semester Available</h5>
                                <p class="text-muted">Please contact the administrator to activate a semester before
                                    submitting preferences.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>




        </main>
    </div>

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function () {
            // Initialize Select2 for add modal
            $('#addPrefForm_course_ids').select2({
                theme: 'bootstrap-5',
                placeholder: 'Select courses...',
                allowClear: true,
                width: '100%'
            });

            // Reset add form when modal is closed
            $('#addPrefModal').on('hidden.bs.modal', function () {
                $('#addPrefForm_course_ids').val(null).trigger('change');
                $('#addPrefForm_preferred_days').val('');
                $('#addPrefForm_preferred_time').val('');
            });
        });
    </script>
</x-layout>