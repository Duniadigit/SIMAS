<?php

namespace App\Http\Controllers\Panel;

use App\Enums\IncomingStatus;
use App\Http\Controllers\Controller;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Services\ActivityLogger;
use App\Services\IncomingWorkflow;
use App\Services\StatsService;
use App\Support\RegistrationNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IncomingLetterController extends Controller
{
    public function __construct(private IncomingWorkflow $workflow) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:'.implode(',', array_keys(IncomingStatus::options()))],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $letters = IncomingLetter::select(['id', 'agenda_no', 'letter_number', 'origin', 'subject', 'category', 'received_date', 'status'])
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['q'] ?? null, function ($q, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($w) => $w->where('letter_number', 'like', $like)
                    ->orWhere('origin', 'like', $like)
                    ->orWhere('subject', 'like', $like));
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('panel.incoming.index', ['letters' => $letters, 'filters' => $filters]);
    }

    public function create()
    {
        return view('panel.incoming.create');
    }

    public function store(Request $request, StatsService $stats)
    {
        $data = $request->validate([
            'letter_number' => ['required', 'string', 'max:100'],
            'origin' => ['required', 'string', 'max:200'],
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:80'],
            'letter_date' => ['required', 'date'],
            'received_date' => ['required', 'date', 'before_or_equal:today'],
            'scan' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:'.config('simas.pdf_max_kb')],
        ], [], [
            'letter_number' => 'nomor surat', 'origin' => 'asal surat', 'subject' => 'perihal',
            'letter_date' => 'tanggal surat', 'received_date' => 'tanggal diterima', 'scan' => 'berkas scan',
        ]);

        $path = $request->file('scan')->store('incoming/'.now()->format('Y'), 'local');
        unset($data['scan']);

        $letter = IncomingLetter::create($data + [
            'agenda_no' => RegistrationNumber::make('SM', fn ($n) => IncomingLetter::where('agenda_no', $n)->exists()),
            'scan_path' => $path,
            'status' => IncomingStatus::Diterima->value,
            'created_by' => $request->user()->id,
        ]);

        ActivityLogger::log($letter, 'diterima', 'Surat masuk dicatat dari '.$letter->origin, null, IncomingStatus::Diterima->value);
        $stats->flush();

        return redirect()->route('panel.incoming.show', $letter)->with('success', 'Surat masuk tersimpan. Menunggu disposisi Kaprodi.');
    }

    public function show(IncomingLetter $incoming)
    {
        $incoming->load(['dispositions', 'creator:id,name', 'logs']);

        return view('panel.incoming.show', ['letter' => $incoming]);
    }

    public function download(IncomingLetter $incoming)
    {
        abort_unless(Storage::disk('local')->exists($incoming->scan_path), 404);

        return Storage::disk('local')->response($incoming->scan_path, 'surat-masuk-'.$incoming->agenda_no.'.pdf', ['Content-Type' => 'application/pdf']);
    }

    public function dispose(IncomingLetter $incoming, Request $request)
    {
        $data = $request->validate([
            'assignee' => ['required', 'string', 'max:150'],
            'instruction' => ['required', 'string', 'max:2000'],
            'deadline' => ['nullable', 'date', 'after_or_equal:today'],
        ], [], ['assignee' => 'tujuan disposisi', 'instruction' => 'instruksi', 'deadline' => 'batas waktu']);

        $this->workflow->addDisposition($incoming, $data, $request->user());

        return back()->with('success', 'Disposisi dicatat.');
    }

    public function completeDisposition(Disposition $disposition, Request $request)
    {
        $data = $request->validate(['completion_note' => ['nullable', 'string', 'max:1000']]);
        $this->workflow->completeDisposition($disposition, $data['completion_note'] ?? null, $request->user());

        return back()->with('success', 'Tindak lanjut ditandai selesai.');
    }

    public function finish(IncomingLetter $incoming, Request $request)
    {
        $this->workflow->finish($incoming, $request->user());

        return back()->with('success', 'Surat ditandai selesai.');
    }
}
