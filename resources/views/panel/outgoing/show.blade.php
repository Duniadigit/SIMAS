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
    $editing = $isAdmin && in_array($st, [S::SedangDibuat, S::Revisi], true);
    $previewUrl = route('panel.outgoing.preview', $letter).'?v='.$letter->updated_at->timestamp;
@endphp

<div class="space-y-6">
    {{-- Ringkasan --}}
    <section class="card card-body">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $letter->registration_no }}</p>
                <h2 class="mt-1 text-lg font-bold text-slate-900">{{ $letter->subject }}</h2>
                <p class="text-sm text-slate-500">{{ $letter->type->name }}</p>
            </div>
            <x-status-badge :status="$st" class="!text-sm" />
        </div>
        <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div><dt class="text-slate-500">Pemohon</dt><dd class="font-medium">{{ $letter->requester_name }} ({{ ucfirst($letter->requester_type) }})</dd></div>
            <div><dt class="text-slate-500">NPM / NIDN</dt><dd class="font-medium">{{ $letter->requester_identifier }}</dd></div>
            <div><dt class="text-slate-500">Kontak</dt><dd class="font-medium">{{ $letter->email ?: '—' }} · {{ $letter->phone ?: '—' }}</dd></div>
            <div><dt class="text-slate-500">Diajukan</dt><dd class="font-medium">{{ $letter->created_at->translatedFormat('d F Y H:i') }}</dd></div>
            @if ($letter->letter_number)
                <div><dt class="text-slate-500">Nomor surat</dt><dd class="font-semibold text-emerald-700">{{ $letter->letter_number }}</dd></div>
            @endif
            @if ($letter->approved_at)
                <div><dt class="text-slate-500">Di-ACC</dt><dd class="font-medium">{{ $letter->approved_at->translatedFormat('d F Y H:i') }} oleh {{ $letter->approver?->name ?? '—' }}</dd></div>
            @endif
        </dl>
        @if ($letter->details)
            <div class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-700"><span class="font-medium">Keterangan pemohon:</span> {{ $letter->details }}</div>
        @endif
        @if ($letter->review_note)
            <div class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900"><span class="font-medium">Catatan {{ $st === S::Ditolak ? 'penolakan' : 'revisi' }}:</span> {{ $letter->review_note }}</div>
        @endif
    </section>

    <div class="grid items-start gap-6 xl:grid-cols-2">
        {{-- Kolom kiri: tindakan + editor + riwayat --}}
        <div class="space-y-6">
            @if ($open)
            <section class="card card-body space-y-5">
                <h3 class="font-semibold text-slate-900">Tindakan</h3>

                @if ($isAdmin && $st === S::Diajukan)
                    <form method="POST" action="{{ route('panel.outgoing.start', $letter) }}" data-once>@csrf
                        <button class="btn-primary" type="submit">Proses permohonan ini</button>
                    </form>
                @endif

                @if ($st === S::MenungguPersetujuan)
                    @if ($canApprove)
                        <p class="text-sm text-slate-600">Periksa pratinjau surat di samping, lalu putuskan.</p>
                        <form method="POST" action="{{ route('panel.outgoing.approve', $letter) }}" data-once data-confirm="ACC surat ini? Nomor surat dipastikan dan TTD dibubuhkan.">@csrf
                            <button class="btn-success" type="submit">ACC &amp; Terbitkan Surat</button>
                        </form>
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

                @if ($editing)
                    <form method="POST" action="{{ route('panel.outgoing.submit', $letter) }}" data-once data-confirm="Teruskan surat ke Kaprodi? Pastikan perubahan sudah disimpan.">@csrf
                        <button class="btn-primary" type="submit" @disabled(! $canPreview)>Teruskan ke Kaprodi</button>
                        <span class="ml-2 text-xs text-slate-500">{{ $canPreview ? 'Yang diteruskan adalah isi yang sudah disimpan.' : 'Susun surat atau unggah draf dulu.' }}</span>
                    </form>
                @endif

                <details class="rounded-lg border border-red-200 bg-red-50/50 p-3">
                    <summary class="cursor-pointer text-sm font-medium text-red-700">Tolak permohonan</summary>
                    <form method="POST" action="{{ route('panel.outgoing.reject', $letter) }}" class="mt-3 space-y-2" data-once data-confirm="Tolak permohonan ini?">@csrf
                        <textarea class="input" name="note" rows="2" required maxlength="1000" placeholder="Alasan penolakan (terlihat oleh pemohon)"></textarea>
                        <button class="btn-danger" type="submit">Tolak</button>
                    </form>
                </details>
            </section>
            @endif

            {{-- Editor template --}}
            @if ($editing)
            <section class="card">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-5 py-4">
                    <h3 class="font-semibold text-slate-900">Susun surat (template)</h3>
                    @if ($letter->letter_data)
                        <a class="text-xs font-medium text-brand-700 hover:underline" href="{{ route('panel.outgoing.docx', $letter) }}">Unduh .docx</a>
                    @endif
                </div>
                <form method="POST" action="{{ route('panel.outgoing.template', $letter) }}" class="space-y-4 p-5" data-once>@csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="number">Nomor surat</label>
                            <input class="input" id="number" name="number" value="{{ old('number', $form['number']) }}" maxlength="100" placeholder="Kosongkan = otomatis saat ACC">
                            @error('number')<p class="err">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="label" for="title">Judul <span class="font-normal text-slate-400">(khusus surat keterangan)</span></label>
                            <input class="input" id="title" name="title" value="{{ old('title', $form['title']) }}" maxlength="150" placeholder="Kosongkan untuk surat dinas biasa">
                            @error('title')<p class="err">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="place">Tempat</label>
                            <input class="input" id="place" name="place" value="{{ old('place', $form['place']) }}" required maxlength="60">
                            @error('place')<p class="err">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="label" for="date">Titimangsa (tanggal surat)</label>
                            <input class="input" id="date" type="date" name="date" value="{{ old('date', $form['date']) }}" required>
                            @error('date')<p class="err">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="subject">Perihal</label>
                            <input class="input" id="subject" name="subject" value="{{ old('subject', $form['subject']) }}" required maxlength="255">
                            @error('subject')<p class="err">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="label" for="attachment">Lampiran</label>
                            <input class="input" id="attachment" name="attachment" value="{{ old('attachment', $form['attachment']) }}" maxlength="100" placeholder="mis. 1 (satu) berkas">
                            @error('attachment')<p class="err">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="label" for="recipient">Tujuan surat <span class="font-normal text-slate-400">(satu baris per baris; kosongkan untuk surat keterangan)</span></label>
                        <textarea class="input" id="recipient" name="recipient" rows="3" maxlength="500" placeholder="Kepala Dinas ...&#10;Kota Tasikmalaya&#10;di Tempat">{{ old('recipient', $form['recipient']) }}</textarea>
                        @error('recipient')<p class="err">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="body">Redaksi utama surat</label>
                        <textarea class="input font-mono" id="body" name="body" rows="14" required maxlength="10000">{{ old('body', $form['body']) }}</textarea>
                        <p class="mt-1 text-xs text-slate-500">Pisahkan paragraf dengan baris kosong. Baris berpola <code>Nama : Budi</code> (minimal dua baris berurutan) otomatis dirapikan menjadi tabel.</p>
                        @error('body')<p class="err">{{ $message }}</p>@enderror
                    </div>
                    <button class="btn-primary" type="submit">Simpan &amp; perbarui pratinjau</button>
                </form>

                <details class="border-t border-slate-200 px-5 py-4">
                    <summary class="cursor-pointer text-sm font-medium text-slate-700">Atau unggah draf PDF sendiri</summary>
                    <form method="POST" action="{{ route('panel.outgoing.draft', $letter) }}" enctype="multipart/form-data" class="mt-3 space-y-2" data-once
                          @if ($letter->letter_data) data-confirm="Mengunggah PDF akan menggantikan isian template yang tersimpan. Lanjutkan?" @endif>@csrf
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <input class="input" type="file" name="draft" accept="application/pdf" required>
                            <button class="btn-ghost shrink-0" type="submit">Unggah</button>
                        </div>
                        <p class="text-xs text-slate-500">PDF maks {{ round(config('simas.pdf_max_kb') / 1024) }} MB. TTD dibubuhkan di halaman terakhir saat ACC.</p>
                        @error('draft')<p class="err">{{ $message }}</p>@enderror
                    </form>
                </details>
            </section>
            @endif

            <section class="card overflow-hidden">
                <h3 class="border-b border-slate-200 px-5 py-4 font-semibold text-slate-900">Riwayat aktivitas</h3>
                <ol class="space-y-4 p-5">
                    @foreach ($letter->logs as $log)
                        <li class="border-l-2 border-brand-200 pl-3">
                            <p class="text-sm text-slate-800">{{ $log->description }}</p>
                            <p class="text-xs text-slate-500">{{ $log->actor_name }} · {{ $log->created_at->translatedFormat('d M Y H:i') }}</p>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>

        {{-- Kolom kanan: pratinjau langsung --}}
        <section class="card overflow-hidden xl:sticky xl:top-20">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-5 py-3">
                <h3 class="font-semibold text-slate-900">
                    Pratinjau {{ $st === S::Selesai ? 'surat final' : 'draf' }}
                </h3>
                @if ($canPreview)
                    <a class="text-xs font-medium text-brand-700 hover:underline" target="_blank" href="{{ $previewUrl }}">Buka layar penuh ↗</a>
                @endif
            </div>
            @if ($canPreview)
                <iframe src="{{ $previewUrl }}#view=FitH" title="Pratinjau surat" class="h-[78vh] w-full bg-slate-100"></iframe>
                <p class="border-t border-slate-200 px-5 py-2 text-xs text-slate-500">
                    Pratinjau tidak tampil di perangkat Anda? <a class="text-brand-700 underline" target="_blank" href="{{ $previewUrl }}">Buka di tab baru</a>.
                </p>
            @else
                <div class="grid h-64 place-items-center px-6 text-center text-sm text-slate-500">
                    Belum ada draf. Susun surat dengan template atau unggah PDF, pratinjau akan muncul di sini.
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
