<?php

namespace App\Services;

use setasign\Fpdi\Fpdi;
use Throwable;

/**
 * Membubuhkan gambar TTD (PNG) pada halaman terakhir PDF.
 * Butuh: composer require setasign/fpdi (versi gratis hanya membaca PDF s/d 1.4 / tanpa
 * compressed xref; untuk PDF yang lebih baru, ekspor draf dari Word sebagai "PDF 1.4 / ISO 19005-1"
 * atau pakai parser komersial FPDI-PDF-Parser). Bila gagal, surat tetap selesai tanpa TTD
 * dan alasan dicatat di log aktivitas.
 */
class SignatureStamper
{
    public ?string $lastError = null;

    public function stamp(string $sourcePdf, string $destPdf, string $signaturePng): bool
    {
        $this->lastError = null;

        if (! class_exists(Fpdi::class)) {
            $this->lastError = 'paket setasign/fpdi belum terpasang';

            return false;
        }

        try {
            $size = @getimagesize($signaturePng);
            if (! $size || $size[0] < 1) {
                throw new \RuntimeException('file TTD bukan gambar valid');
            }

            $cfg = config('simas.signature');
            $w = (float) $cfg['width_mm'];
            $h = $w * ($size[1] / $size[0]);

            $pdf = new Fpdi;
            $pages = $pdf->setSourceFile($sourcePdf);

            for ($i = 1; $i <= $pages; $i++) {
                $tpl = $pdf->importPage($i);
                $dim = $pdf->getTemplateSize($tpl);
                $pdf->AddPage($dim['orientation'], [$dim['width'], $dim['height']]);
                $pdf->useTemplate($tpl);

                if ($i === $pages) {
                    $x = $dim['width'] - (float) $cfg['margin_right_mm'] - $w;
                    $y = $dim['height'] - (float) $cfg['margin_bottom_mm'] - $h;
                    $pdf->Image($signaturePng, max($x, 0), max($y, 0), $w, $h);
                }
            }

            $pdf->Output('F', $destPdf);

            return true;
        } catch (Throwable $e) {
            report($e);
            $this->lastError = $e->getMessage();

            return false;
        }
    }
}
