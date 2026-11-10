@extends('layouts.panel')
@section('title', 'Arsip & Laporan')
@section('heading', 'Arsip & Laporan')
@section('content')
<div class="mb-4 inline-flex rounded-lg border border-slate-200 bg-white p-1 text-sm font-medium">
    <a href="{{ route('panel.archive.index', ['tab' => 'keluar']) }}" class="rounded-md px-4 py-1.5 {{ $tab === 'keluar' ? 'bg-brand-500 text-ink-900' : 'text-slate-600 hover:bg-slate-50' }}">Surat Keluar</a>
    <a href="{{ route('panel.archive.index', ['tab' => 'masuk']) }}" class="rounded-md px-4 py-1.5 {{ $tab === 'masuk' ? 'bg-brand-500 text-ink-900' : 'text-slate-600 hover:bg-slate-50' }}">Surat Masuk</a>
</div>

<form method="GET" class="card card-body mb-4 grid gap-3 md:grid-cols-6">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <input class="input md:col-span-2" name="q" value="{{ $f['q'] ?? '' }}" placeholder="Cari perihal / nomor / nama…">
    @if ($tab === 'keluar')
        <select class="input" name="type"><option value="">Semua jenis</option>
            @foreach ($types as $id => $name)<option value="{{ $id }}" @selected((string) ($f['type'] ?? '') === (string) $id)>{{ $name }}</option>@endforeach
        </select>
    @else
        <select class="input" name="category"><option value="">Semua kategori</option>
            @foreach ($categories as $c)<option value="{{ $c }}" @selected(($f['category'] ?? '') === $c)>{{ $c }}</option>@endforeach
        </select>
    @endif
    <select class="input" name="status"><option value="">Semua status</option>
        @foreach ($statuses as $v => $label)<option value="{{ $v }}" @selected(($f['status'] ?? '') === $v)>{{ $label }}</option>@endforeach
    </select>
    <input class="input" type="date" name="from" value="{{ $f['from'] ?? '' }}" title="Dari tanggal">
    <input class="input" type="date" name="to" value="{{ $f['to'] ?? '' }}" title="Sampai tanggal">
    <div class="flex gap-2 md:col-span-6">
        <button class="btn-primary" type="submit">Cari</button>
        <a class="btn-ghost" href="{{ route('panel.archive.index', ['tab' => $tab]) }}">Reset</a>
        <a class="btn-ghost ml-auto" target="_blank" href="{{ route('panel.archive.print', request()->query()) }}">Cetak laporan</a>
    </div>
</form>

<div class="card overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50"><tr>
            @if ($tab === 'keluar')
                <th class="th">Nomor</th><th class="th">Perihal</th><th class="th">Pemohon</th><th class="th">Jenis</th><th class="th">Status</th><th class="th">Tanggal</th>
            @else
                <th class="th">Agenda</th><th class="th">Perihal</th><th class="th">Asal</th><th class="th">Kategori</th><th class="th">Status</th><th class="th">Diterima</th>
            @endif
        </tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($rows as $r)
                @if ($tab === 'keluar')
                    <tr class="hover:bg-slate-50">
                        <td class="td whitespace-nowrap"><a class="font-semibold text-brand-700 hover:underline" href="{{ route('panel.outgoing.show', $r) }}">{{ $r->letter_number ?: $r->registration_no }}</a></td>
                        <td class="td max-w-xs"><span class="line-clamp-2">{{ $r->subject }}</span></td>
                        <td class="td">{{ $r->requester_name }}</td>
                        <td class="td text-slate-500">{{ $r->type->name }}</td>
                        <td class="td"><x-status-badge :status="$r->status" /></td>
                        <td class="td whitespace-nowrap text-slate-500">{{ ($r->letter_date ?? $r->created_at)->translatedFormat('d M Y') }}</td>
                    </tr>
                @else
                    <tr class="hover:bg-slate-50">
                        <td class="td whitespace-nowrap"><a class="font-semibold text-brand-700 hover:underline" href="{{ route('panel.incoming.show', $r) }}">{{ $r->agenda_no }}</a><div class="text-xs text-slate-500">{{ $r->letter_number }}</div></td>
                        <td class="td max-w-xs"><span class="line-clamp-2">{{ $r->subject }}</span></td>
                        <td class="td">{{ $r->origin }}</td>
                        <td class="td text-slate-500">{{ $r->category ?: '—' }}</td>
                        <td class="td"><x-status-badge :status="$r->status" /></td>
                        <td class="td whitespace-nowrap text-slate-500">{{ $r->received_date->translatedFormat('d M Y') }}</td>
                    </tr>
                @endif
            @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">Tidak ada arsip yang cocok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $rows->links() }}</div>
@endsection
