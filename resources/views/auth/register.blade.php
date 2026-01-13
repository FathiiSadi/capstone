<x-layout title="Register">
    <section class="login-page">
        <div class="card login-card border-0 shadow-lg">
            <div class="card-body p-4 p-md-5">
                
                <div class="text-center mb-4">
                    <img src="{{ asset('assets/logoTransparent.png') }}" 
                         alt="Qalam Logo" 
                         class="login-logo mb-3">
                    <h5 class="fw-bold text-dark">Create Account</h5>
                    <p class="text-muted small">Join the academic portal today</p>
                </div>
                <div>
                    <h4 class="mb-0">Qalam Portal</h4>
                    <small class="text-muted">Sign in to continue</small>
                </div>
            </div>

                <x-forms.form method="POST" action="/auth/register" enctype="multipart/form-data" autocomplete="on">
                    
                    <div class="mb-3">
                        <x-forms.input label="Full Name" name="name" autocomplete="name" 
                                       placeholder="Your Name"/>
                    </div>

                    <div class="mb-3">
                        <x-forms.input label="Email Address" name="email" autocomplete="email" 
                                       placeholder="example@htu.edu.jo"/>
                    </div>

                    <div class="mb-3">
                        <x-forms.input label="Password" name="password" type="password" 
                                       placeholder="••••••••"/>
                    </div>

                    <div class="mb-3">
                        <x-forms.input label="Confirm Password" name="password_confirmation" type="password"
                                       placeholder="••••••••"/>
                    </div>

                    <div class="d-flex justify-content-end align-items-center mb-4">
                        <a href="/auth/login" class="small text-decoration-none" style="color: var(--brand-primary);">
                            Already have an account? Login
                        </a>
                    </div>

                    <div class="d-grid">
                        <x-forms.button class="btn-primary-custom py-2">Create Account</x-forms.button>
                    </div>

                </x-forms.form>
            </div>
        </div>
    </section>
</x-layout>