<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Surat {{ $tab === 'masuk' ? 'Masuk' : 'Keluar' }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #111; margin: 24px; }
        h1 { font-size: 16px; margin: 0 0 2px; } p { margin: 0 0 12px; color: #444; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 5px 6px; text-align: left; vertical-align: top; }
        th { background: #eee; }
        .no-print { margin-bottom: 12px; }
        @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="no-print"><button onclick="window.print()">Cetak / Simpan PDF</button></div>
    <h1>Laporan Surat {{ $tab === 'masuk' ? 'Masuk' : 'Keluar' }} — {{ config('simas.program') }} {{ config('simas.institution') }}</h1>
    <p>
        Periode: {{ !empty($f['from']) ? \Carbon\Carbon::parse($f['from'])->translatedFormat('d M Y') : 'awal' }}
        s.d. {{ !empty($f['to']) ? \Carbon\Carbon::parse($f['to'])->translatedFormat('d M Y') : 'sekarang' }}
        · {{ $rows->count() }} data · dicetak {{ now()->translatedFormat('d M Y H:i') }}
    </p>
    <table>
        <thead><tr>
            <th>No</th>
            @if ($tab === 'keluar')
                <th>Nomor surat</th><th>Registrasi</th><th>Perihal</th><th>Pemohon</th><th>Jenis</th><th>Status</th><th>Tanggal</th>
            @else
                <th>Agenda</th><th>Nomor surat</th><th>Perihal</th><th>Asal</th><th>Kategori</th><th>Status</th><th>Diterima</th>
            @endif
        </tr></thead>
        <tbody>
        @foreach ($rows as $i => $r)
            <tr>
                <td>{{ $i + 1 }}</td>
                @if ($tab === 'keluar')
                    <td>{{ $r->letter_number ?: '—' }}</td><td>{{ $r->registration_no }}</td><td>{{ $r->subject }}</td>
                    <td>{{ $r->requester_name }}</td><td>{{ $r->type->name }}</td><td>{{ $r->status->label() }}</td>
                    <td>{{ ($r->letter_date ?? $r->created_at)->translatedFormat('d M Y') }}</td>
                @else
                    <td>{{ $r->agenda_no }}</td><td>{{ $r->letter_number }}</td><td>{{ $r->subject }}</td>
                    <td>{{ $r->origin }}</td><td>{{ $r->category ?: '—' }}</td><td>{{ $r->status->label() }}</td>
                    <td>{{ $r->received_date->translatedFormat('d M Y') }}</td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
