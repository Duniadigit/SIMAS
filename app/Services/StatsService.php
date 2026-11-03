<?php

namespace App\Services;

use App\Enums\IncomingStatus;
use App\Enums\OutgoingStatus;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use Illuminate\Support\Facades\Cache;

/**
 * Statistik dashboard di-cache supaya ribuan kunjungan ke halaman depan
 * tidak menjadi ribuan query. Cache dibuang setiap ada perubahan data.
 */
class StatsService
{
    private function key(): string
    {
        return 'simas.stats.'.now()->format('Ym');
    }

    /** @return array<string,int> */
    public function month(): array
    {
        return Cache::remember($this->key(), (int) config('simas.stats_ttl'), function () {
            $start = now()->startOfMonth();
            $end = now()->endOfMonth();

            $outgoingMonth = OutgoingLetter::whereBetween('created_at', [$start, $end])->count();
            $outgoingDone = OutgoingLetter::where('status', OutgoingStatus::Selesai->value)
                ->whereBetween('approved_at', [$start, $end])->count();
            $incomingMonth = IncomingLetter::whereBetween('received_date', [$start->toDateString(), $end->toDateString()])->count();

            $pendingByStatus = OutgoingLetter::whereIn('status', OutgoingStatus::pendingValues())
                ->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

            return [
                'outgoing_month' => $outgoingMonth,
                'outgoing_done_month' => $outgoingDone,
                'incoming_month' => $incomingMonth,
                'pending_outgoing' => (int) $pendingByStatus->sum(),
                'being_made' => (int) ($pendingByStatus[OutgoingStatus::SedangDibuat->value] ?? 0),
                'awaiting_approval' => (int) ($pendingByStatus[OutgoingStatus::MenungguPersetujuan->value] ?? 0),
                'awaiting_disposition' => IncomingLetter::where('status', IncomingStatus::Diterima->value)->count(),
            ];
        });
    }

    public function flush(): void
    {
        Cache::forget($this->key());
    }
}
