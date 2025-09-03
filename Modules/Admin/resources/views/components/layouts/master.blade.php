<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>Admin - {{ config('app.name', 'Laravel') }}</title>

    <link href="{{ Vite::asset('Modules/Admin/resources/css/nucleo-icons.css') }}" rel="stylesheet" />
    <link href="{{ Vite::asset('Modules/Admin/resources/css/nucleo-svg.css') }}" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet" />


    <!-- Main Styling -->
    @vite(['resources/css/tooltips.css','resources/css/perfect-scrollbar.css','resources/css/nucleo-svg.css','resources/css/nucleo-icons.css','resources/css/argon-dashboard-tailwind.min.css','resources/css/argon-dashboard.tailwind.css'])

</head>

<body class="m-0 font-sans text-base antialiased font-normal dark:bg-slate-900 leading-default bg-gray-50 text-slate-500">
    <x-admin::side-nav />
    <div class="absolute w-full bg-blue-500 dark:hidden min-h-75"></div>

    {{ $slot }}

</body>


</html>