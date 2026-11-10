<?php

namespace App\Services;

use App\Models\OutgoingLetter;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;

/**
 * Membuat PDF surat dari isian template (OutgoingLetter::letter_data).
 * HTML disusun dengan escape penuh (tidak ada HTML dari pengguna) dan dompdf berjalan tanpa akses
 * jaringan/berkas luar: gambar (logo, TTD) disisipkan sebagai data-URI.
 */
class LetterRenderer
{
    public function signer(): ?User
    {
        return User::where('role', 'kaprodi')->where('is_active', true)->orderBy('id')->first();
    }

    /** @return array<string,mixed> */
    public function context(OutgoingLetter $letter, ?User $signer, bool $final): array
    {
        $d = $letter->letter_data ?? [];
        $cfg = config('simas.letter');
        $date = $letter->letter_date ?? now();

        $signatureUri = null;
        if ($final && $signer?->signature_path && Storage::disk('local')->exists($signer->signature_path)) {
            $signatureUri = $this->dataUri(Storage::disk('local')->path($signer->signature_path));
        }

        $recipient = trim((string) ($d['recipient'] ?? ''));

        return [
            'font_size' => $cfg['font_size_pt'],
            'kop' => $cfg['kop'],
            'kop_address' => $cfg['kop_address'],
            'logo' => $this->logoUri($cfg['logo']),
            'title' => trim((string) ($d['title'] ?? '')),
            'number' => (string) ($letter->letter_number ?? ''),
            'attachment' => trim((string) ($d['attachment'] ?? '')),
            'subject' => (string) $letter->subject,
            'recipient_lines' => $recipient === '' ? [] : preg_split('/\R/u', $recipient),
            'place' => (string) ($d['place'] ?? $cfg['city']),
            'date_text' => $date->translatedFormat('j F Y'),
            'blocks' => $this->blocks((string) ($d['body'] ?? '')),
            'signer_title' => $cfg['signer_title'],
            'signer_name' => $signer?->name ?? '(nama Kaprodi)',
            'signer_nip' => (string) ($signer?->nip ?? ''),
            'signature' => $signatureUri,
        ];
    }

    public function html(OutgoingLetter $letter, ?User $signer = null, bool $final = false): string
    {
        $signer ??= $this->signer();
        $c = $this->context($letter, $signer, $final);
        $e = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        ob_start();
        try {
            require resource_path('letters/surat.php');
        } catch (\Throwable $ex) {
            ob_end_clean();
            throw $ex;
        }

        return (string) ob_get_clean();
    }

    /** Biner PDF. $final=true menyisipkan gambar TTD dan membuang penanda draf. */
    public function pdf(OutgoingLetter $letter, ?User $signer = null, bool $final = false): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('chroot', [base_path('public'), storage_path('app')]);
        $options->set('defaultFont', 'Times');
        $options->set('tempDir', storage_path('framework/cache'));
        $options->set('fontCache', storage_path('framework/cache'));

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->html($letter, $signer, $final), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Memecah redaksi menjadi blok. Paragraf dipisah baris kosong. Paragraf yang SEMUA barisnya
     * berbentuk "Label : isi" (minimal 2 baris) dirender sebagai tabel rapi.
     *
     * @return list<array{type:string,lines?:list<string>,rows?:list<array{0:string,1:string}>}>
     */
    public function blocks(string $body): array
    {
        $out = [];
        foreach (preg_split('/\R{2,}/u', trim($body)) ?: [] as $para) {
            $lines = array_values(array_filter(array_map('rtrim', preg_split('/\R/u', $para)), fn ($l) => trim($l) !== ''));
            if (! $lines) {
                continue;
            }

            $rows = [];
            foreach ($lines as $line) {
                if (preg_match('/^\s*([^:\n]{1,40}?)\s*:\s*(.+)$/u', $line, $m)) {
                    $rows[] = [$m[1], $m[2]];
                }
            }

            $out[] = (count($lines) >= 2 && count($rows) === count($lines))
                ? ['type' => 'kv', 'rows' => $rows]
                : ['type' => 'p', 'lines' => array_map('trim', $lines)];
        }

        return $out;
    }

    private function logoUri(string $relative): ?string
    {
        $path = public_path($relative);

        return is_file($path) ? $this->dataUri($path) : null;
    }

    private function dataUri(string $path): string
    {
        $mime = @getimagesize($path)['mime'] ?? 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
