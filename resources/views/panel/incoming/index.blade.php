@extends('layouts.panel')
@section('title', 'Surat Masuk')
@section('heading', 'Surat Masuk')
@section('content')
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <form method="GET" class="grid flex-1 gap-3 sm:grid-cols-[1fr_200px_auto]">
        <input class="input" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nomor, asal, perihal…">
        <select class="input" name="status">
            <option value="">Semua status</option>
            @foreach (\App\Enums\IncomingStatus::options() as $v => $label)
                <option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn-primary" type="submit">Filter</button>
    </form>
    @if (auth()->user()->isAdmin())
        <a href="{{ route('panel.incoming.create') }}" class="btn-primary">+ Catat Surat Masuk</a>
    @endif
</div>

<div class="card overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50"><tr>
            <th class="th">No. Agenda</th><th class="th">Asal &amp; Perihal</th><th class="th">Kategori</th><th class="th">Diterima</th><th class="th">Status</th>
        </tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($letters as $l)
                <tr class="hover:bg-slate-50">
                    <td class="td whitespace-nowrap"><a class="font-semibold text-brand-700 hover:underline" href="{{ route('panel.incoming.show', $l) }}">{{ $l->agenda_no }}</a>
                        <div class="text-xs text-slate-500">{{ $l->letter_number }}</div></td>
                    <td class="td max-w-sm"><div class="font-medium">{{ $l->origin }}</div><span class="line-clamp-2 text-slate-600">{{ $l->subject }}</span></td>
                    <td class="td text-slate-500">{{ $l->category ?: '—' }}</td>
                    <td class="td whitespace-nowrap text-slate-500">{{ $l->received_date->translatedFormat('d M Y') }}</td>
                    <td class="td"><x-status-badge :status="$l->status" /></td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">Belum ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $letters->links() }}</div>
@endsection
