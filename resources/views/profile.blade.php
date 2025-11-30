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

                <div class="mt-3 row">
                    <div class="col-md-3 text-center">
                        <img src="{{ asset('assets/logo.png') }}" class="img-fluid rounded-circle"
                            style="background:#fff;padding:6px;" alt="Logo">
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
        </main>
    </div>
</x-layout>