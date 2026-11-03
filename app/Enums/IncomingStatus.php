<?php

namespace App\Enums;

enum IncomingStatus: string
{
    case Diterima = 'diterima';
    case Disposisi = 'disposisi';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::Diterima => 'Diterima',
            self::Disposisi => 'Disposisi',
            self::Selesai => 'Selesai',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Diterima => 'bg-amber-100 text-amber-800',
            self::Disposisi => 'bg-blue-100 text-blue-800',
            self::Selesai => 'bg-emerald-100 text-emerald-800',
        };
    }

    /** @return array<string,string> value => label */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
