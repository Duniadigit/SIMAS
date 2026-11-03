<?php

namespace App\Support;

final class RegistrationNumber
{
    /**
     * Nomor registrasi acak yang mudah dibaca, contoh: SK-261102-7KQ4M.
     * Alfabet tanpa karakter membingungkan (0/O, 1/I). Keunikan dijamin juga oleh unique index.
     *
     * @param  callable(string):bool  $exists
     */
    public static function make(string $prefix, callable $exists): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $max = strlen($alphabet) - 1;

        do {
            $rand = '';
            for ($i = 0; $i < 5; $i++) {
                $rand .= $alphabet[random_int(0, $max)];
            }
            $number = sprintf('%s-%s-%s', $prefix, now()->format('ymd'), $rand);
        } while ($exists($number));

        return $number;
    }
}
