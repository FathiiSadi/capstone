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

                <div class="d-flex justify-content-between align-items-center">
                    <h5>Preferences Submissions</h5>
                    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addPrefModal">Add
                        Preferences</button>
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Academic Term</th>
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
                                    <td>{{ $pref['submission_time']->format('Y-m-d H:i') }}</td>
                                    <td><span class="badge bg-success">Submitted</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-secondary view-pref-btn"
                                            data-semester-id="{{ $semesterId }}"
                                            data-semester-name="{{ $pref['semester']->name ?? '' }} - {{ ucfirst($pref['semester']->type ?? '') }}"
                                            data-submission-time="{{ $pref['submission_time']->format('Y-m-d H:i') }}"
                                            data-courses='@json($pref['courses'])'
                                            data-time-slots='@json($pref['time_slots'])' data-bs-toggle="modal"
                                            data-bs-target="#viewPrefModal">View</button>
                                        <button class="btn btn-sm btn-outline-primary edit-pref-btn"
                                            data-semester-id="{{ $semesterId }}"
                                            data-semester-name="{{ $pref['semester']->name ?? '' }} - {{ ucfirst($pref['semester']->type ?? '') }}"
                                            data-course-ids='@json($pref['courses']->pluck('id'))'
                                            data-time-slots='@json($pref['time_slots'])' data-bs-toggle="modal"
                                            data-bs-target="#editPrefModal">Edit</button>
                                        <form action="{{ route('instructor.preferences.destroy', $semesterId) }}"
                                            method="POST" class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete these preferences?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">No preferences submitted yet.</td>
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
                            <form action="{{ route('instructor.preferences.store') }}" method="POST" id="addPrefForm">
                                @csrf
                                <div class="modal-header bg-primary text-white">
                                    <div>
                                        <h5 class="mb-0">Add Course Preferences</h5>
                                        <small>{{ $activeSemester->name }} - {{ ucfirst($activeSemester->type) }}</small>
                                    </div>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body">
                                    <input type="hidden" name="semester_id" value="{{ $activeSemester->id }}">

                                    @if($errors->any())
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <strong>Please fix the following errors:</strong>
                                            <ul class="mb-0 mt-2">
                                                @foreach($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    @endif

                                    <!-- Course Selection -->
                                    <div class="mb-4">
                                        <label for="course_ids" class="form-label fw-bold">
                                            <i class="bi bi-book me-2"></i>Select Courses <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select @error('course_ids') is-invalid @enderror" 
                                                id="course_ids" 
                                                name="course_ids[]" 
                                                multiple 
                                                required
                                                style="min-height: 150px;">
                                            @forelse($availableCourses as $course)
                                                <option value="{{ $course->id }}" {{ in_array($course->id, old('course_ids', [])) ? 'selected' : '' }}>
                                                    {{ $course->code }} - {{ $course->name }}
                                                </option>
                                            @empty
                                                <option disabled>No courses available</option>
                                            @endforelse
                                        </select>
                                        @error('course_ids')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Hold Ctrl/Cmd to select multiple courses, or use the search feature above
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    <!-- Time Preferences -->
                                    <h6 class="mb-3">
                                        <i class="bi bi-clock me-2"></i>Time Preferences <span class="text-muted">(Optional)</span>
                                    </h6>

                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Preferred Days</label>
                                            <select class="form-select" name="preferred_days">
                                                <option value="" {{ old('preferred_days') == '' ? 'selected' : '' }}>Any Day</option>
                                                <option value="Sat/Tue" {{ old('preferred_days') == 'Sat/Tue' ? 'selected' : '' }}>Saturday / Tuesday</option>
                                                <option value="Sun/Wed" {{ old('preferred_days') == 'Sun/Wed' ? 'selected' : '' }}>Sunday / Wednesday</option>
                                                <option value="Mon/Thu" {{ old('preferred_days') == 'Mon/Thu' ? 'selected' : '' }}>Monday / Thursday</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Preferred Time</label>
                                            <select class="form-select" name="preferred_time">
                                                <option value="" {{ old('preferred_time') == '' ? 'selected' : '' }}>Any Time</option>
                                                <option value="Morning" {{ old('preferred_time') == 'Morning' ? 'selected' : '' }}>Morning (8:00 - 12:00)</option>
                                                <option value="Noon" {{ old('preferred_time') == 'Noon' ? 'selected' : '' }}>Noon (12:00 - 14:00)</option>
                                                <option value="Afternoon" {{ old('preferred_time') == 'Afternoon' ? 'selected' : '' }}>Afternoon (14:00 - 18:00)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Additional Notes</label>
                                            <input type="text" 
                                                   class="form-control @error('notes') is-invalid @enderror" 
                                                   name="notes"
                                                   value="{{ old('notes') }}"
                                                   placeholder="e.g., Prefer morning classes"
                                                   maxlength="500">
                                            @error('notes')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">Max 500 characters</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="bi bi-x-circle me-1"></i>Cancel
                                    </button>
                                    <button type="submit" class="btn btn-primary-custom">
                                        <i class="bi bi-check-circle me-1"></i>Save Preferences
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="modal-header bg-warning">
                                <h5 class="mb-0">No Active Semester</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center py-5">
                                <i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">No Active Semester Available</h5>
                                <p class="text-muted">Please contact the administrator to activate a semester before submitting preferences.</p>
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
                        <div class="modal-header">
                            <h5>View Preferences</h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <dl class="row">
                                <dt class="col-sm-4">Term</dt>
                                <dd class="col-sm-8" id="view-semester-name">-</dd>

                                <dt class="col-sm-4">Submitted</dt>
                                <dd class="col-sm-8" id="view-submission-time">-</dd>

                                <dt class="col-sm-4">Time Preferences</dt>
                                <dd class="col-sm-8" id="view-time-slots">-</dd>
                            </dl>

                            <h6>Course Preferences</h6>
                            <ul id="view-courses-list">
                                <li>No courses selected</li>
                            </ul>
                        </div>

                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>


            <!-- EDIT PREFERENCES MODAL -->
            <div class="modal fade" id="editPrefModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form id="editPrefForm" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 id="edit-modal-title">Edit Course Preferences</h5>
                                <button class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="edit_course_ids" class="form-label">Select Courses <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="edit_course_ids" name="course_ids[]" multiple
                                        required>
                                        @foreach($availableCourses as $course)
                                            <option value="{{ $course->id }}">{{ $course->code }} - {{ $course->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Hold Ctrl/Cmd to select multiple courses or use the search
                                        box</small>
                                </div>

                                <h6>Preferred Days / Time</h6>

                                <div class="row g-2 mb-2">
                                    <div class="col-md-4">
                                        <label class="form-label">Preferred Days</label>
                                        <select class="form-select" name="preferred_days" id="edit_preferred_days">
                                            <option value="">Any</option>
                                            <option value="Sat/Tue">Sat/Tue</option>
                                            <option value="Sun/Wed">Sun/Wed</option>
                                            <option value="Mon/Thu">Mon/Thu</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Preferred Time</label>
                                        <select class="form-select" name="preferred_time" id="edit_preferred_time">
                                            <option value="">Any</option>
                                            <option value="Morning">Morning</option>
                                            <option value="Noon">Noon</option>
                                            <option value="Afternoon">Afternoon</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Notes</label>
                                        <input type="text" class="form-control" name="notes" id="edit_notes"
                                            placeholder="Additional notes..." maxlength="500">
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary-custom">Save Changes</button>
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
            // Initialize Select2 for multi-select dropdowns
            $('#course_ids').select2({
                theme: 'bootstrap-5',
                placeholder: 'Select courses...',
                allowClear: true,
                width: '100%'
            });

            $('#edit_course_ids').select2({
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

                $('#view-semester-name').text(semesterName);
                $('#view-submission-time').text(submissionTime);

                // Display time slots
                if (timeSlots && timeSlots.length > 0) {
                    const timeSlotsText = timeSlots.map(slot => slot.days).join(', ');
                    $('#view-time-slots').text(timeSlotsText);
                } else {
                    $('#view-time-slots').text('No time preferences specified');
                }

                // Display courses
                if (courses && courses.length > 0) {
                    const coursesList = courses.map(course =>
                        `<li>${course.code} — ${course.name}</li>`
                    ).join('');
                    $('#view-courses-list').html(coursesList);
                } else {
                    $('#view-courses-list').html('<li>No courses selected</li>');
                }
            });

            // Handle Edit Preferences Modal
            $('.edit-pref-btn').on('click', function () {
                const semesterId = $(this).data('semester-id');
                const semesterName = $(this).data('semester-name');
                const courseIds = $(this).data('course-ids');
                const timeSlots = $(this).data('time-slots');

                // Update modal title
                $('#edit-modal-title').text(`Edit Course Preferences - ${semesterName}`);

                // Update form action
                const formAction = "{{ route('instructor.preferences.update', ':id') }}".replace(':id', semesterId);
                $('#editPrefForm').attr('action', formAction);

                // Set selected courses
                $('#edit_course_ids').val(courseIds).trigger('change');

                // Parse and set time preferences
                if (timeSlots && timeSlots.length > 0) {
                    const firstSlot = timeSlots[0];
                    if (firstSlot && firstSlot.days) {
                        const parts = firstSlot.days.split(' - ');

                        // Try to match preferred days
                        const daysOptions = ['Sat/Tue', 'Sun/Wed', 'Mon/Thu'];
                        const matchedDay = daysOptions.find(day => parts.includes(day));
                        if (matchedDay) {
                            $('#edit_preferred_days').val(matchedDay);
                        }

                        // Try to match preferred time
                        const timeOptions = ['Morning', 'Noon', 'Afternoon'];
                        const matchedTime = timeOptions.find(time => parts.includes(time));
                        if (matchedTime) {
                            $('#edit_preferred_time').val(matchedTime);
                        }

                        // Set notes (last part if exists and not day/time)
                        const notes = parts.filter(part =>
                            !daysOptions.includes(part) && !timeOptions.includes(part)
                        ).join(' - ');
                        if (notes) {
                            $('#edit_notes').val(notes);
                        }
                    }
                } else {
                    // Reset fields
                    $('#edit_preferred_days').val('');
                    $('#edit_preferred_time').val('');
                    $('#edit_notes').val('');
                }
            });

            // Reset edit form when modal is closed
            $('#editPrefModal').on('hidden.bs.modal', function () {
                $('#edit_course_ids').val(null).trigger('change');
                $('#edit_preferred_days').val('');
                $('#edit_preferred_time').val('');
                $('#edit_notes').val('');
            });

            // Reset add form when modal is closed
            $('#addPrefModal').on('hidden.bs.modal', function () {
                $('#course_ids').val(null).trigger('change');
                $('select[name="preferred_days"]').val('');
                $('select[name="preferred_time"]').val('');
                $('input[name="notes"]').val('');
            });
        });
    </script>
</x-layout>