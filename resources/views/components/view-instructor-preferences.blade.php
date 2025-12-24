@props([
    'semesterName' => '',
    'submissionTime' => '',
    'courses' => [],
    'timeSlots' => [],
])

<div class="modal-header bg-primary text-white">
    <h5 class="mb-0">
        <i class="bi bi-eye me-2"></i>Preference Details
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
    <!-- Semester Information Card -->
    <div class="card mb-3 border-primary">
        <div class="card-header bg-primary bg-opacity-10">
            <h6 class="mb-0">
                <i class="bi bi-calendar-event me-2"></i>Semester Information
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <p class="mb-2">
                        <strong>Academic Term:</strong><br>
                        <span class="text-primary fs-6" id="view-semester-name">{{ $semesterName }}</span>
                    </p>
                </div>
                <div class="col-12 col-md-6">
                    <p class="mb-2">
                        <strong>Submitted On:</strong><br>
                        <span class="text-muted" id="view-submission-time">{{ $submissionTime }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Course Preferences Card -->
    <div class="card mb-3 border-info">
        <div class="card-header bg-info bg-opacity-10">
            <h6 class="mb-0">
                <i class="bi bi-book me-2"></i>Course Preferences
            </h6>
        </div>
        <div class="card-body">
            <div id="view-courses-list" class="row g-2">
                <!-- Courses will be populated here by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Time Preferences Card -->
    <div class="card border-success">
        <div class="card-header bg-success bg-opacity-10">
            <h6 class="mb-0">
                <i class="bi bi-clock me-2"></i>Time Preferences
            </h6>
        </div>
        <div class="card-body">
            <div id="view-time-slots">
                <!-- Time slots will be populated here by JavaScript -->
            </div>
        </div>
    </div>
</div>

<div class="modal-footer d-flex flex-column flex-sm-row gap-2">
    <button type="button" class="btn btn-secondary w-100 w-sm-auto" data-bs-dismiss="modal">
        <i class="bi bi-x-circle me-1"></i>Close
    </button>
</div>

<style>
    .course-badge {
        display: block;
        padding: 0.75rem 1rem;
        margin: 0.25rem 0;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 8px;
        font-weight: 500;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-align: center;
    }
    
    .course-badge strong {
        display: block;
        font-size: 1.1rem;
        margin-bottom: 0.25rem;
    }
    
    .course-badge small {
        display: block;
        opacity: 0.9;
        font-size: 0.85rem;
    }
    
    .time-preference-item {
        padding: 0.75rem;
        background: #f8f9fa;
        border-left: 4px solid #28a745;
        border-radius: 4px;
        margin-bottom: 0.5rem;
    }
    
    @media (max-width: 768px) {
        .course-badge {
            padding: 0.5rem 0.75rem;
            font-size: 0.9rem;
        }
        
        .course-badge strong {
            font-size: 1rem;
        }
        
        .time-preference-item {
            padding: 0.5rem;
            font-size: 0.9rem;
        }
    }
</style>

