        <nav class="flex items-center bg-sky-900 justify-between p-4 text-white">
            <a href="">
                <img src="{{ Vite::asset('resources/images/solvex-logo.png') }}" alt="logo" class="h-8">
            </a>
            <div class="flex space-x-6 font-bold">

                <a href="/" class="text-white">Home</a>
                <a href="/schedule" class="text-white">schedule</a>
                @auth
                <a href="" class="text-white">Profile ({{ Auth::user()->name }})</a>
                @endauth
                <!-- <a href="" class="text-white">Companies</a> -->
            </div>
            @auth
            <div>

                <a href="/logout" class="text-white">Logout</a>

            </div>
            @endauth

            @guest
            <div class="space-x-6 font-bold ">
                <a href="/login" class="text-white">Login</a>
                <a href="/register" class="text-white">Register</a>
            </div>
            @endguest

        </nav>