<x-layout title="Login">
    <section class="login-page">
        <div class="card login-card">
            <div class="d-flex align-items-center mb-3">
                <div class="logo-circle me-3">
                    <img src="{{ asset('assets/logo.png') }}" alt="HTU Logo">
                </div>
                <div>
                    <h4 class="mb-0">HTU Portal</h4>
                    <small class="text-muted">Sign in to continue</small>
                </div>
            </div>

            <x-forms.form method="POST" action="/main/auth/login" enctype="multipart/form-data">
                <div class="mb-3">
                    <x-forms.input label="Email" name="email" autocomplete="email" type="email"
                                   placeholder="you@htu.edu"/>
                </div>

                <div class="mb-3">
                    <x-forms.input label="password" name="password" type="password" placeholder="••••••"/>
                </div>

                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <a href="/main/auth/register" class="small text-muted">Don't have an account?</a>
                    </div>
                    <a href="#" class="small text-muted">Forgot password?</a>
                </div>

                <div class="d-grid">
                    <x-forms.button>Login</x-forms.button>
                </div>
            </x-forms.form>
        </div>
    </section>
</x-layout>
