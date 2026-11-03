<?php

namespace App\Enums;

enum OutgoingStatus: string
{
    case Diajukan = 'diajukan';
    case SedangDibuat = 'sedang_dibuat';
    case MenungguPersetujuan = 'menunggu_persetujuan';
    case Revisi = 'revisi';
    case Selesai = 'selesai';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Diajukan => 'Diajukan',
            self::SedangDibuat => 'Sedang Dibuat',
            self::MenungguPersetujuan => 'Menunggu Persetujuan',
            self::Revisi => 'Perlu Revisi',
            self::Selesai => 'Selesai',
            self::Ditolak => 'Ditolak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Diajukan => 'bg-slate-100 text-slate-700',
            self::SedangDibuat => 'bg-amber-100 text-amber-800',
            self::MenungguPersetujuan => 'bg-blue-100 text-blue-800',
            self::Revisi => 'bg-orange-100 text-orange-800',
            self::Selesai => 'bg-emerald-100 text-emerald-800',
            self::Ditolak => 'bg-red-100 text-red-800',
        };
    }

    /** Posisi pada timeline publik (0..3); -1 untuk ditolak. */
    public function step(): int
    {
        return match ($this) {
            self::Diajukan => 0,
            self::SedangDibuat, self::Revisi => 1,
            self::MenungguPersetujuan => 2,
            self::Selesai => 3,
            self::Ditolak => -1,
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

    /** Status yang masih butuh tindakan (untuk reminder dashboard). */
    public static function pendingValues(): array
    {
        return [
            self::Diajukan->value,
            self::SedangDibuat->value,
            self::MenungguPersetujuan->value,
            self::Revisi->value,
        ];
    }
}
