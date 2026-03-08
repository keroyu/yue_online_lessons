<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'Your Time Bank') }}</title>

    <!-- SEO: Meta Description -->
@isset($og)
    <meta name="description" content="{{ $og['description'] }}">
@else
    <meta name="description" content="經營者時間銀行 — 投資理財、創業實戰、自我成長線上課程平台。">
@endisset

    <!-- SEO: Canonical URL -->
    <link rel="canonical" href="{{ isset($og) ? $og['url'] : url()->current() }}">

    <!-- Open Graph -->
@isset($og)
    <meta property="og:type" content="{{ $og['type'] }}">
    <meta property="og:title" content="{{ $og['title'] }}">
    <meta property="og:description" content="{{ $og['description'] }}">
    <meta property="og:url" content="{{ $og['url'] }}">
    @if($og['image'])
    <meta property="og:image" content="{{ $og['image'] }}">
    @endif
@else
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ config('app.name', 'Your Time Bank') }}">
    <meta property="og:description" content="經營者時間銀行 — 投資理財、創業實戰、自我成長線上課程平台。">
    <meta property="og:url" content="{{ url('/') }}">
@endisset

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
@isset($og)
    <meta name="twitter:title" content="{{ $og['title'] }}">
    <meta name="twitter:description" content="{{ $og['description'] }}">
    @if($og['image'])
    <meta name="twitter:image" content="{{ $og['image'] }}">
    @endif
@endisset

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900">
    @inertia
</body>
</html>
