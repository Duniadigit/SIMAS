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
 *
 * Draf punya dua sumber: (1) template (letter_data terisi, PDF dibuat sistem) atau
 * (2) unggahan PDF admin (letter_data kosong). Keduanya berakhir sebagai draft_path saat diteruskan ke Kaprodi.
 */
class OutgoingWorkflow
{
    public function __construct(
        private LetterNumberService $numbers,
        private SignatureStamper $stamper,
        private LetterRenderer $renderer,
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

    /**
     * Admin menyusun surat lewat template (nomor, tujuan, titimangsa, lampiran, redaksi).
     *
     * @param  array{title:?string,number:?string,attachment:?string,recipient:?string,subject:string,place:string,date:string,body:string}  $data
     */
    public function saveTemplate(OutgoingLetter $letter, array $data, User $user): void
    {
        $oldDraft = null;

        $this->transition($letter, [S::SedangDibuat, S::Revisi], $user, 'isi_template', function (OutgoingLetter $l) use ($data, &$oldDraft) {
            $oldDraft = $l->draft_path;
            $l->draft_path = null; // PDF dibuat ulang saat diteruskan ke Kaprodi
            $l->subject = $data['subject'];
            $l->letter_number = $data['number'] ?: null;
            $l->letter_date = $data['date'];
            $l->letter_data = [
                'title' => trim((string) $data['title']),
                'attachment' => trim((string) $data['attachment']),
                'recipient' => trim((string) $data['recipient']),
                'place' => trim($data['place']),
                'body' => trim($data['body']),
            ];

            return 'Menyusun surat dengan template'.($l->letter_number ? ', nomor '.$l->letter_number : '');
        }, keepStatus: true);

        if ($oldDraft) {
            Storage::disk('local')->delete($oldDraft);
        }
    }

    /** Admin mengunggah / mengganti draf PDF sendiri (menggantikan isian template bila ada). */
    public function uploadDraft(OutgoingLetter $letter, UploadedFile $file, User $user): void
    {
        $new = $file->store('drafts/'.now()->format('Y'), 'local');
        $old = null;

        try {
            $this->transition($letter, [S::SedangDibuat, S::Revisi], $user, 'unggah_draf', function (OutgoingLetter $l) use ($new, &$old) {
                $old = $l->draft_path;
                $l->draft_path = $new;
                $l->letter_data = null;

                return 'Mengunggah draf surat (PDF)';
            }, keepStatus: true);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($new);
            throw $e;
        }

        if ($old) {
            Storage::disk('local')->delete($old);
        }
    }

    /** Admin meneruskan draf ke Kaprodi. Surat template dibekukan menjadi PDF pada saat ini. */
    public function submit(OutgoingLetter $letter, User $user): void
    {
        $oldDraft = null;

        $this->transition($letter, [S::SedangDibuat, S::Revisi], $user, 'ajukan_persetujuan', function (OutgoingLetter $l) use (&$oldDraft) {
            if ($l->letter_data) {
                try {
                    $pdf = $this->renderer->pdf($l);
                } catch (\Throwable $e) {
                    report($e);
                    throw ValidationException::withMessages(['draft' => 'Gagal membuat PDF dari template: '.$e->getMessage()]);
                }
                $oldDraft = $l->draft_path;
                $l->draft_path = 'drafts/'.now()->format('Y').'/'.Str::random(32).'.pdf';
                Storage::disk('local')->put($l->draft_path, $pdf);
            } elseif (! $l->draft_path) {
                throw ValidationException::withMessages(['draft' => 'Isi surat dengan template atau unggah draf PDF terlebih dahulu.']);
            }

            $l->status = S::MenungguPersetujuan;
            $l->review_note = null;

            return 'Draf diteruskan ke Kaprodi untuk persetujuan';
        });

        if ($oldDraft) {
            Storage::disk('local')->delete($oldDraft);
        }
    }

    /** ACC: pastikan bernomor, bubuhkan TTD, jadikan final. */
    public function approve(OutgoingLetter $letter, User $user): void
    {
        $this->transition($letter, [S::MenungguPersetujuan], $user, 'acc', function (OutgoingLetter $l) use ($user) {
            $l->loadMissing('type');
            $disk = Storage::disk('local');

            // Nomor yang diketik admin dipertahankan; bila kosong diterbitkan otomatis (dilewati bila bentrok).
            if (! $l->letter_number) {
                for ($i = 0; $i < 20; $i++) {
                    $candidate = $this->numbers->next($l->type, now());
                    if (! OutgoingLetter::where('letter_number', $candidate)->exists()) {
                        $l->letter_number = $candidate;
                        break;
                    }
                }
                if (! $l->letter_number) {
                    throw ValidationException::withMessages(['action' => 'Gagal menerbitkan nomor surat otomatis. Isi nomor secara manual.']);
                }
            }
            if (! $l->letter_date) {
                $l->letter_date = now()->toDateString();
            }

            $signer = $user->isKaprodi()
                ? $user
                : User::where('role', 'kaprodi')->where('is_active', true)->first();

            $final = 'final/'.now()->format('Y').'/'.Str::random(32).'.pdf';
            $disk->makeDirectory(dirname($final));

            $hasSignature = $signer?->signature_path && $disk->exists($signer->signature_path);
            $note = '';
            $stamped = false;

            if ($l->letter_data) {
                // Surat template: dibuat ulang dengan nomor final dan TTD tertanam (andal, tanpa FPDI).
                try {
                    $disk->put($final, $this->renderer->pdf($l, $signer, true));
                } catch (\Throwable $e) {
                    report($e);
                    throw ValidationException::withMessages(['action' => 'Gagal membuat PDF final: '.$e->getMessage()]);
                }
                $stamped = (bool) $hasSignature;
                if (! $stamped) {
                    $note = ' (TTD belum dibubuhkan: Kaprodi belum mengunggah TTD)';
                }
            } else {
                // Draf unggahan: bubuhkan TTD di halaman terakhir lewat FPDI.
                if ($hasSignature) {
                    $stamped = $this->stamper->stamp($disk->path($l->draft_path), $disk->path($final), $disk->path($signer->signature_path));
                    if (! $stamped) {
                        $note = ' (TTD otomatis gagal: '.$this->stamper->lastError.')';
                    }
                } else {
                    $note = ' (TTD otomatis dilewati: Kaprodi belum mengunggah TTD)';
                }
                if (! $stamped) {
                    $disk->copy($l->draft_path, $final);
                }
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
