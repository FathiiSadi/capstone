<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Qalam Portal')</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <link rel="stylesheet" href="{{ asset('style.css') }}">
</head>

<body>

    <header class="topbar">
        <div class="d-flex align-items-center gap-2">
            <img src="{{ asset('assets/logo.png') }}" alt="HTU Logo">
            <span class="fw-bold fs-5 text-white ms-2">Qalam Portal</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span>Mohammad Naim Hussien Shamieh</span>
            <img src="{{ asset('assets/logo.png') }}" alt="Avatar"
                style="width:36px;height:36px;border-radius:50%;background:#fff;">
        </div>
    </header>

    <div class="d-flex">

        <aside class="sidebar d-none d-lg-block">
            <div class="brand">
                <span class="fw-bold text-white">Qalam Portal</span>
            </div>
            <nav class="mt-3">
                <a href="{{ route('home') }}" class="nav-link">Home</a>
                <a href="{{ route('instructor.preferences') }}" class="nav-link">Instructor Preferences</a>
                <a href="{{ route('instructor.profile') }}" class="nav-link">Profile</a>
                <a href="{{ route('logout') }}" class="nav-link">Logout</a>
            </nav>
        </aside>

        <main class="flex-grow-1 main">
            @yield('content')
        </main>
    </div>

</body>

</html>