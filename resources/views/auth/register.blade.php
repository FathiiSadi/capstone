<x-layout title="Register">
    <section class="login-page">
        <div class="card login-card">
            <div class="d-flex align-items-center mb-3">
                <div class="logo-circle me-3">
                    <img src="{{ asset('assets/logo.png') }}" alt="HTU Logo">
                </div>
                <div>
                    <h4 class="mb-0">Qalam Portal</h4>
                    <small class="text-muted">Sign in to continue</small>
                </div>
            </div>

            <x-forms.form method="POST" action="/auth/register" enctype="multipart/form-data" autocomplete="on">
                <div class="mb-3">
                    <x-forms.input label="Your Name" name="name" autocomplete="name" placeholder="Fathi Al-Sadi"/>
                </div>

                <div class="mb-3">
                    <x-forms.input label="Email" name="email" autocomplete="email" placeholder="example@htu.edu.jo"/>
                </div>
                <div class="mb-3">
                    <x-forms.input label="Password" name="password" type="password" placeholder="*******"/>
                </div>

                <div class="mb-3">
                    <x-forms.input label="Confirm Password" name="password_confirmation" type="password"
                                   placeholder="*******"/>
                </div>

                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <a href="/auth/login" class="small text-muted">Already have an account?</a>
                    </div>
                    <a href="#" class="small text-muted">Forgot password?</a>
                </div>

                <div class="d-grid">
                    <x-forms.button>Create Account</x-forms.button>
                </div>
            </x-forms.form>
        </div>
    </section>
</x-layout>
