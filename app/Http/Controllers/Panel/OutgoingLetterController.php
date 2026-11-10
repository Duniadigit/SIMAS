<?php

namespace App\Http\Controllers\Panel;

use App\Enums\OutgoingStatus;
use App\Http\Controllers\Controller;
use App\Models\OutgoingLetter;
use App\Services\LetterDocx;
use App\Services\LetterRenderer;
use App\Services\OutgoingWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OutgoingLetterController extends Controller
{
    public function __construct(private OutgoingWorkflow $workflow) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:'.implode(',', array_keys(OutgoingStatus::options()))],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $letters = OutgoingLetter::with('type:id,name')
            ->select(['id', 'registration_no', 'letter_type_id', 'requester_name', 'requester_identifier', 'subject', 'status', 'letter_number', 'created_at'])
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['q'] ?? null, function ($q, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($w) => $w->where('registration_no', 'like', $like)
                    ->orWhere('requester_name', 'like', $like)
                    ->orWhere('requester_identifier', 'like', $like)
                    ->orWhere('subject', 'like', $like));
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('panel.outgoing.index', ['letters' => $letters, 'filters' => $filters]);
    }

    public function show(OutgoingLetter $letter)
    {
        $letter->load(['type', 'processor:id,name', 'approver:id,name', 'logs']);

        $canPreview = ($letter->status === OutgoingStatus::Selesai && $letter->final_path)
            || $letter->letter_data
            || $letter->draft_path;

        return view('panel.outgoing.show', [
            'letter' => $letter,
            'form' => $this->editorDefaults($letter),
            'canPreview' => (bool) $canPreview,
        ]);
    }

    public function start(OutgoingLetter $letter, Request $request)
    {
        $this->workflow->start($letter, $request->user());

        return back()->with('success', 'Permohonan diproses. Susun surat dengan template atau unggah draf PDF.');
    }

    public function saveTemplate(OutgoingLetter $letter, Request $request)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:150'],
            'number' => ['nullable', 'string', 'max:100', Rule::unique('outgoing_letters', 'letter_number')->ignore($letter->id)],
            'attachment' => ['nullable', 'string', 'max:100'],
            'recipient' => ['nullable', 'string', 'max:500'],
            'subject' => ['required', 'string', 'max:255'],
            'place' => ['required', 'string', 'max:60'],
            'date' => ['required', 'date'],
            'body' => ['required', 'string', 'max:10000'],
        ], [
            'number.unique' => 'Nomor surat ini sudah dipakai surat lain.',
        ], [
            'title' => 'judul surat', 'number' => 'nomor surat', 'attachment' => 'lampiran',
            'recipient' => 'tujuan surat', 'subject' => 'perihal', 'place' => 'tempat',
            'date' => 'titimangsa', 'body' => 'redaksi surat',
        ]);

        $this->workflow->saveTemplate($letter, $data, $request->user());

        return back()->with('success', 'Surat tersimpan. Pratinjau sudah diperbarui.');
    }

    public function uploadDraft(OutgoingLetter $letter, Request $request)
    {
        $request->validate([
            'draft' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:'.config('simas.pdf_max_kb')],
        ], [], ['draft' => 'draf PDF']);

        $this->workflow->uploadDraft($letter, $request->file('draft'), $request->user());

        return back()->with('success', 'Draf PDF berhasil diunggah.');
    }

    public function submit(OutgoingLetter $letter, Request $request)
    {
        $this->workflow->submit($letter, $request->user());

        return back()->with('success', 'Draf diteruskan ke Kaprodi.');
    }

    public function approve(OutgoingLetter $letter, Request $request)
    {
        abort_unless($request->user()->canApprove(), 403);
        $this->workflow->approve($letter, $request->user());

        return back()->with('success', 'Surat di-ACC dan diberi nomor.');
    }

    public function revise(OutgoingLetter $letter, Request $request)
    {
        abort_unless($request->user()->canApprove(), 403);
        $data = $request->validate(['note' => ['required', 'string', 'max:1000']], [], ['note' => 'catatan revisi']);
        $this->workflow->requestRevision($letter, $request->user(), $data['note']);

        return back()->with('success', 'Catatan revisi dikirim ke admin.');
    }

    public function reject(OutgoingLetter $letter, Request $request)
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:1000']], [], ['note' => 'alasan penolakan']);
        $this->workflow->reject($letter, $request->user(), $data['note']);

        return back()->with('success', 'Permohonan ditolak.');
    }

    /** Pratinjau inline (untuk iframe) — surat final, draf template, atau draf unggahan, mana yang berlaku. */
    public function preview(OutgoingLetter $letter, LetterRenderer $renderer)
    {
        $disk = Storage::disk('local');
        $editable = in_array($letter->status, [OutgoingStatus::SedangDibuat, OutgoingStatus::Revisi], true);
        $name = 'surat-'.$letter->registration_no.'.pdf';
        $headers = ['Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store'];

        if ($letter->status === OutgoingStatus::Selesai && $letter->final_path && $disk->exists($letter->final_path)) {
            return $disk->response($letter->final_path, $name, $headers);
        }

        // Selama masih disusun admin, tampilkan hasil template terbaru (render langsung).
        if ($letter->letter_data && ($editable || ! $letter->draft_path)) {
            return response($renderer->pdf($letter), 200, $headers + ['Content-Disposition' => 'inline; filename="'.$name.'"']);
        }

        if ($letter->draft_path && $disk->exists($letter->draft_path)) {
            return $disk->response($letter->draft_path, $name, $headers);
        }

        abort(404);
    }

    /** Unduh versi Word dari surat template, untuk disunting di luar sistem bila perlu. */
    public function docx(OutgoingLetter $letter, LetterDocx $docx)
    {
        abort_unless($letter->letter_data, 404);

        return response()->download(
            $docx->build($letter),
            'surat-'.$letter->registration_no.'.docx',
        )->deleteFileAfterSend(true);
    }

    /** kind = draft | final. Berkas privat, hanya lewat sini. */
    public function download(OutgoingLetter $letter, string $kind)
    {
        abort_unless(in_array($kind, ['draft', 'final'], true), 404);
        $path = $kind === 'draft' ? $letter->draft_path : $letter->final_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, $kind.'-'.$letter->registration_no.'.pdf', ['Content-Type' => 'application/pdf']);
    }

    /** Nilai awal formulir template: data tersimpan, atau templat bawaan jenis surat. */
    private function editorDefaults(OutgoingLetter $letter): array
    {
        $d = $letter->letter_data;

        if ($d === null) {
            $type = $letter->type;
            $d = [
                'title' => (string) $type->default_title,
                'attachment' => '',
                'recipient' => '',
                'place' => config('simas.letter.city'),
                'body' => strtr((string) $type->body_template, [
                    '{nama}' => $letter->requester_name,
                    '{npm}' => $letter->requester_identifier,
                    '{perihal}' => $letter->subject,
                    '{tanggal}' => now()->translatedFormat('j F Y'),
                ]),
            ];
        }

        return $d + [
            'number' => (string) $letter->letter_number,
            'subject' => $letter->subject,
            'date' => ($letter->letter_date ?? now())->toDateString(),
        ];
    }
}
