<x-layout>
    <x-page-heading>Register</x-page-heading>

    <x-forms.form method="POST" action="/register" enctype="multipart/form-data" autocomplete="on">
        <x-forms.input label="Your Name" name="name" autocomplete="name"/>
        <x-forms.input label="Email" name="email" autocomplete="email"/>
        <x-forms.input label="Password" name="password" type="password"/>
        <x-forms.input label="Confirm Password" name="password_confirmation" type="password" />

        <x-forms.divider />




        <x-forms.button>Create Account</x-forms.button>
    </x-forms.form>
</x-layout>
