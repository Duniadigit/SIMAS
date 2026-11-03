@extends('layouts.panel')
@section('title', 'Log Aktivitas')
@section('heading', 'Log Aktivitas')
@section('content')
<div class="card overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50"><tr>
            <th class="th">Waktu</th><th class="th">Siapa</th><th class="th">Aktivitas</th><th class="th">Surat</th><th class="th">Status</th>
        </tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($logs as $log)
                @php
                    $s = $log->subject;
                    $isOut = $log->subject_type === \App\Models\OutgoingLetter::class;
                @endphp
                <tr>
                    <td class="td whitespace-nowrap text-slate-500">{{ $log->created_at->translatedFormat('d M Y H:i') }}</td>
                    <td class="td whitespace-nowrap font-medium">{{ $log->actor_name }}</td>
                    <td class="td max-w-md">{{ $log->description }}</td>
                    <td class="td whitespace-nowrap">
                        @if ($s)
                            <a class="text-brand-700 hover:underline" href="{{ $isOut ? route('panel.outgoing.show', $s->id) : route('panel.incoming.show', $s->id) }}">{{ $isOut ? $s->registration_no : $s->agenda_no }}</a>
                        @else — @endif
                    </td>
                    <td class="td whitespace-nowrap text-xs text-slate-500">{{ $log->from_status ? $log->from_status.' → ' : '' }}{{ $log->to_status ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">Belum ada aktivitas.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $logs->links() }}</div>
@endsection
