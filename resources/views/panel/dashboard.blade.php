@extends('layouts.panel')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('content')
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat-card label="Surat keluar (permohonan)" :value="$stats['outgoing_month']" :hint="$stats['outgoing_done_month'].' selesai'" tone="brand" />
    <x-stat-card label="Surat masuk" :value="$stats['incoming_month']" tone="blue" />
    <x-stat-card label="Perlu tindakan (keluar)" :value="$stats['pending_outgoing']" :hint="$stats['being_made'].' sedang dibuat · '.$stats['awaiting_approval'].' menunggu ACC'" tone="amber" />
    <x-stat-card label="Menunggu disposisi" :value="$stats['awaiting_disposition']" hint="Surat masuk belum didisposisi" tone="amber" />
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <section class="card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold text-slate-900">Pengingat: surat keluar</h2>
            <a href="{{ route('panel.outgoing.index') }}" class="text-xs font-medium text-brand-700 hover:underline">Lihat semua</a>
        </div>
        <ul class="divide-y divide-slate-100">
            @forelse ($pendingOutgoing as $l)
                <li>
                    <a href="{{ route('panel.outgoing.show', $l) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-slate-50">
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-medium text-slate-900">{{ $l->subject }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $l->requester_name }} · {{ $l->type->name }} · {{ $l->created_at->diffForHumans() }}</span>
                        </span>
                        <x-status-badge :status="$l->status" />
                    </a>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-slate-500">Tidak ada surat yang menunggu. 🎉</li>
            @endforelse
        </ul>
    </section>

    <section class="card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold text-slate-900">Pengingat: menunggu disposisi</h2>
            <a href="{{ route('panel.incoming.index', ['status' => 'diterima']) }}" class="text-xs font-medium text-brand-700 hover:underline">Lihat semua</a>
        </div>
        <ul class="divide-y divide-slate-100">
            @forelse ($awaitingDisposition as $l)
                <li>
                    <a href="{{ route('panel.incoming.show', $l) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-slate-50">
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-medium text-slate-900">{{ $l->subject }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $l->origin }} · diterima {{ $l->received_date->translatedFormat('d M Y') }}</span>
                        </span>
                        <x-status-badge :status="$l->status" />
                    </a>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-slate-500">Semua surat masuk sudah didisposisi.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
