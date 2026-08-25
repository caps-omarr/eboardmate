<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover">
    
    <title inertia>{{ config('app.name', 'E-BoardMate') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @if(request()->is('owner*'))
        <!-- PWA Manifest & Mobile App Settings (Strictly Restricted to Landlord Owner Portal) -->
        <link rel="manifest" href="/build/manifest.webmanifest">
        <meta name="theme-color" content="#10b981">
        <link rel="apple-touch-icon" href="/favicon.png">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>