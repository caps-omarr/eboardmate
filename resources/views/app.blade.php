<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title inertia>{{ config('app.name', 'E-BoardMate') }}</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- PWA Manifest & Mobile App Settings -->
    <link rel="manifest" href="/build/manifest.webmanifest">
    <meta name="theme-color" content="#10b981">
    <link rel="apple-touch-icon" href="/favicon.png">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>