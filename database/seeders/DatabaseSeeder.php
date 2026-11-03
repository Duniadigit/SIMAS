<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\LetterType;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['Surat Keterangan Aktif Kuliah', 'SKA'],
            ['Surat Pengantar Riset / Penelitian', 'SPR'],
            ['Surat Permohonan Data / Izin Observasi', 'SPD'],
            ['Surat Dispensasi', 'SDP'],
            ['Surat Rekomendasi', 'SRK'],
            ['Surat Tugas', 'ST'],
            ['Surat Lainnya', 'SL'],
        ];
        foreach ($types as [$name, $code]) {
            LetterType::updateOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
        }

        $password = config('simas.seed_password');

        // GANTI email & password ini setelah login pertama.
        User::updateOrCreate(['email' => 'admin@simas.com'], [
            'name' => 'Admin Prodi', 'role' => Role::Admin, 'password' => $password, 'is_active' => true,
        ]);
        User::updateOrCreate(['email' => 'kaprodi@simas.com'], [
            'name' => 'Kaprodi Hukum Bisnis', 'role' => Role::Kaprodi, 'password' => $password, 'is_active' => true,
        ]);
    }
}
