@extends('layouts.public')
@section('title', 'Beranda')
@section('content')
<section class="border-b border-slate-200">
    <div class="mx-auto max-w-5xl px-4 py-12 lg:py-16">
        <p class="text-sm font-semibold uppercase tracking-widest text-brand-700">Sistem Informasi Manajemen Arsip Surat</p>
        <h1 class="mt-3 max-w-2xl text-3xl font-bold leading-tight text-ink-900 sm:text-4xl">Ajukan surat dan pantau prosesnya, <span class="bg-brand-500 px-1">tanpa antre</span> dan tanpa tanya via WhatsApp.</h1>
        <p class="mt-4 max-w-xl text-ink-700">Layanan surat {{ config('simas.program') }} {{ config('simas.institution') }}: surat keterangan aktif, pengantar riset, dan lainnya.</p>

        <form action="{{ route('track') }}" method="GET" class="mt-8 flex max-w-xl flex-col gap-2 sm:flex-row">
            <input name="q" class="input py-3" placeholder="Nomor registrasi atau NPM, mis. SK-261102-7KQ4M" maxlength="40" required>
            <button class="btn-primary px-6 py-3" type="submit">Lacak</button>
        </form>
        <a href="{{ route('public.create') }}" class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-ink-900 underline decoration-brand-500 decoration-2 underline-offset-4 hover:text-accent-500">Belum punya nomor? Ajukan surat baru →</a>
    </div>
</section>

<section class="mx-auto mt-8 max-w-5xl px-4">
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Permohonan surat bulan ini" :value="$stats['outgoing_month']" tone="brand" />
        <x-stat-card label="Surat selesai bulan ini" :value="$stats['outgoing_done_month']" tone="emerald" />
        <x-stat-card label="Surat masuk bulan ini" :value="$stats['incoming_month']" tone="blue" />
    </div>
</section>

<section class="mx-auto max-w-5xl px-4 py-12">
    <h2 class="text-xl font-bold text-slate-900">Alur permohonan surat</h2>
    <ol class="mt-6 grid gap-4 sm:grid-cols-4">
        @foreach ([['1', 'Pengajuan', 'Isi formulir permohonan secara online.'], ['2', 'Sedang Dibuat', 'Admin prodi menyiapkan draf surat.'], ['3', 'Menunggu Tanda Tangan', 'Draf diteruskan ke Kaprodi.'], ['4', 'Selesai', 'Surat bernomor dan siap diunduh.']] as [$n, $t, $d])
            <li class="card card-body">
                <span class="grid h-8 w-8 place-items-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">{{ $n }}</span>
                <p class="mt-3 font-semibold text-slate-900">{{ $t }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $d }}</p>
            </li>
        @endforeach
    </ol>
</section>
@endsection
