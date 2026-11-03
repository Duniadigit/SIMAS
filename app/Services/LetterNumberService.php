<?php

namespace App\Services;

use App\Models\LetterType;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class LetterNumberService
{
    private const ROMAN = [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    /**
     * Nomor urut berikutnya untuk tahun berjalan. WAJIB dipanggil di dalam DB::transaction:
     * baris counter dikunci (FOR UPDATE) sehingga dua ACC bersamaan tidak mendapat nomor sama.
     */
    public function next(LetterType $type, CarbonInterface $date): string
    {
        $year = (int) $date->format('Y');

        DB::table('letter_counters')->insertOrIgnore(['year' => $year, 'last_number' => 0]);

        $row = DB::table('letter_counters')->where('year', $year)->lockForUpdate()->first();
        $next = $row->last_number + 1;

        DB::table('letter_counters')->where('year', $year)->update(['last_number' => $next]);

        return strtr((string) config('simas.number_format'), [
            '{seq}' => str_pad((string) $next, (int) config('simas.number_pad'), '0', STR_PAD_LEFT),
            '{code}' => $type->code,
            '{year}' => (string) $year,
            '{month}' => $date->format('m'),
            '{roman_month}' => self::ROMAN[(int) $date->format('n')],
        ]);
    }
}
