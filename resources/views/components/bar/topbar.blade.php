<header class="topbar">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-link text-white p-0 me-2" id="sidebarToggle">
            <i class="bi bi-list fs-4"></i>
        </button>

            <img src="{{ asset('assets/logo.png') }}" alt="Qalam Logo">
        

    </div>
    <div class="d-flex align-items-center gap-3">
        @auth
            <div class="dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="{{ Auth::user()->profile_photo_path ? asset('storage/' . Auth::user()->profile_photo_path) : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&background=0D6EFD&color=ffffff' }}"
                        alt="Profile" class="rounded-circle me-2" width="32" height="32" style="object-fit: cover;">
                    <span class="d-none d-md-inline text-dark">{{ Auth::user()->name }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li>
                        <a class="dropdown-item" href="{{ route('instructor.profile') }}">
                            <i class="bi bi-person me-2"></i>Profile
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item" href="/logout">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        @else
            <a href="/auth/login" class="btn btn-sm btn-outline-light">
                <i class="bi bi-box-arrow-in-right me-1"></i>Login
            </a>
        @endauth
    </div>
</header>