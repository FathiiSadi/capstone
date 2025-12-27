<x-layout title="Instructor Profile">
    <x-bar.topbar> </x-bar.topbar>

    <div class="d-flex">
        <x-bar.sidebar></x-bar.sidebar>

        <main class="flex-grow-1 main">
            <div class="page-title">Instructor Profile</div>

            <div class="card card-custom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5>Profile</h5>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show mx-3 mt-3" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show mx-3 mt-3" role="alert">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="mt-3 row">
                    <div class="col-md-3 text-center">
                        <div class="card-body text-center">
                            <div class="position-relative d-inline-block">
                                <img src="{{ $user->profile_photo_path ? asset('storage/' . $user->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=0D6EFD&color=ffffff' }}"
                                    alt="Profile Picture" class="rounded-circle mb-3" width="150" height="150"
                                    style="object-fit: cover;">

                                <form action="{{ route('instructor.profile.update') }}" method="POST"
                                    enctype="multipart/form-data" id="profilePhotoForm">
                                    @csrf
                                    <label for="profile_photo"
                                        class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle p-2 mb-3 me-2 shadow-sm"
                                        style="cursor: pointer; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;"
                                        title="Change Profile Photo">
                                        <i class="bi bi-camera-fill"></i>
                                    </label>
                                    <input type="file" name="profile_photo" id="profile_photo" class="d-none"
                                        onchange="document.getElementById('profilePhotoForm').submit()"
                                        accept="image/*">
                                </form>
                            </div>
                            <h4 class="mb-1">{{ $user->name }}</h4>
                            <p class="text-muted mb-3">{{ ucfirst($user->role) }}</p>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <dl class="row">
                            <dt class="col-sm-4">Name</dt>
                            <dd class="col-sm-8">{{ $user->name }}</dd>

                            <dt class="col-sm-4">Email</dt>
                            <dd class="col-sm-8">{{ $user->email }}</dd>

                            <dt class="col-sm-4">Department</dt>
                            <dd class="col-sm-8">{{ $user->department?->name ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Position</dt>
                            <dd class="col-sm-8">
                                {{ $user->instructor?->position?->value ? ucwords($user->instructor->position->value) : 'N/A' }}
                            </dd>

                            <dt class="col-sm-4">Min Credit Hours</dt>
                            <dd class="col-sm-8">{{ $user->instructor?->min_credits ?? 'N/A' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            @include('partials.preferences-on-profile')
        </main>
    </div>
</x-layout>