@extends('layouts.panel')
@section('title', 'Surat Keluar')
@section('heading', 'Surat Keluar')
@section('content')
<form method="GET" class="card card-body mb-4 grid gap-3 sm:grid-cols-[1fr_200px_auto]">
    <input class="input" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nomor registrasi, nama, NPM, perihal…">
    <select class="input" name="status">
        <option value="">Semua status</option>
        @foreach (\App\Enums\OutgoingStatus::options() as $v => $label)
            <option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $label }}</option>
        @endforeach
    </select>
    <button class="btn-primary" type="submit">Filter</button>
</form>

<div class="card overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50"><tr>
            <th class="th">Registrasi</th><th class="th">Pemohon</th><th class="th">Perihal</th><th class="th">Status</th><th class="th">Tanggal</th>
        </tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($letters as $l)
                <tr class="hover:bg-slate-50">
                    <td class="td whitespace-nowrap"><a class="font-semibold text-brand-700 hover:underline" href="{{ route('panel.outgoing.show', $l) }}">{{ $l->registration_no }}</a>
                        @if ($l->letter_number)<div class="text-xs text-slate-500">{{ $l->letter_number }}</div>@endif</td>
                    <td class="td">{{ $l->requester_name }}<div class="text-xs text-slate-500">{{ $l->requester_identifier }}</div></td>
                    <td class="td max-w-xs"><span class="line-clamp-2">{{ $l->subject }}</span><div class="text-xs text-slate-500">{{ $l->type->name }}</div></td>
                    <td class="td"><x-status-badge :status="$l->status" /></td>
                    <td class="td whitespace-nowrap text-slate-500">{{ $l->created_at->translatedFormat('d M Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">Belum ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $letters->links() }}</div>
@endsection
