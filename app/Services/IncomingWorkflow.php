<?php

namespace App\Services;

use App\Enums\IncomingStatus as S;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IncomingWorkflow
{
    public function __construct(private StatsService $stats) {}

    /** Kaprodi membuat lembar disposisi. */
    public function addDisposition(IncomingLetter $letter, array $data, User $user): void
    {
        DB::transaction(function () use ($letter, $data, $user) {
            $l = $this->lock($letter->id);
            $this->ensureOpen($l);

            $from = $l->status;
            Disposition::create([
                'incoming_letter_id' => $l->id,
                'assignee' => $data['assignee'],
                'instruction' => $data['instruction'],
                'deadline' => $data['deadline'] ?? null,
                'created_by' => $user->id,
            ]);

            $l->status = S::Disposisi;
            $l->save();

            ActivityLogger::log($l, 'disposisi', 'Disposisi kepada '.$data['assignee'].': '.$data['instruction'], $from->value, S::Disposisi->value, $user);
        });

        $this->stats->flush();
    }

    /** Staf/dosen selesai menindaklanjuti; bila semua disposisi tuntas, surat otomatis selesai. */
    public function completeDisposition(Disposition $disposition, ?string $note, User $user): void
    {
        DB::transaction(function () use ($disposition, $note, $user) {
            $l = $this->lock($disposition->incoming_letter_id);
            $d = Disposition::whereKey($disposition->id)->lockForUpdate()->firstOrFail();

            if ($d->completed_at) {
                throw ValidationException::withMessages(['action' => 'Disposisi ini sudah ditandai selesai.']);
            }

            $d->update(['completed_at' => now(), 'completion_note' => $note]);
            ActivityLogger::log($l, 'disposisi_selesai', 'Tindak lanjut selesai oleh '.$d->assignee, null, null, $user);

            $remaining = Disposition::where('incoming_letter_id', $l->id)->whereNull('completed_at')->count();
            if ($remaining === 0 && $l->status !== S::Selesai) {
                $from = $l->status;
                $l->status = S::Selesai;
                $l->save();
                ActivityLogger::log($l, 'selesai', 'Seluruh disposisi selesai, surat ditutup', $from->value, S::Selesai->value, $user);
            }
        });

        $this->stats->flush();
    }

    /** Admin menutup surat yang tidak memerlukan disposisi. */
    public function finish(IncomingLetter $letter, User $user): void
    {
        DB::transaction(function () use ($letter, $user) {
            $l = $this->lock($letter->id);
            $this->ensureOpen($l);

            if (Disposition::where('incoming_letter_id', $l->id)->whereNull('completed_at')->exists()) {
                throw ValidationException::withMessages(['action' => 'Masih ada disposisi yang belum selesai.']);
            }

            $from = $l->status;
            $l->status = S::Selesai;
            $l->save();
            ActivityLogger::log($l, 'selesai', 'Surat ditandai selesai', $from->value, S::Selesai->value, $user);
        });

        $this->stats->flush();
    }

    private function lock(int $id): IncomingLetter
    {
        return IncomingLetter::whereKey($id)->lockForUpdate()->firstOrFail();
    }

    private function ensureOpen(IncomingLetter $l): void
    {
        if ($l->status === S::Selesai) {
            throw ValidationException::withMessages(['action' => 'Surat ini sudah selesai.']);
        }
    }
}
