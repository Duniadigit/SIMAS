<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Kaprodi = 'kaprodi';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin Prodi',
            self::Kaprodi => 'Kaprodi',
        };
    }
}
