<header class="topbar">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-link text-white p-0 me-2" id="sidebarToggle">
            <i class="bi bi-list fs-4"></i>
        </button>
        <img src="{{ asset('assets/logo.png') }}" alt="HTU Logo">
        <span class="fw-bold fs-5 text-white ms-2">HTU Portal</span>
    </div>
    <div class="d-flex align-items-center gap-3">
        @auth
            <span>{{ auth()->user()->name }}</span>
            <div class="dropdown">
                <img src="{{ asset('assets/logo.png') }}" alt="Avatar"
                    style="width:36px;height:36px;border-radius:50%;background:#fff;cursor:pointer;"
                    id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li>
                        <a class="dropdown-item" href="{{ route('instructor.profile') }}">
                            <i class="bi bi-person me-2"></i>Profile
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
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