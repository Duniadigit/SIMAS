@extends('layouts.public')
@section('title', 'Lacak Status')
@section('content')
@php $steps = ['Diajukan', 'Sedang Dibuat', 'Menunggu TTD', 'Selesai']; @endphp
<div class="mx-auto max-w-3xl px-4 py-10">
    <h1 class="text-2xl font-bold text-slate-900">Lacak Status Surat</h1>
    <p class="mt-1 text-sm text-slate-500">Masukkan nomor registrasi atau NPM/NIDN Anda.</p>

    <form action="{{ route('track') }}" method="GET" class="mt-5 flex flex-col gap-2 sm:flex-row">
        <input name="q" value="{{ $q }}" class="input" placeholder="SK-261102-7KQ4M atau NPM" maxlength="40" required>
        <button class="btn-primary" type="submit">Cari</button>
    </form>

    <div class="mt-6"><x-alerts /></div>

    @if ($letters !== null)
        @forelse ($letters as $l)
            @php $step = $l->status->step(); @endphp
            <article class="card card-body mt-4">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $l->registration_no }}</p>
                        <h2 class="mt-0.5 font-semibold text-slate-900">{{ $l->subject }}</h2>
                        <p class="text-sm text-slate-500">{{ $l->type->name }} · diajukan {{ $l->created_at->translatedFormat('d M Y') }}</p>
                    </div>
                    <x-status-badge :status="$l->status" />
                </div>

                @if ($step >= 0)
                    <ol class="mt-5 grid grid-cols-4 gap-2">
                        @foreach ($steps as $i => $label)
                            <li>
                                <div class="h-1.5 rounded-full {{ $i <= $step ? 'bg-brand-500' : 'bg-slate-200' }}"></div>
                                <p class="mt-1.5 text-[11px] leading-tight {{ $i <= $step ? 'font-semibold text-brand-700' : 'text-slate-400' }}">{{ $label }}</p>
                            </li>
                        @endforeach
                    </ol>
                @endif

                @if ($l->review_note && in_array($l->status->value, ['ditolak', 'revisi'], true))
                    <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900"><strong>Catatan:</strong> {{ $l->review_note }}</p>
                @endif

                @if ($l->download_url)
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <a href="{{ $l->download_url }}" class="btn-success">Unduh Surat (PDF)</a>
                        <span class="text-xs text-slate-500">Nomor surat: {{ $l->letter_number }}</span>
                    </div>
                @endif
            </article>
        @empty
            <div class="card card-body mt-4 text-center text-sm text-slate-500">Tidak ditemukan permohonan dengan kata kunci “{{ $q }}”.</div>
        @endforelse
    @endif
</div>
@endsection
