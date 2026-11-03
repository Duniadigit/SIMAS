# SIMAS – Sistem Informasi Manajemen Arsip Surat (Laravel 12)

Kode ini adalah **lapisan di atas instalasi Laravel 12 kosong**. Salin isi folder ini ke root project Anda
(timpa file yang bentrok: `User.php`, `AppServiceProvider.php`, `DatabaseSeeder.php`, `routes/web.php`,
`resources/css/app.css`, `resources/js/app.js`).

## 1. Pasang

```bash
composer require setasign/fpdi          # untuk TTD otomatis
php artisan migrate
php artisan db:seed                     # jenis surat + akun awal
npm install && npm run build            # Tailwind 4 (bawaan Laravel 12)
```

`.env` (minimal):

```
APP_NAME=SIMAS
APP_ENV=production
APP_DEBUG=false
APP_URL=https://alamat-simas.unsil.ac.id      # harus sama persis, dipakai untuk link unduh bertanda tangan
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
DB_CONNECTION=mysql
DB_DATABASE=...  DB_USERNAME=...  DB_PASSWORD=...
SESSION_DRIVER=file                           # lihat bagian performa
CACHE_STORE=file
SIMAS_SEED_PASSWORD=isi-password-kuat
SIMAS_NUMBER_FORMAT="{seq}/UN58.HB/{code}/{year}"   # SESUAIKAN dengan tata naskah dinas prodi
SIMAS_ADMIN_CAN_APPROVE=false
```

Akun awal (dari seeder): `admin@simas.test` dan `kaprodi@simas.test`. **Ganti email & password sebelum go-live.**
Kaprodi login → menu *Profil & TTD* → unggah PNG tanda tangan transparan.

## 2. Peta fitur → dokumen rancangan

| Dokumen | Implementasi |
|---|---|
| Form pengajuan Mahasiswa/Dosen (tanpa login) | `/ajukan` (honeypot + throttle) |
| Tracking nomor registrasi / NPM | `/lacak` (throttle, unduh lewat URL bertanda tangan 30 menit) |
| Alur keluar: Diajukan → Sedang Dibuat → Menunggu Persetujuan → Selesai (+ Revisi/Ditolak) | `OutgoingWorkflow` |
| Penomoran surat otomatis saat ACC | `LetterNumberService` (counter dikunci, tanpa nomor ganda) |
| TTD otomatis pada surat ACC | `SignatureStamper` (FPDI) |
| Surat masuk + scan PDF, disposisi digital Kaprodi, selesai | `IncomingWorkflow` |
| Statistik bulan ini + reminder | `StatsService` + `panel/dashboard` |
| Multi-filter arsip (tanggal, kategori, perihal) + cetak laporan | `/panel/arsip`, `/panel/arsip/cetak` |
| Log aktivitas (siapa, apa, kapan) | tabel `activity_logs`, `/panel/log-aktivitas` |
| PDF terproteksi | disk `local` (privat, di luar `public/`), hanya lewat controller |

## 3. Catatan penting & keputusan yang perlu Anda konfirmasi

1. **Siapa boleh ACC.** Dokumen menulis Admin *dan* Kaprodi bisa ACC, sementara modul verifikasi hanya menyebut Kaprodi.
   Default: hanya Kaprodi. Set `SIMAS_ADMIN_CAN_APPROVE=true` bila admin juga boleh (TTD tetap TTD Kaprodi aktif).
2. **Format nomor surat** hanyalah contoh. Sesuaikan `SIMAS_NUMBER_FORMAT`.
3. **FPDI gratis** hanya membaca PDF tanpa *compressed xref* (PDF ≤ 1.4). Draf dari Word biasanya aman bila diekspor
   sebagai PDF/A; bila gagal, surat tetap selesai **tanpa TTD** dan alasannya tercatat di log aktivitas.
   Alternatifnya parser komersial FPDI-PDF-Parser.
4. **Pelacakan lewat NPM** menampilkan daftar permohonan (perihal & status) siapa pun yang mengetik NPM tersebut. Ini sesuai
   dokumen, tetapi pertimbangkan privasi; bila perlu, wajibkan nomor registrasi saja.
5. Fitur di luar dokumen yang saya tambahkan karena perlu: halaman Profil/TTD, status *Revisi* dan *Ditolak*.
   Belum ada: manajemen akun/jenis surat lewat UI (dilakukan via seeder/tinker), notifikasi email/WhatsApp.

## 4. Performa untuk ratusan–ribuan pengguna

Sudah di kode: index pada kolom filter/urut, `select` kolom seperlunya + eager loading (tanpa N+1; lazy loading
dilarang saat development), paginasi di semua daftar, statistik di-cache dan dibuang saat data berubah,
status diubah dalam transaksi dengan `lockForUpdate` (tanpa ACC ganda/nomor ganda), tanpa queue/Redis wajib
(cocok untuk cPanel/VPS).

Yang harus Anda atur di server:

```bash
composer install --no-dev --optimize-autoloader
php artisan optimize          # cache config, route, view, event
php artisan migrate --force
```

- **Aktifkan OPcache** (php.ini: `opcache.enable=1`, `opcache.validate_timestamps=0` di production, restart PHP-FPM tiap deploy).
- `SESSION_DRIVER=file` (atau `redis` bila tersedia). Driver `database` bawaan membuat tiap request menulis ke MySQL.
- `CACHE_STORE=file` atau `redis`.
- PHP-FPM: naikkan `pm.max_children` sesuai RAM (± 40–60 MB per proses). `upload_max_filesize=6M`, `post_max_size=8M`.
- Document root web server harus mengarah ke folder `public/`.
- MySQL InnoDB, `innodb_buffer_pool_size` ± 50–70% RAM jika server khusus.
- Pencarian perihal memakai `LIKE '%…%'`; nyaman sampai puluhan ribu surat. Bila arsip jauh lebih besar, ganti dengan indeks FULLTEXT.
- Backup rutin: database **dan** `storage/app/private/` (berisi seluruh PDF).

> Saya belum bisa menguji beban atau menjalankan aplikasi ini: lingkungan saya memblokir Packagist sehingga Laravel tidak
> bisa dipasang. Jalankan uji beban (mis. k6/ab) pada server Unsil sebelum rilis.
