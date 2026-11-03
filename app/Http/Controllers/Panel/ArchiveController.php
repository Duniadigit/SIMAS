<?php

namespace App\Http\Controllers\Panel;

use App\Enums\IncomingStatus;
use App\Enums\OutgoingStatus;
use App\Http\Controllers\Controller;
use App\Models\IncomingLetter;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/** Pencarian arsip terpusat: rentang tanggal + kategori + perihal/nomor. */
class ArchiveController extends Controller
{
    public function index(Request $request)
    {
        [$tab, $f] = $this->filters($request);

        $rows = $this->query($tab, $f)->paginate(20)->withQueryString();

        return view('panel.archive.index', [
            'tab' => $tab, 'f' => $f, 'rows' => $rows,
            'types' => LetterType::orderBy('name')->pluck('name', 'id'),
            'categories' => config('simas.incoming_categories'),
            'statuses' => $tab === 'masuk' ? IncomingStatus::options() : OutgoingStatus::options(),
        ]);
    }

    public function print(Request $request)
    {
        [$tab, $f] = $this->filters($request);

        return view('panel.archive.print', [
            'tab' => $tab, 'f' => $f,
            'rows' => $this->query($tab, $f)->limit(1000)->get(),
        ]);
    }

    /** @return array{0:string,1:array<string,mixed>} */
    private function filters(Request $request): array
    {
        $f = $request->validate([
            'tab' => ['nullable', 'in:keluar,masuk'],
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'type' => ['nullable', 'integer'],
            'category' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'string', 'max:30'],
        ]);

        return [$f['tab'] ?? 'keluar', $f];
    }

    private function query(string $tab, array $f): Builder
    {
        $like = isset($f['q']) ? '%'.addcslashes($f['q'], '%_\\').'%' : null;

        if ($tab === 'masuk') {
            return IncomingLetter::select(['id', 'agenda_no', 'letter_number', 'origin', 'subject', 'category', 'letter_date', 'received_date', 'status'])
                ->when($like, fn ($q) => $q->where(fn ($w) => $w->where('subject', 'like', $like)
                    ->orWhere('letter_number', 'like', $like)->orWhere('origin', 'like', $like)))
                ->when($f['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
                ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
                ->when($f['from'] ?? null, fn ($q, $v) => $q->where('received_date', '>=', $v))
                ->when($f['to'] ?? null, fn ($q, $v) => $q->where('received_date', '<=', $v))
                ->orderByDesc('received_date')->orderByDesc('id');
        }

        return OutgoingLetter::with('type:id,name')
            ->select(['id', 'registration_no', 'letter_type_id', 'requester_name', 'subject', 'status', 'letter_number', 'letter_date', 'created_at'])
            ->when($like, fn ($q) => $q->where(fn ($w) => $w->where('subject', 'like', $like)
                ->orWhere('letter_number', 'like', $like)->orWhere('registration_no', 'like', $like)
                ->orWhere('requester_name', 'like', $like)))
            ->when($f['type'] ?? null, fn ($q, $v) => $q->where('letter_type_id', $v))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', $v.' 00:00:00'))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', $v.' 23:59:59'))
            ->orderByDesc('id');
    }
}
