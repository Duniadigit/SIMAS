<?php

namespace App\Http\Controllers\Panel;

use App\Enums\OutgoingStatus;
use App\Http\Controllers\Controller;
use App\Models\OutgoingLetter;
use App\Services\OutgoingWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        return view('panel.outgoing.show', ['letter' => $letter]);
    }

    public function start(OutgoingLetter $letter, Request $request)
    {
        $this->workflow->start($letter, $request->user());

        return back()->with('success', 'Permohonan diproses. Silakan unggah draf surat.');
    }

    public function uploadDraft(OutgoingLetter $letter, Request $request)
    {
        $request->validate([
            'draft' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:'.config('simas.pdf_max_kb')],
        ], [], ['draft' => 'draf PDF']);

        $this->workflow->uploadDraft($letter, $request->file('draft'), $request->user());

        return back()->with('success', 'Draf berhasil diunggah.');
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

    /** kind = draft | final. Berkas privat, hanya lewat sini. */
    public function download(OutgoingLetter $letter, string $kind)
    {
        abort_unless(in_array($kind, ['draft', 'final'], true), 404);
        $path = $kind === 'draft' ? $letter->draft_path : $letter->final_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, $kind.'-'.$letter->registration_no.'.pdf', ['Content-Type' => 'application/pdf']);
    }
}
