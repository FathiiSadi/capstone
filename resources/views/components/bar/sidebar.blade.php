<aside class="sidebar d-none d-lg-block" id="sidebar">
    <div class="brand">
        <span class="fw-bold text-white">HTU Portal</span>
    </div>
    <nav class="mt-3">
        @guest
            {{-- Guest users only see login --}}
            <a href="/auth/login" class="nav-link">Login</a>
        @else
            {{-- Authenticated users --}}
            @if(in_array(auth()->user()->role, ['instructor', 'admin']))
                {{-- Instructor/Admin menu --}}
                <a href="/" class="nav-link">Home</a>
                <a href="/preferences" class="nav-link">Instructor Preferences</a>

                <a href="/profile" class="nav-link">Profile</a>
                <a href="/logout" class="nav-link">Logout</a>
            @else
                {{-- Other authenticated users --}}
                <a href="/" class="nav-link">Home</a>
                <a href="/logout" class="nav-link">Logout</a>
            @endif
        @endguest
    </nav>
</aside>
