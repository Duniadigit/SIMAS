<?php

namespace App\Http\Controllers;

use App\Enums\OutgoingStatus;
use App\Models\OutgoingLetter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class TrackingController extends Controller
{
    public function index(Request $request)
    {
        $q = strtoupper(trim((string) $request->query('q', '')));
        $letters = null;

        if ($q !== '') {
            abort_if(strlen($q) > 40, 422);

            $letters = OutgoingLetter::with('type:id,name')
                ->select(['id', 'registration_no', 'letter_type_id', 'requester_name', 'subject', 'status', 'letter_number', 'review_note', 'final_path', 'created_at', 'updated_at'])
                ->where(fn ($w) => $w->where('registration_no', $q)->orWhere('requester_identifier', $q))
                ->latest('id')
                ->limit(20)
                ->get();

            foreach ($letters as $l) {
                $l->download_url = ($l->status === OutgoingStatus::Selesai && $l->final_path)
                    ? URL::temporarySignedRoute('track.download', now()->addMinutes(30), ['letter' => $l->id])
                    : null;
            }
        }

        return view('public.track', ['q' => $q, 'letters' => $letters]);
    }

    public function download(OutgoingLetter $letter)
    {
        abort_unless($letter->status === OutgoingStatus::Selesai && $letter->final_path, 404);

        return Storage::disk('local')->response(
            $letter->final_path,
            'surat-'.$letter->registration_no.'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }
}
