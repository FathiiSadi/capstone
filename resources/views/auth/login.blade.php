<x-layout>
    <x-page-heading>Login</x-page-heading>

    <x-forms.form method="POST" action="/login" enctype="multipart/form-data">
        <x-forms.input label="Email" name="email" autocomplete="email"/>
        <x-forms.input label="Password" name="password" type="password" autocomplete="current-password"/>
        <x-forms.button>Login</x-forms.button>
    </x-forms.form>
</x-layout>
