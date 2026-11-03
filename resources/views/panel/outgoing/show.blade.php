@extends('layouts.panel')
@section('title', $letter->registration_no)
@section('heading', 'Detail Surat Keluar')
@section('content')
@php
    use App\Enums\OutgoingStatus as S;
    $user = auth()->user();
    $st = $letter->status;
    $isAdmin = $user->isAdmin();
    $canApprove = $user->canApprove();
    $open = ! in_array($st, [S::Selesai, S::Ditolak], true);
@endphp

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <section class="card card-body">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $letter->registration_no }}</p>
                    <h2 class="mt-1 text-lg font-bold text-slate-900">{{ $letter->subject }}</h2>
                    <p class="text-sm text-slate-500">{{ $letter->type->name }}</p>
                </div>
                <x-status-badge :status="$st" class="!text-sm" />
            </div>
            <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-500">Pemohon</dt><dd class="font-medium">{{ $letter->requester_name }} ({{ ucfirst($letter->requester_type) }})</dd></div>
                <div><dt class="text-slate-500">NPM / NIDN</dt><dd class="font-medium">{{ $letter->requester_identifier }}</dd></div>
                <div><dt class="text-slate-500">Kontak</dt><dd class="font-medium">{{ $letter->email ?: '—' }} · {{ $letter->phone ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Diajukan</dt><dd class="font-medium">{{ $letter->created_at->translatedFormat('d F Y H:i') }}</dd></div>
                @if ($letter->letter_number)
                    <div><dt class="text-slate-500">Nomor surat</dt><dd class="font-semibold text-emerald-700">{{ $letter->letter_number }}</dd></div>
                    <div><dt class="text-slate-500">Di-ACC</dt><dd class="font-medium">{{ $letter->approved_at?->translatedFormat('d F Y H:i') }} oleh {{ $letter->approver?->name ?? '—' }}</dd></div>
                @endif
            </dl>
            @if ($letter->details)
                <div class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-700"><span class="font-medium">Keterangan:</span> {{ $letter->details }}</div>
            @endif
            @if ($letter->review_note)
                <div class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900"><span class="font-medium">Catatan {{ $st === S::Ditolak ? 'penolakan' : 'revisi' }}:</span> {{ $letter->review_note }}</div>
            @endif

            <div class="mt-5 flex flex-wrap gap-2">
                @if ($letter->draft_path)<a class="btn-ghost" target="_blank" href="{{ route('panel.outgoing.download', [$letter, 'draft']) }}">Lihat draf PDF</a>@endif
                @if ($letter->final_path)<a class="btn-success" target="_blank" href="{{ route('panel.outgoing.download', [$letter, 'final']) }}">Surat final (PDF)</a>@endif
            </div>
        </section>

        {{-- Tindakan --}}
        @if ($open)
        <section class="card card-body space-y-5">
            <h3 class="font-semibold text-slate-900">Tindakan</h3>

            @if ($isAdmin && $st === S::Diajukan)
                <form method="POST" action="{{ route('panel.outgoing.start', $letter) }}" data-once>@csrf
                    <button class="btn-primary" type="submit">Proses permohonan ini</button>
                </form>
            @endif

            @if ($isAdmin && in_array($st, [S::SedangDibuat, S::Revisi], true))
                <form method="POST" action="{{ route('panel.outgoing.draft', $letter) }}" enctype="multipart/form-data" class="space-y-2" data-once>@csrf
                    <label class="label" for="draft">Unggah draf surat (PDF, maks {{ round(config('simas.pdf_max_kb') / 1024) }} MB)</label>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <input class="input" id="draft" type="file" name="draft" accept="application/pdf" required>
                        <button class="btn-ghost shrink-0" type="submit">Unggah</button>
                    </div>
                    @error('draft')<p class="err">{{ $message }}</p>@enderror
                </form>
                <form method="POST" action="{{ route('panel.outgoing.submit', $letter) }}" data-once data-confirm="Teruskan draf ke Kaprodi?">@csrf
                    <button class="btn-primary" type="submit" @disabled(! $letter->draft_path)>Teruskan ke Kaprodi</button>
                    @unless ($letter->draft_path)<span class="ml-2 text-xs text-slate-500">Unggah draf dulu.</span>@endunless
                </form>
            @endif

            @if ($st === S::MenungguPersetujuan)
                @if ($canApprove)
                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('panel.outgoing.approve', $letter) }}" data-once data-confirm="ACC surat ini? Nomor surat akan diterbitkan dan TTD dibubuhkan.">@csrf
                            <button class="btn-success" type="submit">ACC &amp; Terbitkan Nomor</button>
                        </form>
                    </div>
                    <form method="POST" action="{{ route('panel.outgoing.revise', $letter) }}" class="space-y-2" data-once>@csrf
                        <label class="label" for="note-rev">Catatan revisi</label>
                        <textarea class="input" id="note-rev" name="note" rows="2" required maxlength="1000"></textarea>
                        @error('note')<p class="err">{{ $message }}</p>@enderror
                        <button class="btn-ghost" type="submit">Kembalikan untuk revisi</button>
                    </form>
                @else
                    <p class="text-sm text-slate-500">Menunggu persetujuan Kaprodi.</p>
                @endif
            @endif

            <details class="rounded-lg border border-red-200 bg-red-50/50 p-3">
                <summary class="cursor-pointer text-sm font-medium text-red-700">Tolak permohonan</summary>
                <form method="POST" action="{{ route('panel.outgoing.reject', $letter) }}" class="mt-3 space-y-2" data-once data-confirm="Tolak permohonan ini?">@csrf
                    <textarea class="input" name="note" rows="2" required maxlength="1000" placeholder="Alasan penolakan (akan terlihat oleh pemohon)"></textarea>
                    <button class="btn-danger" type="submit">Tolak</button>
                </form>
            </details>
        </section>
        @endif
    </div>

    <aside class="card h-fit overflow-hidden">
        <h3 class="border-b border-slate-200 px-5 py-4 font-semibold text-slate-900">Riwayat aktivitas</h3>
        <ol class="space-y-4 p-5">
            @foreach ($letter->logs as $log)
                <li class="border-l-2 border-brand-200 pl-3">
                    <p class="text-sm text-slate-800">{{ $log->description }}</p>
                    <p class="text-xs text-slate-500">{{ $log->actor_name }} · {{ $log->created_at->translatedFormat('d M Y H:i') }}</p>
                </li>
            @endforeach
        </ol>
    </aside>
</div>
@endsection
