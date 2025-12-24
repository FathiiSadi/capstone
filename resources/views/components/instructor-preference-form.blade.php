@props([
    'formId' => 'preferenceForm',
    'action' => '',
    'method' => 'POST',
    'availableCourses' => [],
    'selectedCourseIds' => [],
    'preferredDays' => '',
    'preferredTime' => '',
    'notes' => '',
    'semesterId' => null,
    'semesterName' => '',
    'isEdit' => false,
])

<form id="{{ $formId }}" action="{{ $action }}" method="POST">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="modal-header {{ $isEdit ? '' : 'bg-primary text-white' }}">
        <div>
            <h5 class="mb-0">{{ $isEdit ? 'Edit' : 'Add' }} Course Preferences</h5>
            @if($semesterName)
                <small>{{ $semesterName }}</small>
            @endif
        </div>
        <button type="button" class="btn-close {{ $isEdit ? '' : 'btn-close-white' }}" data-bs-dismiss="modal"></button>
    </div>

    <div class="modal-body">
        @if(!$isEdit)
            <input type="hidden" name="semester_id" value="{{ $semesterId }}">
        @endif

        @if($errors->any() && !$isEdit)
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
            <label for="{{ $formId }}_course_ids" class="form-label fw-bold">
                <i class="bi bi-book me-2"></i>Select Courses <span class="text-danger">*</span>
            </label>
            <select class="form-select course-select @error('course_ids') is-invalid @enderror"
                    id="{{ $formId }}_course_ids"
                    name="course_ids[]"
                    multiple
                    required
                    style="min-height: 150px;">
                @forelse($availableCourses as $course)
                    <option value="{{ $course->id }}" 
                        {{ in_array($course->id, $selectedCourseIds) ? 'selected' : '' }}>
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
            <div class="col-12 col-md-4">
                <label class="form-label">Preferred Days</label>
                <select class="form-select" name="preferred_days" id="{{ $formId }}_preferred_days">
                    <option value="" {{ $preferredDays == '' ? 'selected' : '' }}>Any Day</option>
                    <option value="Sat/Tue" {{ $preferredDays == 'Sat/Tue' ? 'selected' : '' }}>Saturday / Tuesday</option>
                    <option value="Sun/Wed" {{ $preferredDays == 'Sun/Wed' ? 'selected' : '' }}>Sunday / Wednesday</option>
                    <option value="Mon/Thu" {{ $preferredDays == 'Mon/Thu' ? 'selected' : '' }}>Monday / Thursday</option>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Preferred Time</label>
                <select class="form-select" name="preferred_time" id="{{ $formId }}_preferred_time">
                    <option value="" {{ $preferredTime == '' ? 'selected' : '' }}>Any Time</option>
                    <option value="Morning" {{ $preferredTime == 'Morning' ? 'selected' : '' }}>Morning (8:00 - 12:00)</option>
                    <option value="Noon" {{ $preferredTime == 'Noon' ? 'selected' : '' }}>Noon (12:00 - 14:00)</option>
                    <option value="Afternoon" {{ $preferredTime == 'Afternoon' ? 'selected' : '' }}>Afternoon (14:00 - 18:00)</option>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Additional Notes</label>
                <input type="text"
                       class="form-control @error('notes') is-invalid @enderror"
                       name="notes"
                       id="{{ $formId }}_notes"
                       value="{{ $notes }}"
                       placeholder="e.g., Prefer morning classes"
                       maxlength="500">
                @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Max 500 characters</small>
            </div>
        </div>
    </div>

    <div class="modal-footer d-flex flex-column flex-sm-row gap-2">
        <button type="button" class="btn btn-secondary w-100 w-sm-auto" data-bs-dismiss="modal">
            <i class="bi bi-x-circle me-1"></i>Cancel
        </button>
        <button type="submit" class="btn btn-primary-custom w-100 w-sm-auto">
            <i class="bi bi-check-circle me-1"></i>{{ $isEdit ? 'Save Changes' : 'Save Preferences' }}
        </button>
    </div>
</form>
