<?php

namespace App\Http\Controllers\Panel;

use App\Enums\IncomingStatus;
use App\Enums\OutgoingStatus;
use App\Http\Controllers\Controller;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Services\StatsService;

class DashboardController extends Controller
{
    public function __invoke(StatsService $stats)
    {
        // Reminder: surat yang masih menunggu tindakan (paling lama di atas).
        $pendingOutgoing = OutgoingLetter::with('type:id,name')
            ->select(['id', 'registration_no', 'letter_type_id', 'requester_name', 'subject', 'status', 'created_at', 'updated_at'])
            ->whereIn('status', OutgoingStatus::pendingValues())
            ->orderBy('created_at')->limit(8)->get();

        $awaitingDisposition = IncomingLetter::select(['id', 'agenda_no', 'letter_number', 'origin', 'subject', 'received_date', 'status'])
            ->where('status', IncomingStatus::Diterima->value)
            ->orderBy('received_date')->limit(8)->get();

        return view('panel.dashboard', [
            'stats' => $stats->month(),
            'pendingOutgoing' => $pendingOutgoing,
            'awaitingDisposition' => $awaitingDisposition,
        ]);
    }
}
