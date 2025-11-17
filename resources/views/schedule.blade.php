@php use Illuminate\Support\Facades\URL; @endphp
<x-layout title="schedule">
    <x-bar.topbar> </x-bar.topbar>

    <div class="d-flex">
        <x-bar.sidebar></x-bar.sidebar>
        <main class="flex-grow-1 main">
            <div class="page-title">Schedule Generator</div>

            <!-- Generate button and progress -->
            <div class="card card-custom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5>Generate Schedule Options</h5>
                    <button id="generate-schedule-btn" class="btn btn-primary-custom">Generate Schedules</button>
                </div>

                <div class="mt-3">
                    <div class="progress" style="height:28px;">
                        <div id="schedule-progress" class="progress-bar" style="width:0%">0%</div>
                    </div>
                </div>
            </div>

            <!-- Tabs for Sch1, Sch2, Sch3 -->
            <div class="card card-custom mt-4">
                <ul class="nav nav-pills justify-content-center mb-3" id="scheduleTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="sch1-tab" data-bs-toggle="pill" data-bs-target="#sch1"
                                type="button">Schedule 1
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="sch2-tab" data-bs-toggle="pill" data-bs-target="#sch2"
                                type="button">Schedule 2
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="sch3-tab" data-bs-toggle="pill" data-bs-target="#sch3"
                                type="button">Schedule 3
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="scheduleTabsContent">

                    <!-- Schedule 1 -->
                    <div class="tab-pane fade show active" id="sch1" role="tabpanel">
                        <h6 class="text-danger fw-bold mb-3">Schedule Option 1</h6>
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th>Instructor</th>
                                <th>Course</th>
                                <th>Section</th>
                                <th>Time Slot</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>Dr. Ahmad</td>
                                <td>CS101</td>
                                <td>1</td>
                                <td>Mon 08:00 - 10:00</td>
                            </tr>
                            <tr>
                                <td>Dr. Lina</td>
                                <td>CS201</td>
                                <td>1</td>
                                <td>Tue 10:00 - 12:00</td>
                            </tr>
                            <tr>
                                <td>Dr. Sami</td>
                                <td>CS301</td>
                                <td>1</td>
                                <td>Wed 12:00 - 14:00</td>
                            </tr>
                            </tbody>
                        </table>
                        <button id="select-schedule-btn" class="btn btn-primary-custom">Select Schedule</button>

                    </div>

                    <!-- Schedule 2 -->
                    <div class="tab-pane fade" id="sch2" role="tabpanel">
                        <h6 class="text-danger fw-bold mb-3">Schedule Option 2</h6>
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th>Instructor</th>
                                <th>Course</th>
                                <th>Section</th>
                                <th>Time Slot</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>Dr. Ahmad</td>
                                <td>CS101</td>
                                <td>2</td>
                                <td>Mon 10:00 - 12:00</td>
                            </tr>
                            <tr>
                                <td>Dr. Lina</td>
                                <td>CS201</td>
                                <td>1</td>
                                <td>Wed 08:00 - 10:00</td>
                            </tr>
                            <tr>
                                <td>Dr. Sami</td>
                                <td>CS301</td>
                                <td>1</td>
                                <td>Thu 14:00 - 16:00</td>
                            </tr>
                            </tbody>
                        </table>
                        <button id="select-schedule-btn" class="btn btn-primary-custom">Select Schedule</button>

                    </div>

                    <!-- Schedule 3 -->
                    <div class="tab-pane fade" id="sch3" role="tabpanel">
                        <h6 class="text-danger fw-bold mb-3">Schedule Option 3</h6>
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th>Instructor</th>
                                <th>Course</th>
                                <th>Section</th>
                                <th>Time Slot</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>Dr. Ahmad</td>
                                <td>CS101</td>
                                <td>1</td>
                                <td>Sun 08:00 - 10:00</td>
                            </tr>
                            <tr>
                                <td>Dr. Lina</td>
                                <td>CS201</td>
                                <td>2</td>
                                <td>Tue 14:00 - 16:00</td>
                            </tr>
                            <tr>
                                <td>Dr. Sami</td>
                                <td>CS301</td>
                                <td>2</td>
                                <td>Wed 10:00 - 12:00</td>
                            </tr>
                            </tbody>
                        </table>
                        <button id="select-schedule-btn" class="btn btn-primary-custom">Select Schedule</button>

                    </div>
                </div>
            </div>
        </main>
    </div>
</x-layout>
