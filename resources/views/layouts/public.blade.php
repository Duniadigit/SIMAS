<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SIMAS') · {{ config('simas.program') }} {{ config('simas.institution') }}</title>
    @include('partials.fonts')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-white">
    <header class="bg-brand-500 text-ink-900">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-x-6 gap-y-2 px-4 py-3">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                @include('partials.logo')
                <span class="text-xs font-bold uppercase leading-tight tracking-wide">
                    SIMAS<span class="block font-semibold">{{ config('simas.program') }}</span>
                </span>
            </a>
            <nav class="flex flex-wrap items-center gap-x-5 gap-y-1 text-sm font-medium">
                <a href="{{ route('public.create') }}" class="hover:text-white">Ajukan Surat</a>
                <a href="{{ route('track') }}" class="hover:text-white">Lacak Status</a>
                @auth
                    <a href="{{ route('panel.dashboard') }}" class="rounded-full bg-ink-900 px-4 py-1.5 text-white hover:bg-ink-800">Panel</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-full bg-ink-900 px-4 py-1.5 text-white hover:bg-ink-800">Masuk Staf</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="flex-1">@yield('content')</main>

    <footer class="bg-brand-500 text-ink-900">
        <div class="mx-auto flex max-w-5xl flex-col gap-2 px-4 py-4 text-sm sm:flex-row sm:items-center sm:justify-between">
            <p>FISIP | SIMAS {{ config('simas.program') }}</p>
            <p class="max-w-md sm:text-right">{{ config('simas.address') }}</p>
        </div>
    </footer>
</body>
</html>
