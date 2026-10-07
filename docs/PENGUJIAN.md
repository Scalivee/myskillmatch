# Pengujian

Revisi PRD v1.3/design v1.1 diperiksa pada 1 Oktober 2026 di Windows/Herd, PHP 8.4, Laravel 13.34.0, PHPUnit 12.5.37, Vite 7.3.6, dan Chrome headless.

## Hasil revisi yang benar-benar dijalankan

- `composer test -- --compact`: **30 tes, 225 assertion lulus** pada SQLite in-memory; suite yang sama juga lulus pada MySQL melalui akun khusus database sementara. Mencakup 16 tes bisnis lama dan 14 tes revisi.
- UAT-23–30: login/guard terpisah, redirect tetap sesuai role, logout per konteks, penolakan seluruh route Admin bagi non-Admin, aktivitas milik akun aktif, batas hari WIB, data kosong vs error 503, timestamp pengiriman vs pembaruan, idempotensi pembayaran/aktivasi, snapshot paket, alasan moderasi, dan audit identitas pelaku.
- `npm run build`: berhasil; empat bobot Plus Jakarta Sans disertakan dalam build. Tidak ada penambahan dependensi.
- Pint pada semua PHP yang diubah: berhasil. Proyek merupakan salinan tanpa metadata Git, sehingga formatter dijalankan pada daftar file eksplisit.
- Migration tambahan master data dan indeks berhasil pada MySQL aplikasi, tanpa `migrate:fresh`, perubahan `.env`, atau penghapusan data.
- Query analitik MySQL diperiksa **baca saja** pada database lokal: empat seri harian berhasil. Suite regresi MySQL penuh kemudian dijalankan melalui `php tests/Support/mysql-review.php`: 30 tes/225 assertion lulus. Tes dua proses menghasilkan tepat satu penerimaan dan satu penolakan kuota. Database serta dua entri host akun uji sementara dibersihkan setelah selesai; logika lock/kuota/seleksi tidak diubah.
- `node scripts/visual-review.mjs`: **81 screenshot responsif** (27 halaman/state × 375/768/1440 px), ditambah satu screenshot state submit diproses. Tidak ditemukan body overflow horizontal atau error browser pada runner final; font lokal terdeteksi dimuat.
- Pemeriksaan browser meliputi landing, katalog/detail, hasil pencarian kosong, login, validasi gagal, aktivitas kedua role, onboarding Volunteer, profil, lamaran/unggahan, assessment, pengelolaan event, pelamar, kehadiran, transaksi, login Admin, dashboard berisi/kosong, dan seluruh modul Admin.
- Kontras pasangan token utama dihitung: tombol primer 6,29:1, teks utama 15,59:1, metadata 6,18:1, badge status 8,99:1, dan teks error 6,47:1. Ini bukan audit seluruh pasangan warna/state.
- Keyboard otomatis: skip link, buka menu mobile, tutup dengan Escape, dan fokus ringkasan validasi lulus. Tabel yang lebar menggunakan kontainer scroll; hasil “tanpa overflow” berarti body halaman tidak meluber, bukan semua tabel dipaksa muat satu layar.
- Tabel moderasi yang semula terlalu memadat di ponsel diperbaiki dengan lebar minimum serta kontainer gulir yang dapat difokuskan lewat keyboard. Screenshot representatif ditinjau secara visual, termasuk desktop landing/aktivitas/Admin, mobile landing/onboarding/pengelolaan event/form validasi, tablet katalog/aktivitas, unggahan, assessment, dan moderasi.

Bukti lokal: `storage/app/visual-review/report.json` dan screenshot `*.png`. Data pada screenshot adalah fixture sintetis berlabel demo, bukan angka operasional atau harga paket produksi. Nilai Rp10.000 pada fixture transaksi adalah nominal uji, tidak disimpan sebagai konfigurasi harga aplikasi.

## Batas hasil

SMTP, ClamAV asli, dan Midtrans sandbox dengan kredensial nyata belum diuji end-to-end. Tes gateway memakai HTTP fake; file fixture berstatus gagal tidak menyediakan CV nyata. Tidak ada klaim production-ready, pentest, MFA, audit pembaca layar, audit kontras seluruh state, uji performa/beban, atau lulus seluruh checklist PRD. Screenshot fallback kategori tidak membuktikan upload poster tersedia. Kehadiran dan pembayaran mempertahankan otorisasi/backend lama; regresinya diperiksa lewat suite lokal, bukan transaksi uang nyata.

Runner browser memakai database SQLite unik untuk pengujian dan tidak mengganti konfigurasi database aplikasi MySQL. Runtime worker/scheduler tetap memerlukan proses tersendiri. Hasil baseline Linux/MySQL 8.0.46 tetap merupakan riwayat lama; suite MySQL dan kompetisi dua proses kini juga telah dijalankan pada lingkungan Windows lokal revisi ini.

## Kesiapan integrasi lokal

Pemeriksaan konfigurasi hanya mencatat status, tanpa menampilkan secret: mailer masih `log`, kredensial SMTP belum tersedia, scanner `disabled`, dan key Midtrans sandbox belum terisi. Karena itu verifikasi email sungguhan, pemindaian ClamAV, serta pembayaran sandbox eksternal belum diuji. Tidak ada email atau transaksi eksternal yang dikirim dalam pemeriksaan ini.

## Runner MySQL terisolasi

```powershell
php artisan config:clear
php tests/Support/mysql-review.php
```

Runner hanya menerima APP_ENV=local, MySQL localhost, konfigurasi tanpa cache, dan tanpa DB_URL. Akun MySQL lokal harus berhak membuat database serta akun uji. Runner membuat nama database acak berakhiran `_test`, akun sementara yang diberi hak pada database itu, lalu menjalankan suite dan pemeriksaan konkurensi. Tidak memakai ulang database yang sudah ada. Blok cleanup menghapus hanya database/akun yang berhasil dibuat pada invocation tersebut. Password acak diteruskan melalui environment child process dan tidak ditampilkan. Bila proses dihentikan paksa, cleanup mungkin tidak berjalan; nama database uji tercatat pada output untuk penanganan operator.

Satu assertion snapshot disesuaikan agar membandingkan snapshot tersimpan sebelum/sesudah perubahan paket. MySQL dapat menormalkan urutan key JSON; urutan key bukan perubahan nilai historis.

## SQLite (default)

Dari folder proyek setelah instalasi dependensi:

```powershell
php artisan config:clear
php artisan test
```

Diperlukan ekstensi PHP `pdo_sqlite` dan `sqlite3`. `phpunit.xml` memilih database `:memory:`. Data aplikasi lokal tidak perlu dikosongkan.

## MySQL pada database khusus pengujian

Buat database **terpisah** bernama `skillmatch_test` dan akun yang hanya memiliki akses ke database itu. Jangan gunakan akun atau database produksi: RefreshDatabase membuat ulang skema pengujian.

Buka terminal PowerShell baru, lalu atur konfigurasi sesi terminal tersebut:

```powershell
$env:APP_ENV = 'testing'
$env:DB_CONNECTION = 'mysql'
$env:DB_HOST = '127.0.0.1'
$env:DB_PORT = '3306'
$env:DB_DATABASE = 'skillmatch_test'
$env:DB_USERNAME = 'skillmatch_test'
$env:DB_PASSWORD = 'PASSWORD_AKUN_TEST_ANDA'
$env:DB_URL = ''
php artisan config:clear
php artisan test
```

Untuk pengujian konkurensi, tetap dalam terminal tersebut:

```powershell
php artisan migrate --force
php tests/Support/concurrency.php
```

Skrip konkurensi hanya mengizinkan APP_ENV=testing dan database MySQL berakhiran `_test`. Skrip membuat data sintetis lalu membersihkannya; seeder master tetap dapat tersimpan pada database test. Output yang diharapkan:

```text
PASS: two concurrent requests, one accepted, one quota rejection.
```

Tutup terminal pengujian setelah selesai agar variabel sesi tidak dipakai saat menjalankan aplikasi biasa. Pastikan tidak ada konfigurasi cache produksi ketika menjalankan pengujian.
