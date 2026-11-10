@php
    $user = auth()->user();
    $nav = [
        ['panel.dashboard', 'Dashboard', 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10', 'panel.dashboard'],
        ['panel.outgoing.index', 'Surat Keluar', 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8', 'panel.outgoing.*'],
        ['panel.incoming.index', 'Surat Masuk', 'M3 8l9 6 9-6M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z', 'panel.incoming.*'],
        ['panel.archive.index', 'Arsip & Laporan', 'M20 7H4a1 1 0 00-1 1v3h18V8a1 1 0 00-1-1zM5 11v8a1 1 0 001 1h12a1 1 0 001-1v-8M10 15h4', 'panel.archive.*'],
        ['panel.logs.index', 'Log Aktivitas', 'M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z', 'panel.logs.*'],
        ['panel.profile', 'Profil & TTD', 'M15 7a3 3 0 11-6 0 3 3 0 016 0zM5 20a7 7 0 0114 0', 'panel.profile*'],
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Panel') · SIMAS</title>
    @include('partials.fonts')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="min-h-screen lg:flex">
    {{-- Sidebar --}}
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 hidden w-64 shrink-0 flex-col bg-ink-900 text-ink-200 lg:static lg:flex">
        <div class="flex items-center gap-3 px-5 py-5">
            @include('partials.logo')
            <span class="leading-tight">
                <span class="block font-bold tracking-wide text-white">SIMAS</span>
                <span class="block text-[11px] text-ink-300">{{ config('simas.program') }}</span>
            </span>
        </div>
        <nav class="flex-1 space-y-1 px-3 py-2">
            @foreach ($nav as [$route, $label, $icon, $pattern])
                <a href="{{ route($route) }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs($pattern) ? 'bg-brand-500 text-ink-900' : 'hover:bg-white/10' }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                    {{ $label }}
                </a>
            @endforeach
        </nav>
        <div class="border-t border-white/10 p-4">
            <p class="truncate text-sm font-semibold text-white">{{ $user->name }}</p>
            <p class="text-xs text-ink-300">{{ $user->role->label() }}</p>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button class="w-full rounded-lg border border-white/20 px-3 py-2 text-xs font-semibold hover:bg-white/10">Keluar</button>
            </form>
        </div>
    </aside>
    <div id="backdrop" class="fixed inset-0 z-30 hidden bg-black/40 lg:hidden"></div>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-slate-200 bg-white/90 px-4 py-3 backdrop-blur lg:px-8">
            <button id="menu-btn" class="rounded-lg p-2 hover:bg-slate-100 lg:hidden" aria-label="Menu">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h1 class="text-lg font-bold text-slate-900">@yield('heading')</h1>
            <div class="ml-auto"><a href="{{ route('home') }}" class="text-xs font-medium text-brand-700 hover:underline">Halaman publik ↗</a></div>
        </header>

        <main class="flex-1 px-4 py-6 lg:px-8">
            <x-alerts />
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
