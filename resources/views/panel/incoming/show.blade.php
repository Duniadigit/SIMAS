@extends('layouts.panel')
@section('title', $letter->agenda_no)
@section('heading', 'Detail Surat Masuk')
@section('content')
@php
    use App\Enums\IncomingStatus as S;
    $user = auth()->user();
    $open = $letter->status !== S::Selesai;
@endphp
<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <section class="card card-body">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $letter->agenda_no }}</p>
                    <h2 class="mt-1 text-lg font-bold text-slate-900">{{ $letter->subject }}</h2>
                    <p class="text-sm text-slate-500">{{ $letter->origin }}</p>
                </div>
                <x-status-badge :status="$letter->status" class="!text-sm" />
            </div>
            <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-500">Nomor surat</dt><dd class="font-medium">{{ $letter->letter_number }}</dd></div>
                <div><dt class="text-slate-500">Kategori</dt><dd class="font-medium">{{ $letter->category ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Tanggal surat</dt><dd class="font-medium">{{ $letter->letter_date->translatedFormat('d F Y') }}</dd></div>
                <div><dt class="text-slate-500">Diterima</dt><dd class="font-medium">{{ $letter->received_date->translatedFormat('d F Y') }} · dicatat {{ $letter->creator?->name ?? '—' }}</dd></div>
            </dl>
            <details class="mt-5 rounded-lg border border-slate-200" open>
                <summary class="cursor-pointer px-4 py-2 text-sm font-medium text-slate-700">Pratinjau scan surat</summary>
                <iframe src="{{ route('panel.incoming.download', $letter) }}#view=FitH" title="Scan surat" class="h-[70vh] w-full bg-slate-100"></iframe>
            </details>
            <div class="mt-4 flex flex-wrap gap-2">
                <a class="btn-ghost" target="_blank" href="{{ route('panel.incoming.download', $letter) }}">Buka layar penuh</a>
                @if ($open && $user->isAdmin())
                    <form method="POST" action="{{ route('panel.incoming.finish', $letter) }}" data-once data-confirm="Tandai surat ini selesai?">@csrf
                        <button class="btn-ghost" type="submit">Tandai selesai</button>
                    </form>
                @endif
            </div>
        </section>

        <section class="card overflow-hidden">
            <h3 class="border-b border-slate-200 px-5 py-4 font-semibold text-slate-900">Lembar disposisi</h3>
            <ul class="divide-y divide-slate-100">
                @forelse ($letter->dispositions as $d)
                    <li class="space-y-2 px-5 py-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Kepada: {{ $d->assignee }}</p>
                                <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $d->instruction }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $d->created_at->translatedFormat('d M Y') }}
                                    @if ($d->deadline) · batas {{ $d->deadline->translatedFormat('d M Y') }}@endif
                                </p>
                            </div>
                            @if ($d->completed_at)
                                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">Selesai {{ $d->completed_at->translatedFormat('d M Y') }}</span>
                            @endif
                        </div>
                        @if ($d->completed_at && $d->completion_note)
                            <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">Catatan: {{ $d->completion_note }}</p>
                        @elseif (! $d->completed_at)
                            <form method="POST" action="{{ route('panel.disposition.complete', $d) }}" class="flex flex-col gap-2 sm:flex-row" data-once>@csrf
                                <input class="input" name="completion_note" placeholder="Catatan tindak lanjut (opsional)" maxlength="1000">
                                <button class="btn-success shrink-0" type="submit">Tandai selesai</button>
                            </form>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-slate-500">Belum ada disposisi.</li>
                @endforelse
            </ul>

            @if ($open && $user->isKaprodi())
                <form method="POST" action="{{ route('panel.incoming.dispose', $letter) }}" class="space-y-3 border-t border-slate-200 bg-slate-50 p-5" data-once>@csrf
                    <h4 class="text-sm font-semibold text-slate-900">Buat disposisi baru</h4>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="label" for="assignee">Ditujukan kepada (dosen/staf)</label>
                            <input class="input" id="assignee" name="assignee" value="{{ old('assignee') }}" required maxlength="150">
                            @error('assignee')<p class="err">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="label" for="deadline">Batas waktu</label>
                            <input class="input" id="deadline" type="date" name="deadline" value="{{ old('deadline') }}" min="{{ now()->toDateString() }}">
                            @error('deadline')<p class="err">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="label" for="instruction">Instruksi tindak lanjut</label>
                        <textarea class="input" id="instruction" name="instruction" rows="3" required maxlength="2000">{{ old('instruction') }}</textarea>
                        @error('instruction')<p class="err">{{ $message }}</p>@enderror
                    </div>
                    <button class="btn-primary" type="submit">Simpan disposisi</button>
                </form>
            @endif
        </section>
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
