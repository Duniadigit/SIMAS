<?php

namespace App\Services;

use App\Enums\OutgoingStatus as S;
use App\Models\OutgoingLetter;
use App\Models\User;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Seluruh perpindahan status surat keluar lewat sini: baris dikunci (FOR UPDATE),
 * status divalidasi ulang, perubahan + log aktivitas ditulis dalam satu transaksi.
 */
class OutgoingWorkflow
{
    public function __construct(
        private LetterNumberService $numbers,
        private SignatureStamper $stamper,
        private StatsService $stats,
    ) {}

    /** Admin mulai memproses permohonan. */
    public function start(OutgoingLetter $letter, User $user): void
    {
        $this->transition($letter, [S::Diajukan], $user, 'proses', function (OutgoingLetter $l) use ($user) {
            $l->status = S::SedangDibuat;
            $l->processed_by = $user->id;

            return 'Mulai memproses draf surat';
        });
    }

    /** Admin mengunggah / mengganti draf PDF. */
    public function uploadDraft(OutgoingLetter $letter, UploadedFile $file, User $user): void
    {
        $new = $file->store('drafts/'.now()->format('Y'), 'local');
        $old = null;

        try {
            $this->transition($letter, [S::SedangDibuat, S::Revisi], $user, 'unggah_draf', function (OutgoingLetter $l) use ($new, &$old) {
                $old = $l->draft_path;
                $l->draft_path = $new;

                return 'Mengunggah draf surat';
            }, keepStatus: true);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($new);
            throw $e;
        }

        if ($old) {
            Storage::disk('local')->delete($old);
        }
    }

    /** Admin meneruskan draf ke Kaprodi. */
    public function submit(OutgoingLetter $letter, User $user): void
    {
        $this->transition($letter, [S::SedangDibuat, S::Revisi], $user, 'ajukan_persetujuan', function (OutgoingLetter $l) {
            if (! $l->draft_path) {
                throw ValidationException::withMessages(['draft' => 'Unggah draf PDF terlebih dahulu.']);
            }
            $l->status = S::MenungguPersetujuan;
            $l->review_note = null;

            return 'Draf diteruskan ke Kaprodi untuk persetujuan';
        });
    }

    /** ACC: beri nomor, bubuhkan TTD, jadikan final. */
    public function approve(OutgoingLetter $letter, User $user): void
    {
        $this->transition($letter, [S::MenungguPersetujuan], $user, 'acc', function (OutgoingLetter $l) use ($user) {
            $l->loadMissing('type');
            $disk = Storage::disk('local');

            $l->letter_date = now()->toDateString();
            $l->letter_number = $this->numbers->next($l->type, now());

            $signer = $user->isKaprodi()
                ? $user
                : User::where('role', 'kaprodi')->where('is_active', true)->first();

            $final = 'final/'.now()->format('Y').'/'.Str::random(32).'.pdf';
            $disk->makeDirectory(dirname($final));

            $note = '';
            $stamped = false;
            if ($signer?->signature_path && $disk->exists($signer->signature_path)) {
                $stamped = $this->stamper->stamp(
                    $disk->path($l->draft_path), $disk->path($final), $disk->path($signer->signature_path)
                );
                if (! $stamped) {
                    $note = ' (TTD otomatis gagal: '.$this->stamper->lastError.')';
                }
            } else {
                $note = ' (TTD otomatis dilewati: Kaprodi belum mengunggah TTD)';
            }

            if (! $stamped) {
                $disk->copy($l->draft_path, $final);
            }

            $l->final_path = $final;
            $l->status = S::Selesai;
            $l->approved_by = $user->id;
            $l->approved_at = now();
            $l->review_note = null;

            return 'ACC surat, nomor '.$l->letter_number.($stamped ? ', TTD dibubuhkan' : '').$note;
        });
    }

    public function requestRevision(OutgoingLetter $letter, User $user, string $note): void
    {
        $this->transition($letter, [S::MenungguPersetujuan], $user, 'revisi', function (OutgoingLetter $l) use ($note) {
            $l->status = S::Revisi;
            $l->review_note = $note;

            return 'Meminta revisi: '.$note;
        });
    }

    public function reject(OutgoingLetter $letter, User $user, string $note): void
    {
        $this->transition($letter, [S::Diajukan, S::SedangDibuat, S::Revisi, S::MenungguPersetujuan], $user, 'tolak', function (OutgoingLetter $l) use ($note) {
            $l->status = S::Ditolak;
            $l->review_note = $note;

            return 'Menolak permohonan: '.$note;
        });
    }

    /**
     * @param  list<S>  $allowed
     * @param  Closure(OutgoingLetter):string  $mutate  mengembalikan deskripsi log
     */
    private function transition(OutgoingLetter $letter, array $allowed, User $user, string $action, Closure $mutate, bool $keepStatus = false): void
    {
        DB::transaction(function () use ($letter, $allowed, $user, $action, $mutate, $keepStatus) {
            $fresh = OutgoingLetter::whereKey($letter->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array($fresh->status, $allowed, true)) {
                throw ValidationException::withMessages([
                    'action' => 'Status surat sudah berubah menjadi "'.$fresh->status->label().'". Muat ulang halaman.',
                ]);
            }

            $from = $fresh->status;
            $description = $mutate($fresh);
            $fresh->save();

            ActivityLogger::log($fresh, $action, $description, $from->value, $keepStatus ? null : $fresh->status->value, $user);
        });

        $this->stats->flush();
    }
}
