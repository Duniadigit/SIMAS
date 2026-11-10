<?php

namespace App\Services;

use App\Models\OutgoingLetter;
use App\Models\User;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;

/**
 * Versi .docx dari surat template, agar admin bisa menyunting di Word bila perlu.
 * Isinya sama dengan PDF; TTD tidak disisipkan (draf). Hasil edit diunggah kembali sebagai PDF.
 */
class LetterDocx
{
    public function __construct(private LetterRenderer $renderer) {}

    /** @return string path berkas sementara (hapus setelah dikirim) */
    public function build(OutgoingLetter $letter, ?User $signer = null): string
    {
        $signer ??= $this->renderer->signer();
        $c = $this->renderer->context($letter, $signer, false);
        $cm = fn (float $v) => Converter::cmToTwip($v);

        $word = new PhpWord;
        $word->setDefaultFontName('Times New Roman');
        $word->setDefaultFontSize((int) $c['font_size']);
        $word->setDefaultParagraphStyle(['spaceAfter' => 0, 'spaceBefore' => 0]);

        $s = $word->addSection([
            'pageSizeW' => $cm(21), 'pageSizeH' => $cm(29.7),
            'marginTop' => $cm(1.6), 'marginBottom' => $cm(2), 'marginLeft' => $cm(2.5), 'marginRight' => $cm(2.2),
        ]);

        // Kop
        $logo = public_path(config('simas.letter.logo'));
        if (is_file($logo)) {
            $s->addImage($logo, ['height' => 55, 'alignment' => Jc::CENTER]);
        }
        foreach ($c['kop'] as $line) {
            $s->addText($line, ['bold' => true, 'size' => $c['font_size'] + 3], ['alignment' => Jc::CENTER]);
        }
        if ($c['kop_address'] !== '') {
            $s->addText($c['kop_address'], ['size' => $c['font_size'] - 2], ['alignment' => Jc::CENTER]);
        }
        $s->addText('', [], ['borderBottomSize' => 18, 'borderBottomColor' => '000000', 'spaceAfter' => 200]);

        $number = $c['number'] !== '' ? $c['number'] : '[diterbitkan saat ACC]';
        $dateLine = $c['place'].', '.$c['date_text'];

        if ($c['title'] !== '') {
            $s->addText($dateLine, [], ['alignment' => Jc::END]);
            $s->addText($c['title'], ['bold' => true, 'underline' => 'single', 'size' => $c['font_size'] + 1], ['alignment' => Jc::CENTER, 'spaceBefore' => 120]);
            $s->addText('Nomor: '.$number, [], ['alignment' => Jc::CENTER, 'spaceAfter' => 240]);
        } else {
            $t = $s->addTable();
            $rows = [['Nomor', $number], ['Lampiran', $c['attachment'] !== '' ? $c['attachment'] : '-'], ['Perihal', $c['subject']]];
            foreach ($rows as $i => [$k, $v]) {
                $t->addRow();
                $t->addCell($cm(2.4))->addText($k);
                $t->addCell($cm(0.4))->addText(':');
                $t->addCell($cm(6.3))->addText($v, ['bold' => $k === 'Perihal']);
                $t->addCell($cm(6.4))->addText($i === 0 ? $dateLine : '', [], ['alignment' => Jc::END]);
            }
            if ($c['recipient_lines']) {
                $s->addText('', [], ['spaceAfter' => 120]);
                $s->addText('Yth.');
                foreach ($c['recipient_lines'] as $line) {
                    $s->addText($line);
                }
            }
            $s->addText('', [], ['spaceAfter' => 120]);
        }

        // Isi
        foreach ($c['blocks'] as $b) {
            if ($b['type'] === 'kv') {
                $t = $s->addTable();
                foreach ($b['rows'] as [$k, $v]) {
                    $t->addRow();
                    $t->addCell($cm(0.8))->addText('');
                    $t->addCell($cm(3.6))->addText($k);
                    $t->addCell($cm(0.4))->addText(':');
                    $t->addCell($cm(9.5))->addText($v);
                }
                $s->addText('', [], ['spaceAfter' => 120]);

                continue;
            }
            $run = $s->addTextRun(['alignment' => Jc::BOTH, 'spaceAfter' => 160]);
            foreach ($b['lines'] as $i => $line) {
                if ($i > 0) {
                    $run->addTextBreak();
                }
                $run->addText($line);
            }
        }

        // Tanda tangan
        $s->addText('', [], ['spaceAfter' => 120]);
        $t = $s->addTable();
        $t->addRow();
        $t->addCell($cm(8.2))->addText('');
        $cell = $t->addCell($cm(7.3));
        $cell->addText($c['signer_title']);
        $cell->addText('');
        $cell->addText('');
        $cell->addText('');
        $cell->addText($c['signer_name'], ['bold' => true, 'underline' => 'single']);
        if ($c['signer_nip'] !== '') {
            $cell->addText('NIP. '.$c['signer_nip']);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'simas_docx_');
        IOFactory::createWriter($word, 'Word2007')->save($tmp);

        return $tmp;
    }
}
