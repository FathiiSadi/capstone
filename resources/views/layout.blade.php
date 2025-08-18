<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    @vite(['resources/css/app.css', 'resources/css/bootstrap.min.css', 'resources/css/bootstrap-icons.css', 'resources/css/templatemo-topic-listing.css'])
</head>

<body id="top">

    <main>
        <x-nav-bar.nav></x-nav-bar.nav>
        <x-hero></x-hero>
        <x-featured></x-featured>
        <x-timeline></x-timeline>

        <x-footer></x-footer>

    </main>

</body>

</html>