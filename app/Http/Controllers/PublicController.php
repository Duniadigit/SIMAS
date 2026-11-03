<?php

namespace App\Http\Controllers;

use App\Enums\OutgoingStatus;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Services\ActivityLogger;
use App\Services\StatsService;
use App\Support\RegistrationNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicController extends Controller
{
    public function home(StatsService $stats)
    {
        return view('public.home', ['stats' => $stats->month()]);
    }

    public function create()
    {
        return view('public.request', ['types' => $this->types()]);
    }

    public function store(Request $request, StatsService $stats)
    {
        $data = $request->validate([
            'letter_type_id' => ['required', 'integer', 'in:'.implode(',', array_keys($this->types()))],
            'requester_type' => ['required', 'in:mahasiswa,dosen'],
            'requester_name' => ['required', 'string', 'max:150'],
            'requester_identifier' => ['required', 'string', 'min:4', 'max:30', 'regex:/^[A-Za-z0-9.\- ]+$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\- ]+$/'],
            'subject' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'letter_type_id' => 'jenis surat', 'requester_type' => 'status pemohon',
            'requester_name' => 'nama', 'requester_identifier' => 'NPM/NIDN',
            'subject' => 'perihal', 'details' => 'keterangan', 'phone' => 'nomor HP',
        ]);

        // Honeypot: bot mengisi kolom tersembunyi ini. Pura-pura sukses, jangan simpan.
        if ($request->filled('website')) {
            return redirect()->route('home');
        }

        $data['requester_identifier'] = strtoupper(trim($data['requester_identifier']));
        $data['registration_no'] = RegistrationNumber::make('SK', fn ($n) => OutgoingLetter::where('registration_no', $n)->exists());
        $data['status'] = OutgoingStatus::Diajukan->value;

        $letter = OutgoingLetter::create($data);
        ActivityLogger::log($letter, 'pengajuan', 'Permohonan surat diajukan oleh '.$letter->requester_name, null, OutgoingStatus::Diajukan->value, null);
        $stats->flush();

        return redirect()->route('track', ['q' => $letter->registration_no])
            ->with('success', 'Permohonan terkirim. Simpan nomor registrasi Anda: '.$letter->registration_no);
    }

    /** @return array<int,string> id => nama */
    private function types(): array
    {
        return Cache::remember('simas.letter_types', 600, fn () => LetterType::where('is_active', true)
            ->orderBy('name')->pluck('name', 'id')->all());
    }
}
