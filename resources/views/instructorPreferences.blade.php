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
                                            <button class="btn btn-sm btn-outline-secondary view-pref-btn"
                                                data-semester-id="{{ $semesterId }}"
                                                data-semester-name="{{ $pref['semester']->name ?? '' }} - {{ ucfirst($pref['semester']->type ?? '') }}"
                                                data-submission-time="{{ $pref['submission_time']->format('Y-m-d H:i') }}"
                                                data-courses='@json($pref['courses'])'
                                                data-time-slots='@json($pref['time_slots'])' data-bs-toggle="modal"
                                                data-bs-target="#viewPrefModal">
                                                <i class="bi bi-eye"></i>
                                                <span class="d-none d-md-inline ms-1">View</span>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary edit-pref-btn"
                                                data-semester-id="{{ $semesterId }}"
                                                data-semester-name="{{ $pref['semester']->name ?? '' }} - {{ ucfirst($pref['semester']->type ?? '') }}"
                                                data-course-ids='@json($pref['courses']->pluck('id'))'
                                                data-time-slots='@json($pref['time_slots'])' data-bs-toggle="modal"
                                                data-bs-target="#editPrefModal">
                                                <i class="bi bi-pencil"></i>
                                                <span class="d-none d-md-inline ms-1">Edit</span>
                                            </button>
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
                                :notes="old('notes', '')" :semesterId="$activeSemester->id"
                                :semesterName="$activeSemester->name . ' - ' . ucfirst($activeSemester->type)"
                                :isEdit="false" />
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


            <!-- VIEW PREFERENCES MODAL -->
            <div class="modal fade" id="viewPrefModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <x-view-instructor-preferences />
                    </div>
                </div>
            </div>


            <!-- EDIT PREFERENCES MODAL -->
            <div class="modal fade" id="editPrefModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form id="editPrefForm" action="#" method="POST">
                            @csrf
                            <input type="hidden" name="_method" value="PUT">
                            <input type="hidden" id="edit_semester_id" name="semester_id" value="">
                            <div class="modal-header">
                                <div>
                                    <h5 class="mb-0">Edit Course Preferences</h5>
                                    <small id="edit-semester-name"></small>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <!-- Course Selection -->
                                <div class="mb-4">
                                    <label for="editPrefForm_course_ids" class="form-label fw-bold">
                                        <i class="bi bi-book me-2"></i>Select Courses <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select course-select" id="editPrefForm_course_ids"
                                        name="course_ids[]" multiple required style="min-height: 150px;">
                                        @foreach($availableCourses as $course)
                                            <option value="{{ $course->id }}">{{ $course->code }} - {{ $course->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Hold Ctrl/Cmd to select multiple courses, or use the search feature above
                                    </div>
                                </div>

                                <hr class="my-4">

                                <!-- Time Preferences -->
                                <h6 class="mb-3">
                                    <i class="bi bi-clock me-2"></i>Time Preferences <span
                                        class="text-muted">(Optional)</span>
                                </h6>

                                <div class="row g-3">
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">Preferred Days</label>
                                        <select class="form-select" name="preferred_days"
                                            id="editPrefForm_preferred_days">
                                            <option value="">Any Day</option>
                                            <option value="Sat/Tue">Saturday / Tuesday</option>
                                            <option value="Sun/Wed">Sunday / Wednesday</option>
                                            <option value="Mon/Thu">Monday / Thursday</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">Preferred Time</label>
                                        <select class="form-select" name="preferred_time"
                                            id="editPrefForm_preferred_time">
                                            <option value="">Any Time</option>
                                            <option value="Morning">Morning (8:00 - 12:00)</option>
                                            <option value="Noon">Noon (12:00 - 14:00)</option>
                                            <option value="Afternoon">Afternoon (14:00 - 18:00)</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">Additional Notes</label>
                                        <input type="text" class="form-control" name="notes" id="editPrefForm_notes"
                                            placeholder="e.g., Prefer morning classes" maxlength="500">
                                        <small class="form-text text-muted">Max 500 characters</small>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer d-flex flex-column flex-sm-row gap-2">
                                <button type="button" class="btn btn-secondary w-100 w-sm-auto" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle me-1"></i>Cancel
                                </button>
                                <button type="submit" class="btn btn-primary-custom w-100 w-sm-auto">
                                    <i class="bi bi-check-circle me-1"></i>Save Changes
                                </button>
                            </div>
                        </form>
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
            // Initialize Select2 for add form
            function initializeSelect2(selector) {
                $(selector).select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Select courses...',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $(selector).closest('.modal')
                });
            }

            // Initialize Select2 for add modal
            $('#addPrefForm_course_ids').select2({
                theme: 'bootstrap-5',
                placeholder: 'Select courses...',
                allowClear: true,
                width: '100%'
            });

            // Handle View Preferences Modal
            $('.view-pref-btn').on('click', function () {
                const semesterName = $(this).data('semester-name');
                const submissionTime = $(this).data('submission-time');
                const courses = $(this).data('courses');
                const timeSlots = $(this).data('time-slots');

                // Update semester info
                $('#view-semester-name').text(semesterName);
                $('#view-submission-time').text(submissionTime);

                // Display courses with beautiful badges
                if (courses && courses.length > 0) {
                    const coursesList = courses.map(course =>
                        `<div class="col-md-6">
                            <div class="course-badge">
                                <strong>${course.code}</strong><br>
                                <small>${course.name}</small>
                            </div>
                        </div>`
                    ).join('');
                    $('#view-courses-list').html(coursesList);
                } else {
                    $('#view-courses-list').html('<div class="col-12"><p class="text-muted text-center">No courses selected</p></div>');
                }

                // Display time slots with styled cards
                if (timeSlots && timeSlots.length > 0) {
                    const timeSlotsHtml = timeSlots.map(slot => {
                        const parts = slot.days.split(' - ');
                        return `<div class="time-preference-item">
                            <i class="bi bi-clock-fill text-success me-2"></i>
                            <strong>${parts[0] || 'Any Day'}</strong>
                            ${parts[1] ? ` • <i class="bi bi-sun me-1"></i>${parts[1]}` : ''}
                            ${parts[2] ? `<br><small class="text-muted ms-4"><i class="bi bi-sticky me-1"></i>${parts[2]}</small>` : ''}
                        </div>`;
                    }).join('');
                    $('#view-time-slots').html(timeSlotsHtml);
                } else {
                    $('#view-time-slots').html('<p class="text-muted"><i class="bi bi-info-circle me-2"></i>No time preferences specified</p>');
                }
            });

            // Initialize Select2 for edit modal
            $('#editPrefForm_course_ids').select2({
                theme: 'bootstrap-5',
                placeholder: 'Select courses...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#editPrefModal')
            });

            // Handle Edit Preferences Modal
            $('.edit-pref-btn').on('click', function () {
                const semesterId = $(this).data('semester-id');
                const semesterName = $(this).data('semester-name');
                const courseIds = $(this).data('course-ids');
                const timeSlots = $(this).data('time-slots');

                console.log('Edit button clicked:', {
                    semesterId,
                    semesterName,
                    courseIds,
                    timeSlots
                });

                // Store semester ID in hidden field
                $('#edit_semester_id').val(semesterId);

                // Update semester name
                $('#edit-semester-name').text(semesterName);

                // Set selected courses
                $('#editPrefForm_course_ids').val(courseIds).trigger('change');

                // Parse and set time preferences
                let preferredDays = '';
                let preferredTime = '';
                let notes = '';

                if (timeSlots && timeSlots.length > 0) {
                    const firstSlot = timeSlots[0];
                    if (firstSlot && firstSlot.days) {
                        const parts = firstSlot.days.split(' - ');

                        // Try to match preferred days
                        const daysOptions = ['Sat/Tue', 'Sun/Wed', 'Mon/Thu'];
                        const matchedDay = daysOptions.find(day => parts.includes(day));
                        if (matchedDay) {
                            preferredDays = matchedDay;
                        }

                        // Try to match preferred time
                        const timeOptions = ['Morning', 'Noon', 'Afternoon'];
                        const matchedTime = timeOptions.find(time => parts.includes(time));
                        if (matchedTime) {
                            preferredTime = matchedTime;
                        }

                        // Set notes (last part if exists and not day/time)
                        notes = parts.filter(part =>
                            !daysOptions.includes(part) && !timeOptions.includes(part)
                        ).join(' - ');
                    }
                }

                // Set form values
                $('#editPrefForm_preferred_days').val(preferredDays);
                $('#editPrefForm_preferred_time').val(preferredTime);
                $('#editPrefForm_notes').val(notes);
            });

            // Handle form submission - set action before submitting
            $('#editPrefForm').on('submit', function (e) {
                e.preventDefault();

                const semesterId = $('#edit_semester_id').val();
                if (!semesterId) {
                    alert('Error: Semester ID is missing. Please try again.');
                    return false;
                }

                const formAction = "{{ route('instructor.preferences.update', ':id') }}".replace(':id', semesterId);
                console.log('Form submitting to:', formAction);

                // Set the action and submit
                $(this).attr('action', formAction);
                this.submit();
            });

            // Reset edit form when modal is closed
            $('#editPrefModal').on('hidden.bs.modal', function () {
                $('#editPrefForm_course_ids').val(null).trigger('change');
                $('#editPrefForm_preferred_days').val('');
                $('#editPrefForm_preferred_time').val('');
                $('#editPrefForm_notes').val('');
                $('#edit-semester-name').text('');
            });

            // Reset add form when modal is closed
            $('#addPrefModal').on('hidden.bs.modal', function () {
                $('#addPrefForm_course_ids').val(null).trigger('change');
                $('#addPrefForm_preferred_days').val('');
                $('#addPrefForm_preferred_time').val('');
                $('#addPrefForm_notes').val('');
            });
        });
    </script>
</x-layout>