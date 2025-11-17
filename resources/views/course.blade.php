<x-layout title="course">
    <x-bar.topbar> </x-bar.topbar>

    <div class="d-flex">
        <x-bar.sidebar></x-bar.sidebar>

        <main class="flex-grow-1 main">
            <div class="page-title">Course Management</div>

            <div class="card card-custom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5>Course & Sections</h5>
                    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addCourseModal">Add
                        Course
                    </button>
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>Course ID</th>
                            <th>Name</th>
                            <th>Credit Hrs</th>
                            <th>Sections</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>CS101</td>
                            <td>Intro to Programming</td>
                            <td>3</td>
                            <td>2</td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary">Edit</button>
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add Course Modal -->
            <div class="modal fade" id="addCourseModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5>Add New Course</h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form>
                                <div class="mb-2"><label>Course ID</label><input class="form-control"></div>
                                <div class="mb-2"><label>Course Name</label><input class="form-control"></div>
                                <div class="mb-2"><label>Credit Hours</label><input class="form-control" type="number">
                                </div>
                                <div class="mb-2"><label>Number of Sections</label><input class="form-control"
                                                                                          type="number"></div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-primary-custom">Save</button>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</x-layout>
