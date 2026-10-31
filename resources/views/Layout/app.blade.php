<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'My Website')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('afterglow/afterglow.css') }}">
    <link rel="stylesheet" href="{{ asset('afterglow/soundtrack-spotlight.css') }}">
    <link rel="stylesheet" href="{{ asset('afterglow/afterglow-worlds-final.css') }}?v=1">
<link rel="stylesheet" href="{{ asset('afterglow/auth-worlds-motion.css') }}?v=6">
</head>

<body
    class="min-h-screen bg-center bg-fixed bg-no-repeat"
    style="
        background-image: url('{{ asset('images/background.png') }}');
        background-size: cover;
    "
>
<x-navbar :page="$page ?? ''" />


    <main>
        @yield('content')
    </main>
<script defer src="{{ asset('afterglow/afterglow.js') }}"></script>
<script defer src="{{ asset('afterglow/auth-worlds-motion.js') }}?v=6"></script>

</body>

</html>
