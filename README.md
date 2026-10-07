# SkillMatch Volunteer — kode aplikasi dan panduan Windows

Paket awal aplikasi Laravel yang dapat dijalankan secara lokal. Backend: Laravel 13/PHP ≥8.3. Database utama: MySQL/InnoDB. Tampilan: Blade, Tailwind CSS 4, Alpine CSP. Source code, migration, seeder, dan pengujian tersedia; tabel tidak perlu dibuat manual satu per satu.

**Mulai dari bagian 1–6 untuk menjalankan aplikasi.** `docs/STATUS_IMPLEMENTASI.md` menjelaskan fitur yang tersedia dan bagian PRD yang masih perlu dikembangkan sebelum deployment publik. Proyek ini bukan hasil audit keamanan produksi.

## 1. Isi paket

- `app/Http/Controllers`: autentikasi, profil, event, lamaran, dokumen, seleksi, dashboard, Admin, pembayaran.
- `app/Http/Requests/EventRequest.php`: validasi event; validasi alur lain berada di controller.
- `app/Services`: matching, recruitment, assessment, pembayaran sandbox.
- `app/Policies/DocumentPolicy.php`: akses dokumen pemilik dan perekrut terkait.
- `app/Jobs/ScanDocument.php`: karantina dan pemeriksaan ClamAV.
- `database/migrations`: tabel, relasi, indeks, unique constraint.
- `database/seeders`: master data dan demo lokal.
- `resources/views`, `resources/css`, `resources/js`: tampilan dan interaksi.
- `routes/web.php`, `routes/console.php`: route aplikasi dan tugas terjadwal.
- `tests/Feature/SkillMatchTest.php`: pengujian fungsi serta akses lintas akun.
- `docs/PRD.md`, `docs/design.md`: spesifikasi acuan.
- `.env.example`, `composer.lock`, `package-lock.json`: konfigurasi contoh dan versi dependensi.
- `scripts/setup.ps1`: bantuan setup yang tidak menghapus database atau menimpa .env.

Folder `vendor`, `node_modules`, database uji, dokumen uji, akun/password uji, dan `.env` pengembang tidak dimasukkan dalam ZIP. Dependensi diunduh saat instalasi. `public/build` disertakan agar hasil build tersedia, tetapi lakukan build ulang setelah mengubah tampilan.

## 2. Siapkan lingkungan sekali saja

Diperlukan:

1. PHP 8.3 atau lebih baru yang kompatibel dengan dependensi pada lockfile; gunakan PHP 8.3/8.4 untuk panduan ini.
2. Composer 2.
3. Node.js 22.12+ atau 24 LTS dan npm.
4. MySQL 8 dengan InnoDB, layanan berjalan di port yang Anda gunakan.
5. Ekstensi PHP yang diperlukan Laravel/Composer, termasuk `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `curl`, `dom`, `xml`, `xmlwriter`, `ctype`, `tokenizer`, `filter`, `session`, dan `pdo`. Untuk pengujian SQLite tambahkan `pdo_sqlite`/`sqlite3`.

Herd dapat menjalankan PHP dan website. Pastikan MySQL tersedia secara terpisah bila instalasi Herd Anda tidak menyediakannya. Hindari menjalankan dua layanan MySQL pada port 3306 yang sama.

Buka terminal VS Code atau PowerShell:

```powershell
php -v
composer --version
node -v
npm -v
php -m
```

Jika perintah tidak dikenali, selesaikan instalasi/PATH, lalu buka terminal baru. Jangan mengganti stack proyek menjadi SQLite hanya untuk menghindari setup MySQL; SQLite dipakai pada sebagian pengujian otomatis.

## 3. Ekstrak dan siapkan database

Ekstrak folder `skillmatch` dari ZIP, misalnya ke `C:\Herd\skillmatch`, lalu buka folder tersebut di VS Code. Terminal harus berada pada folder yang memiliki file `artisan`:

```powershell
cd C:\Herd\skillmatch
```

Melalui MySQL Workbench, CLI MySQL, atau phpMyAdmin yang terhubung ke server MySQL, buat database kosong. Contoh SQL untuk komputer lokal:

```sql
CREATE DATABASE skillmatch CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'skillmatch'@'localhost' IDENTIFIED BY 'GANTI_DENGAN_PASSWORD_ANDA';
GRANT ALL PRIVILEGES ON skillmatch.* TO 'skillmatch'@'localhost';
```

Ganti password contoh sebelum menjalankannya. Akun tersebut hanya berhak atas database `skillmatch`, bukan seluruh server. Hak perubahan skema diperlukan untuk migration pada lingkungan development. Untuk produksi, pisahkan akun migration dan akun aplikasi yang lebih terbatas. Jika akun/database sudah ada, gunakan yang sesuai tanpa menjalankan CREATE ulang.

Salin konfigurasi:

```powershell
Copy-Item .env.example .env
```

Isi bagian berikut di `.env`:

```dotenv
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=skillmatch
DB_USERNAME=skillmatch
DB_PASSWORD="password-yang-Anda-tetapkan"
```

Gunakan tanda kutip jika password mengandung spasi atau `#`. Jangan mengunggah `.env` ke GitHub. Server database dan nama akun harus sesuai dengan instalasi Anda.

## 4. Instalasi kode, tabel, dan frontend

Jalankan berurutan; berhenti dan baca pesan jika suatu langkah gagal:

```powershell
composer install
php artisan key:generate
php artisan config:clear
php artisan migrate
php artisan db:seed
npm ci
npm run build
```

- `composer install` memasang versi PHP library sesuai `composer.lock`.
- `key:generate` hanya untuk instalasi baru; jangan mengganti key aplikasi yang sudah menyimpan data terenkripsi.
- `migrate` membuat tabel dari kode migration yang sudah disediakan.
- `db:seed` mengisi skill serta paket Free/Standard/Premium; harga Standard/Premium dibiarkan kosong, bukan diisi angka karangan.
- `npm ci` memakai `package-lock.json`; tidak perlu `npm update`.
- `npm run build` menghasilkan CSS/JS aplikasi. Untuk baseline ini gunakan hasil build, bukan Vite dev server, agar CSP tetap ketat.

Alternatif bantuan setelah `.env` diisi:

```powershell
.\scripts\setup.ps1
```

Jika kebijakan komputer melarang skrip PowerShell, gunakan perintah manual di atas. Skrip tidak membuat database MySQL, tidak menghapus tabel, dan tidak menimpa `.env` yang ada. Jangan memakai `migrate:fresh` pada database berisi data yang ingin disimpan.

## 5. Buat akun untuk mencoba aplikasi

### Pilihan A — akun dan event demo lokal

Atur:

```dotenv
APP_ENV=local
SKILLMATCH_DEMO=true
```

Lalu:

```powershell
php artisan config:clear
php artisan skillmatch:demo
```

Perintah meminta password minimal 12 karakter. Gunakan password uji sendiri; password tidak disertakan dalam ZIP. Akun baru yang dibuat:

| Role | Email |
|---|---|
| Admin | admin@skillmatch.test |
| Organizer | organizer@skillmatch.test |
| Volunteer | volunteer@skillmatch.test |

Ketiganya menggunakan password yang Anda masukkan ketika dibuat. Akun yang sudah ada tidak ditimpa saat perintah diulang. Akun demo sudah terverifikasi untuk memudahkan pengujian lokal. Jangan aktifkan mode demo di internet.

Seeder membuat tiga event sintetis; posisi demo tidak mewajibkan CV agar alur dasar dapat dicoba sebelum ClamAV terpasang. Ini bukan bypass pemindaian: file yang benar-benar diunggah tetap dikarantina sampai pemindai memeriksanya.

### Pilihan B — Admin sendiri

```powershell
php artisan skillmatch:admin
```

Masukkan nama, email, dan password kuat. Admin dibuat dari CLI tepercaya, tidak lewat registrasi publik. Volunteer dan Organizer mendaftar lewat `/register`.

## 6. Jalankan aplikasi

Terminal pertama:

```powershell
php artisan serve --host=127.0.0.1 --port=8000 --no-reload
```

Buka [http://127.0.0.1:8000](http://127.0.0.1:8000).

Terminal kedua, tetap pada folder proyek:

```powershell
php artisan queue:work --tries=2 --timeout=110
```

Terminal ketiga:

```powershell
php artisan schedule:work
```

Worker memproses pemeriksaan dokumen. Scheduler menyelesaikan assessment yang waktunya habis dan memeriksa ulang lamaran pending. Hentikan masing-masing dengan Ctrl+C. Bila konfigurasi job berubah, jalankan `php artisan queue:restart` lalu mulai worker lagi.

Alternatif Herd: park/link folder `skillmatch`, gunakan domain lokal yang diberikan Herd, ubah `APP_URL`, lalu `php artisan config:clear`. Jangan menjalankan `artisan serve` dan Herd pada URL yang sama. Gunakan URL yang sama saat login, mengunggah, dan membuka tautan email agar session/cookie konsisten.

URL dan konteks login (PRD v1.3):

| Area | Login | Halaman awal | Akun |
|---|---|---|---|
| Volunteer | `/login` | `/volunteer/aktivitas` | `/volunteer/profile` |
| Organizer | `/login` | `/organizer/aktivitas` | `/organizer/profile` |
| Admin | `/admin/login` | `/admin/dashboard` | `/admin/account` |

Login umum menolak Admin; login Admin menolak Volunteer/Organizer. Guard `web` dan `admin` memakai namespace autentikasi terpisah dalam sesi. Keduanya dapat aktif bersamaan; logout hanya mengakhiri konteks terkait dan meregenerasi sesi/CSRF. Logout Admin kembali ke login Admin. Redirect sesudah login selalu ke halaman role sendiri; intended URL dari sesi tidak dipercaya. URL dashboard pengguna lama mengarah ke aktivitas dengan pemeriksaan role.

Volunteer/Organizer menggunakan navbar website, daftar aktivitas, dan notifikasi. Organizer memiliki Transaksi Saya serta navigasi lokal Posisi, Assessment, Pelamar, Kehadiran, dan Paket. Panel Admin memiliki modul pengguna, moderasi, master data, paket, verifikasi ulang transaksi sandbox, konten/pengaturan terbatas, audit, serta akun sendiri. Tautan Lihat Website membuka web utama tanpa mengubah role.

## 7. Uji alur lengkap tanpa pembayaran

1. Masuk sebagai Volunteer demo; buka Profil dan lihat skill/ketersediaan.
2. Buka Cari Event → event demo → Lamar Posisi.
3. Buka draft → Kirim Lamaran. Posisi demo tidak mewajibkan CV.
4. Mulai assessment, pilih jawaban, klik Simpan Jawaban bila ingin menyimpan sementara, lalu Kirim & Akhiri.
5. Keluar; masuk sebagai Organizer demo.
6. Event Saya → Kelola Event → Tinjau Pelamar → Detail.
7. Tinjau skor, assessment, dan dokumen bila tersedia. Terima atau Tolak dengan alasan.
8. Masuk kembali sebagai Volunteer; lihat hasil seleksi dan notifikasi.
9. Untuk mencoba attendance, gunakan event yang jadwalnya sudah dimulai. Penyelesaian event baru tersedia setelah waktu akhir dan seluruh attendance sudah diisi. Jangan mengubah jam komputer untuk memaksa status; buat data uji terpisah.

Untuk event baru: Organizer membuat draft → tambah posisi dan soal assessment → Ajukan Verifikasi → Admin menyetujui → paket gratis dipublikasikan. Paket berbayar menunggu pembayaran.

## 8. Konfigurasi unggah dokumen dan ClamAV

File berada di `storage/app/private/documents`, metadata di tabel `documents`. Tidak perlu `php artisan storage:link` untuk CV; link publik hanya untuk aset publik jika nantinya digunakan.

Default `DOCUMENT_SCANNER=disabled` berarti file **tidak akan dianggap aman**. Setelah worker memprosesnya, status menjadi `scan_failed`; perekrut tidak dapat mengunduhnya. Untuk memproses unggahan:

1. Pasang ClamAV CLI dari distribusi resminya untuk OS Anda.
2. Konfigurasikan dan perbarui database signature menggunakan prosedur ClamAV (`freshclam` pada instalasi yang mendukungnya).
3. Pastikan `clamscan --version` berhasil dan uji satu PDF sintetis di terminal.
4. Isi `.env`, contoh path Windows:

```dotenv
DOCUMENT_SCANNER=clamav
CLAMAV_BINARY="C:/Program Files/ClamAV/clamscan.exe"
```

5. Jalankan `php artisan config:clear`, mulai ulang worker, lalu unggah CV PDF maksimal 5 MB.
6. Status: Sedang Diperiksa → Siap Diunduh jika lolos; file ditolak/error tetap tidak dapat diunduh.
7. File berstatus Pemeriksaan Gagal dapat dijadwalkan ulang dari halaman lamaran setelah pemindai aktif.

Scanner memeriksa malware dan menolak file terenkripsi yang ditandai scanner. MIME/ukuran dan ekstensi juga diperiksa server. Jangan menambahkan tombol “anggap aman” untuk melewati pemindaian. Dokumen `ready` hanya dapat diunduh pemilik atau perekrut event terkait; Admin tidak otomatis diberi akses isi CV.

## 9. Email verifikasi dan pemulihan password

Default `MAIL_MAILER=log`: tidak mengirim email sungguhan; untuk pengujian lokal cari tautan pada `storage/logs/laravel.log`. Jangan membagikan log karena dapat mengandung tautan reset/verifikasi.

Untuk benar-benar mengirim email, isi SMTP milik Anda (`MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`) sesuai penyedia. Jalankan `php artisan config:clear`. Akun baru perlu verifikasi sebelum melamar atau mengelola rekrutmen. Akun demo hanya terverifikasi otomatis pada mode local.

## 10. Midtrans Sandbox

Kode integrasi sandbox tersedia, tetapi memerlukan akun/kredensial Anda dan harga paket yang disepakati.

1. Ambil **Server Key Sandbox**, isi `MIDTRANS_SERVER_KEY` di `.env` dan jangan masukkan key ke frontend.
2. Admin → Paket → isi harga uji Standard/Premium. Jelaskan kepada tim bahwa ini nominal pengujian, bukan harga final produk.
3. Organizer membuat event dengan paket itu → ajukan → Admin menyetujui.
4. Organizer membuka event → Bayar Paket → diarahkan ke halaman checkout Midtrans sandbox.
5. Setelah selesai, gunakan tombol Periksa Status. Server mengambil status transaksi dari Midtrans; kembali dari halaman pembayaran saja tidak mengubah order menjadi paid.
6. Untuk notifikasi otomatis, endpoint `/payments/midtrans/notification` harus dapat diakses Midtrans melalui URL HTTPS publik yang Anda kendalikan. Localhost tidak dapat menerima webhook eksternal. Pengujian lokal tetap bisa memakai Periksa Status tanpa membuka aplikasi ke publik.

Kode memverifikasi signature, memeriksa order dan nominal melalui API status, menangani notifikasi berulang, serta tidak mengaktifkan event batal/ditangguhkan. Jangan menggunakan kartu atau uang nyata. Error jaringan tidak menghasilkan pembayaran sukses palsu. Jika checkout gagal setelah order dibuat, periksa status sebelum mencoba kembali; dukungan otomatis untuk semua kasus refund/chargeback belum termasuk paket awal.

## 11. Pengujian dan pengembangan berikutnya

```powershell
php artisan test
```

`phpunit.xml` memakai database SQLite in-memory agar tes tidak menghapus database kerja. Extensi SQLite diperlukan untuk perintah ini. Untuk menguji MySQL, gunakan konfigurasi test terpisah dengan database khusus berakhiran `_test`; lihat `docs/PENGUJIAN.md`. Jangan arahkan `RefreshDatabase` ke data kerja.

Setelah perubahan tampilan:

```powershell
npm run build
```

Setelah menambah migration:

```powershell
php artisan migrate
```

PRD dan design.md adalah target pengembangan, bukan bukti seluruh fitur selesai. Baca matriks status implementasi sebelum menyusun laporan atau melakukan deployment.

## 12. Troubleshooting

| Gejala | Periksa / tindakan |
|---|---|
| `php`/`composer` tidak dikenali | PHP/Composer dan PATH; restart terminal. |
| `could not find driver` | Aktifkan `pdo_mysql` di PHP CLI yang ditampilkan `php --ini`; gunakan `pdo_sqlite` untuk tes. |
| `Unknown database skillmatch` | Buat database kosong dan cocokkan `DB_DATABASE`. |
| `Access denied for user` | Username, host, password, dan hak database di `.env`; lalu `config:clear`. |
| `No application encryption key` | Jalankan `key:generate` hanya pada instalasi baru. |
| Port 8000 tidak bisa digunakan | Coba `php artisan serve --host=127.0.0.1 --port=8080 --no-reload`; ubah `APP_URL` ke port yang dipakai. |
| `Vite manifest not found` | `npm ci`, kemudian `npm run build`. |
| CSS tidak berubah | Build ulang dan refresh browser; pastikan membuka proyek yang benar. |
| `419 Page Expired` | Refresh, login ulang, konsisten menggunakan satu hostname, pastikan session database dan APP_KEY benar. |
| Dokumen terus menunggu | Worker belum berjalan; cek `php artisan queue:failed` dan konfigurasi scanner. |
| `scan_failed` | ClamAV/path/signature belum siap; perbaiki lalu Periksa Lagi. Tidak perlu mengubah status lewat database. |
| Paket berbayar tidak bisa checkout | Harga belum diatur, event belum approved, atau Server Key sandbox belum diisi. |
| Tautan verifikasi salah host | Perbaiki APP_URL, config:clear, lalu kirim ulang tautan. |

Referensi resmi: [Laravel](https://laravel.com/docs/13.x), [Midtrans Snap](https://docs.midtrans.com/reference/backend-integration), [Midtrans notifications](https://docs.midtrans.com/docs/https-notification-webhooks), [ClamAV](https://docs.clamav.net/).


## 12. Menjalankan revisi PRD v1.3 / design v1.1

Dari instalasi yang sudah berjalan, tanpa menimpa `.env` atau key:

```powershell
php artisan config:clear
php artisan migrate --force --no-interaction
npm run build
composer test -- --compact
php artisan queue:restart
```

Jalankan worker dan scheduler pada terminal terpisah seperti bagian 6. Migration `2026_10_01_000001` menyediakan timestamp aktivasi dan settings; `2026_10_01_000002` menambahkan master kategori/kota, status skill, dan indeks analitik. Nama kategori/kota yang sudah digunakan dipertahankan. Tidak ada backfill tanggal historis buatan. Paket dan nominal order tetap memakai snapshot.

Analitik Admin mendukung 7/30 hari atau rentang maksimal 366 hari, memakai batas hari WIB terhadap timestamp UTC. Akun/event aktif adalah kondisi saat ini. Akun baru/event dibuat memakai tanggal pembuatan; lamaran memakai waktu pengiriman; transaksi sandbox memakai pembayaran terverifikasi pertama; aktivasi memakai aktivasi pertama per event. Grafik menyediakan tabel harian. Kegagalan query menampilkan pesan error dengan HTTP 503, bukan angka nol.

Font Plus Jakarta Sans disajikan lokal dari `resources/fonts` (SIL OFL pada `OFL.txt`). Ilustrasi komunitas dan ikon outline adalah SVG orisinal dalam komponen Blade, tanpa foto pihak ketiga. Kartu menggunakan fallback kategori; upload poster belum tersedia. Tidak ada dependensi baru atau perubahan lockfile.

### Review browser yang dapat diulang

```powershell
node scripts/visual-review.mjs
```

Memerlukan Node 22+ dengan WebSocket bawaan, PHP SQLite, dan Chrome. Di Windows runner mendeteksi PHP Herd; set `PHP_BINARY` ke executable PHP dan `CHROME_PATH` ke executable Chrome bila lokasinya berbeda. Runner memilih port localhost sementara secara otomatis. Runner menggunakan database SQLite unik di `storage/app/visual-review`, akun sintetis, dan profil Chrome terpisah. Data MySQL serta `.env` aplikasi tidak berubah. Jangan gunakan fixture/akun ini pada server publik. Screenshot dan `report.json` berada di folder tersebut; server dan browser dihentikan setelah review. File database/profil hasil uji dapat dibersihkan setelah tidak diperlukan.

Pengujian revisi ada di `tests/Feature/RevisionTest.php`; skenario UAT-23–30 mencakup role, guard, logout, isolasi data, periode WIB, kegagalan analitik, callback berulang, audit, serta snapshot. Lihat `docs/PENGUJIAN.md` untuk hasil dan batas verifikasi. Konfigurasi SMTP, ClamAV, dan kredensial Midtrans sandbox tetap diperlukan untuk integrasi eksternal; status form atau callback browser saja tidak membuktikan layanan tersebut bekerja.


### Regresi MySQL dan konkurensi lokal

Untuk lingkungan MySQL localhost dengan akun yang berhak membuat database/akun uji:

```powershell
php artisan config:clear
php tests/Support/mysql-review.php
```

Runner membuat database unik `skillmatch_review_<acak>_test` dan akun khusus sementara, menjalankan PHPUnit serta kompetisi dua proses untuk satu slot, lalu membersihkan sumber daya buatannya. Database kerja tidak dijadikan target RefreshDatabase. Alternatif akun uji yang disiapkan manual tetap dijelaskan di `docs/PENGUJIAN.md`.

Hasil pemeriksaan lanjutan: 30 tes/225 assertion lulus pada MySQL maupun SQLite, dan pemeriksaan konkurensi lulus. Integrasi lokal masih memakai mailer log, scanner nonaktif, dan key Midtrans belum terisi; konfigurasi eksternal diperlukan sebelum uji end-to-end layanan tersebut.
