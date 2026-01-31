<x-layout title="Login">
    <section class="login-page">
        <div class="card login-card border-0 shadow-lg">
            <div class="card-body p-4 p-md-5">

                <div class="text-center mb-4">
                    <img src="{{ asset('assets/logoTransparent.png') }}" alt="Qalam Logo" class="login-logo mb-3">
                    <h5 class="fw-bold text-dark">Welcome Back</h5>
                    <p class="text-muted small">Sign in to your account to continue</p>
                </div>
                <div>
                    <h4 class="mb-0">Qalam Portal</h4>
                    <small class="text-muted">Sign in to continue</small>
                </div>
            </div>

            <x-forms.form method="POST" action="/auth/login" enctype="multipart/form-data">
                <div class="mb-3">
                    <x-forms.input label="Email" name="email" autocomplete="email" type="email"
                        placeholder="you@htu.edu" />
                </div>

                <div class="mb-3">
                    <x-forms.input label="Password" name="password" type="password" placeholder="••••••" />
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <a href="/auth/register" class="small text-decoration-none" style="color: var(--brand-primary);">
                        Create account?
                    </a>
                </div>

                <div class="d-grid">
                    <x-forms.button class="btn-primary-custom py-2">Login</x-forms.button>
                </div>
            </x-forms.form>
        </div>
        </div>
    </section>
</x-layout>
