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
        $penutup = "Demikian surat ini kami sampaikan. Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.";
        $mhs = "Nama : {nama}\nNPM : {npm}\nProgram Studi : Hukum Bisnis";

        // [nama, kode, judul (khusus surat keterangan), templat redaksi]
        // Placeholder: {nama} {npm} {perihal} {tanggal}. Redaksi hanyalah contoh; sesuaikan dengan naskah resmi prodi.
        $types = [
            ['Surat Keterangan Aktif Kuliah', 'SKA', 'SURAT KETERANGAN AKTIF KULIAH',
                "Yang bertanda tangan di bawah ini, Ketua Jurusan Hukum Bisnis Fakultas Ilmu Sosial dan Ilmu Politik Universitas Siliwangi, menerangkan bahwa:\n\n{$mhs}\n\nadalah benar mahasiswa aktif pada Jurusan Hukum Bisnis Universitas Siliwangi pada semester berjalan.\n\nDemikian surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya."],
            ['Surat Pengantar Riset / Penelitian', 'SPR', null,
                "Dengan hormat,\n\nDalam rangka penyusunan tugas akhir, kami mohon kesediaan Bapak/Ibu memberikan izin kepada mahasiswa kami:\n\n{$mhs}\n\nuntuk melaksanakan penelitian di instansi yang Bapak/Ibu pimpin dengan topik: {perihal}.\n\n{$penutup}"],
            ['Surat Permohonan Data / Izin Observasi', 'SPD', null,
                "Dengan hormat,\n\nSehubungan dengan kegiatan akademik, kami mohon kesediaan Bapak/Ibu memberikan data/izin observasi kepada mahasiswa kami:\n\n{$mhs}\n\nuntuk keperluan: {perihal}.\n\n{$penutup}"],
            ['Surat Dispensasi', 'SDP', null,
                "Dengan hormat,\n\nMohon diberikan dispensasi kepada mahasiswa berikut:\n\n{$mhs}\n\nuntuk keperluan: {perihal}.\n\n{$penutup}"],
            ['Surat Rekomendasi', 'SRK', 'SURAT REKOMENDASI',
                "Yang bertanda tangan di bawah ini, Ketua Jurusan Hukum Bisnis Fakultas Ilmu Sosial dan Ilmu Politik Universitas Siliwangi, memberikan rekomendasi kepada:\n\n{$mhs}\n\nuntuk keperluan: {perihal}.\n\nDemikian surat rekomendasi ini dibuat untuk dipergunakan sebagaimana mestinya."],
            ['Surat Tugas', 'ST', 'SURAT TUGAS',
                "Yang bertanda tangan di bawah ini, Ketua Jurusan Hukum Bisnis Fakultas Ilmu Sosial dan Ilmu Politik Universitas Siliwangi, menugaskan:\n\n{$mhs}\n\nuntuk melaksanakan: {perihal}.\n\nDemikian surat tugas ini dibuat untuk dilaksanakan dengan penuh tanggung jawab."],
            ['Surat Lainnya', 'SL', null, "Dengan hormat,\n\n\n\n{$penutup}"],
        ];

        foreach ($types as [$name, $code, $title, $body]) {
            $type = LetterType::firstOrNew(['code' => $code]);
            $type->name = $name;
            $type->is_active = true;
            // Jangan timpa templat yang sudah disunting.
            $type->default_title ??= $title;
            $type->body_template ??= $body;
            $type->save();
        }

        $password = config('simas.seed_password');

        // GANTI email & password ini setelah login pertama.
        User::updateOrCreate(['email' => 'admin@simas.test'], [
            'name' => 'Admin Prodi', 'role' => Role::Admin, 'password' => $password, 'is_active' => true,
        ]);
        User::updateOrCreate(['email' => 'kaprodi@simas.test'], [
            'name' => 'Kaprodi Hukum Bisnis', 'role' => Role::Kaprodi, 'password' => $password, 'is_active' => true,
        ]);
    }
}
