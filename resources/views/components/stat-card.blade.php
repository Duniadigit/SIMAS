@props(['label', 'value', 'hint' => null, 'tone' => 'brand'])
@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-700',
        'amber' => 'bg-amber-50 text-amber-700',
        'emerald' => 'bg-emerald-50 text-emerald-700',
        'blue' => 'bg-sky-50 text-sky-700',
    ];
@endphp
<div class="card card-body">
    <div class="flex items-start justify-between">
        <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
        <span class="rounded-lg px-2 py-1 text-xs font-semibold {{ $tones[$tone] ?? $tones['brand'] }}">{{ now()->translatedFormat('M Y') }}</span>
    </div>
    <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($value, 0, ',', '.') }}</p>
    @if ($hint)<p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>@endif
</div>
