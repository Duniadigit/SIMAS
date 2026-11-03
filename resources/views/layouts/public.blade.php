<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SIMAS') · {{ config('simas.program') }} {{ config('simas.institution') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col">
    <header class="bg-brand-900 text-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-gold-500 font-extrabold text-brand-950">S</span>
                <span class="leading-tight">
                    <span class="block text-base font-bold tracking-wide">SIMAS</span>
                    <span class="block text-[11px] text-brand-200">{{ config('simas.program') }} · {{ config('simas.institution') }}</span>
                </span>
            </a>
            <nav class="flex items-center gap-1 text-sm">
                <a href="{{ route('public.create') }}" class="rounded-lg px-3 py-2 hover:bg-white/10">Ajukan Surat</a>
                <a href="{{ route('track') }}" class="rounded-lg px-3 py-2 hover:bg-white/10">Lacak Status</a>
                @auth
                    <a href="{{ route('panel.dashboard') }}" class="ml-2 rounded-lg bg-gold-500 px-3 py-2 font-semibold text-brand-950 hover:bg-gold-400">Panel</a>
                @else
                    <a href="{{ route('login') }}" class="ml-2 rounded-lg border border-white/30 px-3 py-2 hover:bg-white/10">Masuk Staf</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="flex-1">@yield('content')</main>

    <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500">
        © {{ now()->year }} {{ config('simas.program') }} {{ config('simas.institution') }} · Sistem Informasi Manajemen Arsip Surat
    </footer>
</body>
</html>
