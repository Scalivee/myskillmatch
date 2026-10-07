# SkillMatch Volunteer — Design Specification

Versi 1.1 · 1 Oktober 2026  
Dokumen pendamping: `PRD.md` versi 1.3 (salinan proyek PRD SkillMatch Volunteer)  
Stack: Laravel/PHP, Blade, Tailwind CSS, Alpine.js, MySQL  
Bahasa antarmuka: Bahasa Indonesia · Zona waktu tampilan: WIB

## 1. Tujuan dan otoritas dokumen

Dokumen ini menjadi acuan visual dan interaksi untuk website rekrutmen volunteer SkillMatch. Cakupannya meliputi halaman publik, Ringkasan Aktivitas Volunteer, Ringkasan Aktivitas Organizer, panel Admin, formulir, dokumen, assessment, seleksi, dan pembayaran. Ini merupakan spesifikasi desain untuk implementasi, bukan screenshot aplikasi yang sudah dibangun.

Urutan acuan: instruksi terbaru pengguna → PRD untuk fungsi, aturan bisnis, keamanan, dan cakupan → design.md untuk tampilan dan interaksi. Jika ada konflik, pertahankan aturan PRD dan dokumentasikan konflik; jangan mengubah aturan bisnis demi tampilan. Dokumen ini tidak mengubah isi PRD secara otomatis.

Keputusan visual di sini adalah baseline implementasi. Agent boleh menyesuaikan spacing kecil, proporsi responsif, dan pemilihan ikon dalam keluarga yang sama. Agent tidak boleh mengganti role, stack, rumus Match Score, alur persetujuan/pembayaran, aturan akses dokumen, atau menambah fitur berbayar tanpa dasar produk.

Paket Free/Standard/Premium dan batas 2/5/10 posisi, 30/100/300 lamaran, serta 7/30/60 hari berasal dari usulan percakapan, belum keputusan final PRD. Gunakan sebagai fixture prototipe bertanda “Contoh paket”, bukan konfigurasi produksi. Harga berbayar belum ditetapkan. Jangan membuat harga, diskon, testimoni, statistik, atau label “Terlaris” palsu.

## 2. Penerapan skill UI/UX Pro Max

Sumber skill: arsip unggahan `ui-ux-pro-max-skill-main.zip`, entry `.claude/skills/ui-ux-pro-max/SKILL.md`. Skill dibaca dan mesin pencarian lokalnya digunakan; tidak diperlukan instalasi plugin tambahan untuk membaca desain ini.

| Pencarian | Hasil yang relevan | Penerapan pada SkillMatch |
|---|---|---|
| `volunteer recruitment platform accessible --design-system` | Flat Design, alur bertahap, keterbacaan, efek ringan | Permukaan solid, hierarki jelas, formulir lamaran bertahap; satu CTA utama per layar. |
| `error summary validation --domain ux` | Error summary dengan fokus dan tautan ke field; error inline | Ringkasan kesalahan setelah submit dan pesan spesifik di setiap field. |
| `forms authorization blade --stack laravel` | Tidak ada hasil | Tidak dianggap rekomendasi yang terverifikasi. |
| `badge --stack laravel` sebagai retry | Anonymous Blade components | Primitive UI konsisten melalui komponen Blade; verifikasi API terhadap versi Laravel proyek. |
| `categorical comparison dashboard --domain chart` | Radar/bullet, kurang sesuai untuk kategori status sederhana | Tidak digunakan sebagai acuan grafik status. |
| `bar category comparison --domain chart` sebagai retry | Bar chart dan tabel data alternatif | Grafik batang berlabel khusus agregasi platform pada panel Admin; tidak digunakan pada Ringkasan Aktivitas pengguna. |

Skill adalah sumber rekomendasi, bukan template yang wajib disalin seluruhnya. Palet biru-hijau dan font Atkinson dari pencarian tidak dipakai mentah: baseline dipilih indigo–teal dan Plus Jakarta Sans mengikuti arah visual yang sudah dibahas. Prinsip aksesibilitas dan kesederhanaan tetap dipertahankan. Pola funnel diterapkan pada formulir, bukan memaksakan landing page penuh CTA. Tidak menambah GSAP, React, atau dashboard template yang mengubah stack.

## 3. Karakter visual

**Arah: platform komunitas yang ramah dengan ruang kerja rekrutmen yang tertib.** Halaman publik memberi ruang pada event dan kegiatan manusia; Ringkasan Aktivitas mengutamakan tindakan pribadi; dashboard analitik hanya tersedia pada panel Admin.

- Light mode menjadi baseline MVP. Jangan menyediakan toggle dark mode sebelum semua token dan halaman untuk tema gelap benar-benar dibuat dan diuji.
- Background abu-abu sangat terang, permukaan putih, indigo untuk aksi utama, teal untuk aksen informasi kecocokan. Jangan memberi makna “diterima” pada warna Match Score.
- Kartu memakai border tipis tanpa bayangan besar. Shadow hanya untuk permukaan mengambang seperti menu dan dialog.
- Hindari glassmorphism, neon, 3D dekoratif, gradien berlebihan, carousel otomatis, dan animasi masuk pada setiap kartu.
- Gunakan satu keluarga ikon SVG outline: Lucide, ukuran 20–24 px dan stroke konsisten. Emoji bukan ikon navigasi.
- Logo awal berupa wordmark “SkillMatch” dan simbol geometris sederhana yang orisinal. Jangan memakai logo organisasi nyata sebagai contoh tanpa izin.
- Foto event berasal dari penyelenggara atau aset berlisensi. Gambar harus relevan dengan kegiatan; jangan menampilkan foto stok sebagai bukti event yang sudah berlangsung.

## 4. Design tokens

### 4.1 Warna semantik

| Token | Nilai | Penggunaan |
|---|---|---|
| `--sm-primary` | `#4F46E5` | Tombol utama, tautan, menu aktif. |
| `--sm-primary-hover` | `#4338CA` | Hover tombol utama. |
| `--sm-primary-soft` | `#EEF2FF` | Latar menu aktif atau sorotan ringan. |
| `--sm-on-primary` | `#FFFFFF` | Teks pada tombol utama. |
| `--sm-accent` | `#0F766E` | Aksen teal yang lebih gelap untuk teks/ikon penting. |
| `--sm-accent-soft` | `#F0FDFA` | Latar panel rincian kecocokan. |
| `--sm-bg` | `#FAFAF8` | Background web utama; panel Admin boleh memakai #F8FAFC. |
| `--sm-surface` | `#FFFFFF` | Kartu, formulir, header. |
| `--sm-text` | `#17212B` | Teks utama. |
| `--sm-muted` | `#52606D` | Teks pendukung yang tetap terbaca. |
| `--sm-divider` | `#E5E7EB` | Pemisah dekoratif; bukan satu-satunya batas input. |
| `--sm-control-border` | `#64748B` | Batas kontrol saat diperlukan untuk identifikasi. |
| `--sm-success` / `--sm-success-bg` | `#166534` / `#F0FDF4` | Berhasil, diterima, hadir. |
| `--sm-warning` / `--sm-warning-bg` | `#92400E` / `#FFFBEB` | Menunggu tindakan/pemeriksaan. |
| `--sm-danger` / `--sm-danger-bg` | `#B91C1C` / `#FEF2F2` | Gagal, ditolak, tindakan destruktif. |
| `--sm-info` / `--sm-info-bg` | `#1D4ED8` / `#EFF6FF` | Informasi proses. |
| `--sm-focus` | `#4F46E5` | Outline fokus 2 px, offset 2 px; kontras diperiksa pada latar setempat. |

Komponen memakai token semantik, bukan hex berbeda pada tiap halaman. Warna aksen yang sebelumnya diusulkan `#0D9488` diperdalam menjadi `#0F766E` untuk baseline teks/ikon agar keterbacaan lebih baik. Teks selalu disandingkan dengan warna status.

### 4.2 Tipografi dan angka

Font utama: **Plus Jakarta Sans**, bobot 400, 500, 600, 700; fallback `ui-sans-serif, system-ui, sans-serif`. Host font secara lokal bila tersedia aset berlisensi; gunakan `font-display: swap`. Jangan menghalangi render sambil menunggu font.

| Peran | Desktop | Mobile | Aturan |
|---|---|---|---|
| Hero | 52–56 px / 1,12 | 34–38 px / 1,18 | Maksimal sekitar 2–3 baris; jangan memaksa line break yang rusak pada mobile. |
| Judul halaman | 30/38 px | 24/32 px | Satu h1 per halaman. |
| Judul bagian | 22/30 px | 20/28 px | Bobot 600–700. |
| Body/form | 16/24 px | 16/24 px | Label tetap terlihat; bukan placeholder saja. |
| Tabel/keterangan | 14/20 px | 14/20 px | Hindari mengecilkan teks untuk memaksa muat. |
| KPI | 32/40 px | 28/36 px | Tabular numerals. |

Angka lokal: `1.250`, `80,00`, `Rp150.000` hanya bila nominal berasal dari konfigurasi. Tanggal: `30 Sep 2026`; waktu: `09.00–12.00 WIB`. Jangan menggunakan tanggal ambigu `09/10/26`. Match Score selalu diberi penyebut `/100`. Bedakan jumlah dengan persentase.

### 4.3 Ukuran, jarak, dan lapisan

Spacing scale: 4, 8, 12, 16, 24, 32, 48, 64 px. Radius: input/button 8 px, kartu 12 px, dialog 16 px, badge pill. Border 1 px. Target klik utama minimal **44 × 44 CSS px** sebagai standar produk web ini.

| Komponen | Ukuran/layout |
|---|---|
| Container publik | Maks. 1200 px, gutter 16/24/32 px. |
| Panel Admin | Maks. 1440 px area konten; sidebar 248 px pada desktop. |
| Aktivitas pengguna | Container web utama maks. 1200 px; tanpa sidebar administratif. |
| Form panjang | Maks. 760 px, satu kolom untuk field utama. |
| Header | Sekitar 64 px; ruang bertambah jika konten membungkus. |
| Card padding | 16 px mobile, 24 px desktop. |
| Dialog | Maks. 560 px umum; dialog besar tidak menggantikan halaman editor. |
| Z-index | Konten 0, sticky 20, dropdown 30, overlay 40, dialog 50, toast 60. |

Animasi: warna/opacity 120–180 ms, drawer 200 ms; tidak mengubah posisi konten saat hover. `prefers-reduced-motion` mematikan translasi dan animasi dekoratif. Jangan menunda respons hanya agar animasi terlihat.

## 5. Struktur navigasi dan URL

URL berikut adalah kontrak navigasi yang diusulkan untuk implementasi, bukan jaminan route sudah tersedia. Gunakan named routes Laravel agar path dapat dipelihara tanpa hardcode.

| Area | Route utama | Navigasi |
|---|---|---|
| Publik | `/`, `/events`, `/events/{event}`, `/packages`, `/login`, `/register` | Cari Event, Cara Kerja, Paket Organizer; Masuk dan Daftar. |
| Volunteer | `/volunteer/aktivitas` | Aktivitas Saya, Cari Event, Lamaran Saya, Riwayat, Notifikasi. |
| Profil Volunteer | `/profil` | Data diri, skill, dan availability; halaman berbeda dari Ringkasan Aktivitas. |
| Organizer | `/organizer/aktivitas` | Aktivitas Saya, Event Saya, Transaksi Saya, Notifikasi. |
| Event workspace | `/organizer/events/{event}` | Ringkasan, Posisi & Persyaratan, Assessment, Pelamar, Kehadiran, Paket. |
| Profil Organizer | `/profil` | Profil organisasi dan pengaturan akun. |
| Admin | `/admin/dashboard` | Dashboard, Event & Moderasi, Pengguna, Master Data, Paket, Transaksi, Konten Publik, Pengaturan, Audit Aktivitas. |
| Profil Admin | `/admin/account` | Akun dan keamanan; terpisah dari pengelolaan platform. |

Login pengguna umum berada di `/login`; login Admin terpisah di `/admin/login`, dengan layout dan konteks autentikasi sendiri sesuai PRD v1.3. Registrasi hanya Volunteer/Organizer; Admin tidak menjadi pilihan registrasi. Tidak ada role switcher di produksi untuk berpindah hak akses. Shell Admin diberi label “Administrasi” agar konteks selalu jelas.

Header panel Admin: breadcrumb, judul halaman, notifikasi, avatar/menu akun. Menu akun memuat Profil, Pengaturan Keamanan, Keluar. Notifikasi dapat halaman atau panel yang bisa diakses keyboard. Item menu yang tidak berwenang tidak dirender; proteksi route/Policy tetap wajib di server.

## 6. Tata letak responsif

| Lebar | Perilaku |
|---|---|
| 360–639 px | Satu kolom, gutter 16 px, sidebar Admin menjadi drawer; web utama menggunakan menu mobile. Filter menjadi panel disclosure. CTA utama full width bila diperlukan. |
| 640–767 px | Dua kartu KPI per baris bila muat; formulir tetap nyaman satu kolom. |
| 768–1023 px | Grid event dua kolom; drawer navigasi tetap digunakan untuk menyisakan ruang konten. |
| ≥1024 px | Sidebar persisten khusus Admin; grid event tiga kolom; panel ringkasan/detail berdampingan. |
| ≥1440 px | Container dibatasi; jangan meregangkan paragraf dan form memenuhi layar. |

Tidak boleh ada scroll horizontal pada seluruh halaman. Tabel kompleks boleh memiliki region scroll berlabel, tetapi aksi utama tidak disembunyikan di ujung kanan. Pada mobile, daftar pelamar menggunakan kartu ringkas dengan nama, posisi, status, skor, dan tombol Detail. Form tanggal, kuota, dan pembayaran harus tetap dapat digunakan pada ponsel.

Uji 360, 375, 768, 1024, dan 1440 px, zoom teks 200%, serta reflow 320 CSS px. Sticky footer/header tidak menutupi kontrol yang sedang fokus. Navigasi back mempertahankan filter dan halaman pagination melalui query string.

## 7. Halaman publik

### 7.1 Landing page

Urutan lengkap mengikuti bagian 19.3: navbar → hero → pencarian cepat → event terbuka → cara kerja → kelompok fitur → manfaat Organizer → ringkasan paket → FAQ/footer.

Hero memakai judul **“Temukan peranmu. Beri dampak nyata.”** Teks pendukung menjelaskan pencarian berdasarkan skill, jadwal, dan lokasi tanpa mengklaim AI. CTA utama “Cari Event”; “Kelola Rekrutmen Event” sebagai tautan/aksi sekunder.

Desktop memakai area teks 7 kolom dan visual event/komunitas 5 kolom. Mobile menampilkan teks dan pencarian terlebih dahulu. Visual tidak boleh menyerupai data real-time palsu. Jika gambar tidak tersedia, gunakan ilustrasi geometris ringan, bukan broken image atau angka dekoratif.

Pencarian: kata kunci, kota, kategori, tombol “Cari”. Event terbuka maksimal enam kartu awal dan tautan “Lihat Semua Event”; tidak memakai infinite scroll. Cara kerja cukup empat langkah: lengkapi profil, pilih posisi, ikuti seleksi, berpartisipasi. Footer memuat bantuan, privasi, dan ketentuan.

### 7.2 Katalog event

Judul “Temukan Event”, jumlah hasil, search, filter kategori/kota/tanggal, pengurutan, dan pagination. Desktop filter di bar atas; mobile tombol “Filter” membuka panel dengan tombol Terapkan dan Hapus Filter. Filter aktif tampil sebagai chip yang dapat dihapus; koleksi chip boleh membungkus baris.

Kartu: poster rasio 16:9, judul maksimal dua baris dengan judul lengkap pada detail, Organizer, kota, tanggal event, tenggat, status “Pendaftaran Dibuka”, tombol “Lihat Detail”. Badge kecocokan hanya ditampilkan jika skor memiliki data dan konteks posisi yang jelas; baseline tidak menampilkan Match Score pada kartu event sebelum melamar karena skor PRD berada pada posisi/lamaran.

### 7.3 Detail event dan posisi

Desktop: informasi utama 8 kolom, panel jadwal/status/CTA 4 kolom. Susunan konten: nama event, Organizer, kategori, lokasi dan tanggal, deskripsi, daftar posisi, persyaratan, jadwal tugas, informasi dokumen. Tombol “Lamar Posisi” berada pada posisi yang dipilih.

Perlihatkan syarat wajib dan preferensi secara terpisah. Kuota peserta diterima berbeda dari batas jumlah pelamar paket. Event penuh/ditutup menampilkan alasan dan tombol kembali ke katalog, bukan CTA aktif yang berakhir error. Login dari CTA mempertahankan tujuan event; setelah login validasi ulang hak akses dan ketersediaan.

## 8. Aktivitas pengguna dan dashboard analitik Admin

### 8.1 Ringkasan Aktivitas Volunteer

Gunakan navbar web utama, container 1200 px, sapaan singkat, lalu daftar pekerjaan. Tidak memakai sidebar Admin, baris KPI besar, grafik status, atau filter analitik periode.

Urutan: tindakan mendesak (assessment/dokumen) → lamaran terakhir → kegiatan terdekat → notifikasi. Desktop memakai kolom utama sekitar 8/12 dan kolom pendamping 4/12; mobile satu kolom, dengan tindakan mendesak pertama. Baris lamaran memuat nama event, posisi, status, tanggal, dan tautan detail. Jumlah kecil dapat ditampilkan sebagai badge pada judul daftar.

Pengguna baru mendapat satu blok onboarding dengan ilustrasi sederhana, penjelasan singkat, dan CTA Cari Event/Lengkapi Profil. Jangan menampilkan empat kartu nol diikuti tiga panel kosong yang besar. Bagian yang belum berisi data tetap dapat ditemukan lewat navigasi; cukup tampilkan empty state ringkas pada halaman terkait.

### 8.2 Ringkasan Aktivitas Organizer

Gunakan layout web utama. Judul Ringkasan Aktivitas dengan CTA Buat Event. Tampilkan antrean tindakan ringkas (revisi, pembayaran, kandidat siap ditinjau), kemudian daftar event milik sendiri. Setiap baris/kartu event memuat identitas event, status, jadwal, jumlah kandidat perlu ditinjau, dan tombol Kelola Event.

Posisi, Assessment, Pelamar, Kehadiran, serta Paket berada pada navigasi lokal halaman pengelolaan event. Gunakan tabs/link berlabel jelas; fitur-fitur itu tidak disembunyikan semuanya dalam menu tiga titik. Rekap kuota dan attendance boleh berada pada detail event sebagai informasi operasional. Tidak ada grafik pertumbuhan, dashboard analitik Organizer, atau data lintas penyelenggara.

### 8.3 Dashboard analitik Admin

Panel `/admin/*` memiliki sidebar dan header terpisah, tanpa navbar publik/footer pemasaran. `/admin/login` juga memakai layout sendiri. Tautan Lihat Website menjadi jalan keluar yang jelas ke web utama; profil Admin ada di `/admin/account`.

Komposisi dashboard: judul + filter periode → maksimal empat kartu indikator utama → dua grafik utama → antrean tindakan. Indikator tambahan berada pada bagian lanjutan/modul terkait, bukan delapan kartu yang berebut perhatian. Data berasal dari backend dengan definisi persis PRD bagian 11.5.

- Kartu utama: total akun saat ini, event aktif saat ini, lamaran terkirim dalam periode, nilai transaksi sandbox terverifikasi dalam periode. Beri label cakupan waktu pada tiap kartu.
- Grafik awal: tren lamaran dan transaksi terverifikasi. Tren akun/event serta komposisi paket dapat dibuka melalui bagian analitik lanjutan di panel yang sama.
- Antrean: event menunggu verifikasi, pemeriksaan dokumen gagal, dan transaksi perlu penanganan; masing-masing menaut ke modul tindakan.
- Filter periode 7/30 hari atau rentang tanggal. Kartu keadaan saat ini tidak berpura-pura mengikuti filter historis.
- Pembayaran dihitung berdasarkan paid_at dan aktivasi berdasarkan activated_at; pengulangan callback tidak menambah jumlah atau mengganti timestamp historis.

Grafik menggunakan batang untuk kategori dan garis/batang untuk waktu. Hindari radar, gauge besar, grafik 3D, dan donut penuh status. Sertakan satuan, angka, ringkasan kalimat, serta tabel alternatif. Data kosong dan kegagalan memuat harus dibedakan. Akun non-Admin tidak menerima data agregasi melalui HTML maupun endpoint.

## 9. Volunteer: profil, lamaran, dan assessment

### 9.1 Profil dan availability

Profil menampilkan kelengkapan berdasarkan field wajib yang nyata. Tautan “Lengkapi Profil” mengarah ke field yang belum lengkap. Skill memakai pilihan master yang dapat dicari, bukan tag bebas yang memecah pencocokan. Availability menggunakan daftar interval tanggal/jam dengan tombol Tambah Jadwal; kalender hanya tambahan visual, bukan satu-satunya cara input.

CV untuk lamaran dikelola pada lamaran terkait, bukan menjadi berkas publik di profil. Tampilkan pemberitahuan singkat bahwa dokumen lamaran terkirim dapat diakses perekrut event tersebut.

### 9.2 Form lamaran

Stepper empat tahap: **Posisi → Profil → Dokumen → Tinjau & Kirim**. Indikator menyebut “Langkah 2 dari 4”. Tombol Kembali mempertahankan input. Jangan meminta ulang data yang sudah tersimpan; tampilkan ringkasan dengan tautan Ubah.

Draft tersimpan setelah tindakan simpan yang berhasil; tampilkan “Draft tersimpan pukul …” hanya setelah konfirmasi server. File tidak dapat dipulihkan browser setelah reload kecuali unggah sebelumnya berhasil; jelaskan keadaan ini dengan jujur. Pada tahap final tampilkan posisi, jadwal, dokumen, dan informasi bahwa perubahan tertentu dikunci setelah pengiriman. Konfirmasi tidak menambahkan persetujuan marketing terselubung.

### 9.3 Unggah dokumen

Area dropzone harus memiliki tombol “Pilih File” dan input file berlabel. Ketentuan terlihat sebelum unggah: CV PDF; dokumen pendukung PDF/JPG/PNG sesuai posisi; maksimal 5 MB/file dan 5 file/lamaran. Tampilkan daftar dokumen wajib, opsional, nama file, ukuran, progress nyata bila tersedia, status, dan aksi yang diizinkan.

| State | Teks antarmuka | Aksi |
|---|---|---|
| Belum diunggah | “Unggah CV untuk posisi ini.” | Pilih File. |
| Uploading | “Mengunggah CV…” dan progress jika tersedia | Batalkan bila backend mendukung pembatalan; jangan memberi tombol palsu. |
| quarantined | “Sedang diperiksa. Dokumen belum dapat diunduh.” | Perbarui status; tidak ada Unduh. |
| ready | “Siap digunakan” | Unduh; Ganti/Hapus hanya jika PRD mengizinkan. |
| rejected | “Dokumen tidak lolos pemeriksaan. Unggah file lain.” | Unggah Ulang jika sebelum tenggat. |
| scan_failed | “Pemeriksaan belum berhasil. Dokumen tetap tersimpan.” | Coba Periksa Lagi jika endpoint tersedia; selain itu instruksi hubungi bantuan. |
| Upload gagal | “Unggahan gagal. Periksa koneksi dan coba lagi.” | Coba Lagi; input lain tetap dipertahankan. |

Jangan menyebut ready sebagai jaminan “100% aman”. Jangan menampilkan path storage, nama server, atau stack trace. Pengiriman lamaran dengan scan pending mengikuti PRD: entri dapat terkirim sebagai documents_pending, screening menunggu dokumen wajib ready. Jangan menyamakan dokumen pending dengan dokumen hilang.

### 9.4 Lamaran Saya dan jadwal

Daftar lamaran: event, posisi, waktu kirim, status, tindakan berikutnya. Filter status dan pagination; status dapat dibuka ke detail timeline. Timeline menampilkan aktivitas yang benar-benar terjadi. Jadwal hanya memuat event yang diterima, dengan keterangan lokasi/jam dan tautan detail. Penarikan lamaran memakai dialog sesuai tahap yang diizinkan PRD.

### 9.5 Assessment

Halaman persiapan memuat jumlah soal, durasi, aturan satu percobaan, dan tombol “Mulai Assessment”. Setelah mulai: judul posisi, timer berbasis waktu server, nomor soal, jawaban tersimpan, navigasi soal, dan tombol “Tinjau Jawaban”. Desktop panel nomor soal di samping; mobile berupa disclosure. Radio button memakai label klik yang luas.

Peringatan sisa waktu disampaikan pada ambang penting, bukan dibacakan screen reader setiap detik. Jangan mengunci clipboard/browser secara agresif atau menambahkan proctoring. Jika timer habis, server menentukan akhir percobaan; UI menunjukkan “Waktu selesai. Jawaban sedang dikirim.” Saat koneksi gagal, tunjukkan jawaban mana yang belum tersimpan; jangan mengklaim tersimpan otomatis tanpa respons server. Setelah selesai, tampilkan nilai yang diizinkan dan status menunggu peninjauan; jangan membocorkan kunci soal.

## 10. Organizer: workspace event dan seleksi

Event Saya menampilkan daftar event dengan tiga informasi terpisah: moderasi, pembayaran, dan pelaksanaan. Jangan menggabungkan “Disetujui” dengan “Sudah Dibayar”. Editor event memakai langkah: Informasi → Posisi & Jadwal → Persyaratan → Assessment → Paket → Tinjau. Simpan Draft selalu tersedia bila form dapat disimpan. Sebelum publikasi, tampilkan checklist kelengkapan yang terkait data nyata.

Setelah ada lamaran, field yang dibekukan PRD menjadi read-only dengan penjelasan, bukan hilang. Tombol edit tidak boleh berpura-pura aktif lalu kehilangan perubahan saat submit.

### 10.1 Pelamar

Tab: Semua Pelamar, Menunggu Dokumen/Assessment, Siap Ditinjau, Diterima, Ditolak. Default Siap Ditinjau saat ada kandidat yang perlu diproses. Tabel memuat nama, posisi, Match Score, nilai assessment, status, tanggal kirim, Detail. Filter posisi/status dan sort tersedia; pagination server-side, misalnya 20 baris/halaman.

Match Score adalah angka 0–100 disertai label “Kecocokan”, bukan “Kualitas kandidat”. Detail skor memuat S, A, L, bobot 50/30/20, dan kontribusinya. Nilai assessment dipisahkan; jangan menambahkan bobot sendiri atau label ambang tinggi/rendah yang belum disepakati.

### 10.2 Detail pelamar

Header: nama, posisi, status, Match Score, nilai assessment. Tab: Profil, Dokumen, Assessment, Riwayat Status. Panel tindakan: Terima Pelamar dan Tolak dengan gaya yang berbeda. Aksi penerimaan hanya untuk kandidat eligible sesuai server. Dialog penerimaan menyebut nama, posisi, dan sisa kuota; penolakan memerlukan alasan singkat. Jika kuota habis atau jadwal bentrok saat submit, tutup loading, tampilkan alasan, dan perbarui data; jangan meninggalkan badge accepted palsu.

Dokumen hanya menampilkan metadata untuk perekrut terkait, tombol Unduh untuk ready, tanpa inline PDF viewer pada MVP. Permintaan unduh tetap diperiksa server. Akun tidak berwenang mendapat tampilan akses ditolak/tidak ditemukan yang tidak membocorkan identitas pemilik lain.

### 10.3 Kehadiran dan penyelesaian

Kehadiran menampilkan hanya peserta accepted; pilihan Belum Dicatat, Hadir, Tidak Hadir. Tindakan “Simpan Kehadiran” memberi hasil per baris bila sebagian gagal. Jangan menandai semuanya hadir secara default. Tombol “Selesaikan Event” tersedia setelah syarat waktu dan kelengkapan attendance terpenuhi, dengan konfirmasi yang menyebut konsekuensi pembentukan riwayat.

## 11. Panel Admin

Admin memiliki layout dashboard sendiri pada `/admin/*`, dengan menu profil terpisah. Overview tidak menjadi satu halaman panjang berisi semua tabel. Setiap modul mempunyai halaman daftar/detail sendiri.

- Verifikasi Event: tab Menunggu, Perlu Revisi, Disetujui. Detail menampilkan Organizer, event, persyaratan, dan ringkasan paket. Aksi Setujui/ Minta Revisi disertai konfirmasi; revisi membutuhkan alasan.
- Pengguna: filter role/status, detail identitas minimum, aksi penangguhan disertai alasan. Tidak ada tombol ganti password menjadi nilai tetap.
- Master Data: kategori, skill, kota; bedakan Nonaktifkan dari Hapus. Data terpakai mengikuti aturan PRD.
- Paket: editor nama, harga, limit, status aktif; validasi server dan ringkasan sebelum simpan. Perubahan paket tidak mengubah snapshot order lama.
- Transaksi: order, event, Organizer, paket, nominal, status, waktu. “Periksa Status” berarti rekonsiliasi server, bukan tombol bebas “Tandai Lunas”.
- Audit: filter pelaku/tindakan/tanggal; tampilkan metadata minimum, tanpa token, password, atau isi CV.

Admin tidak mendapat tombol baca semua CV secara default. Jika belum ada alur permission khusus yang diaudit, tampilkan metadata saja. Keamanan akun Admin mengikuti PRD; MFA pada deployment publik bukan opsi kosmetik.

## 12. Paket, checkout, dan status pembayaran

### 12.1 Perbandingan paket

Kartu paket tiga kolom pada desktop, satu kolom pada mobile. Setiap kartu menampilkan nama, harga dari konfigurasi, label **“per event”**, batas posisi, batas lamaran, durasi pendaftaran, fitur yang tersedia, dan tombol Pilih Paket. Jangan menyebut per bulan. Paket terpilih memiliki border indigo dan indikator teks “Dipilih”, bukan hanya warna.

Fixture usulan (khusus prototipe, belum aturan produksi):

| Batas | Free | Standard | Premium |
|---|---:|---:|---:|
| Posisi | 2 | 5 | 10 |
| Lamaran terkirim | 30 | 100 | 300 |
| Durasi pendaftaran | 7 hari | 30 hari | 60 hari |
| Harga | Gratis | Belum ditetapkan | Belum ditetapkan |

Fitur inti dan keamanan sama pada semua paket. Tampilkan badge “Contoh paket” pada layar demo yang memakai fixture ini. Checkout berbayar tidak aktif bila konfigurasi harga belum lengkap. Jangan mengarang fitur ekspor, prioritas dukungan, AI, sertifikat, atau promosi event sebagai pembeda paket.

### 12.2 Alur dan representasi status

Urutan: pilih paket → ajukan event → disetujui Admin → bayar jika berbayar → server verifikasi → hak paket aktif/publikasi memenuhi syarat. UI memisahkan “Event disetujui”, “Pembayaran terverifikasi”, dan “Pendaftaran dibuka” karena tanggal pendaftaran dapat berada di masa depan.

| State | Tampilan | CTA |
|---|---|---|
| Menunggu verifikasi | “Event sedang ditinjau Admin.” | Lihat Pengajuan; pembayaran belum tersedia. |
| Perlu revisi | Catatan revisi yang spesifik | Perbaiki Event. |
| Approved + Free | “Event disetujui. Paket Free aktif.” | Lihat Event; status pendaftaran mengikuti jadwal. |
| Approved + unpaid | Ringkasan event, paket, harga, batas | Bayar Paket. |
| pending | “Menunggu konfirmasi pembayaran.” | Lanjutkan Pembayaran bila sesi masih valid; Periksa Status. |
| paid | “Pembayaran terverifikasi.” | Lihat Event; jelaskan jika belum dipublikasikan karena status event. |
| failed/expired | “Pembayaran belum berhasil/kedaluwarsa.” | Coba Bayar Lagi dengan order baru. |
| Event cancelled/suspended | “Event belum dapat dipublikasikan.” | Lihat Alasan/Hubungi Bantuan; jangan menjanjikan refund otomatis. |

Tombol bayar tidak mengubah status menjadi paid secara optimistis. Callback browser tidak menjadi sumber kebenaran. Polling hanya terbatas pada halaman status pembayaran, berhenti pada status akhir atau timeout dan menyediakan Periksa Status; jangan menggunakan spinner tanpa akhir.

Banner “Mode simulasi pembayaran” wajib pada lingkungan sandbox. Di ringkasan event, tampilkan penggunaan paket seperti “18 dari 30 lamaran”, dengan batas dan penjelasan yang berasal dari konfigurasi. Jangan menyediakan tombol upgrade jika aturan upgrade belum diimplementasikan; ketika batas tercapai tampilkan pendaftaran ditutup, sementara akses seleksi tetap tersedia.

## 13. Komponen bersama dan interaksi

Primitive yang perlu tersedia: Button, IconButton, Link, Card, Badge, Alert, Field, Select, Checkbox, RadioGroup, FileUpload, Stepper, Tabs, Table, Pagination, Dialog, Drawer, Toast, EmptyState, Skeleton, StatCard, ChartPanel, ScoreBreakdown, StatusTimeline.

| Komponen | State/perilaku wajib |
|---|---|
| Button | Default, hover, focus-visible, pressed, disabled, loading. Loading menjaga lebar dan memberi label proses; server tetap menangani idempotensi. |
| Form field | Label, petunjuk, nilai, required yang jelas, error inline melalui aria-describedby; border merah bukan satu-satunya indikator. |
| Error summary | Fokus ke ringkasan setelah submit gagal; tiap item menaut ke field bermasalah; tidak mengambil fokus setiap blur. |
| Dialog/drawer | Judul, focus trap, Escape/batal, pengembalian fokus ke pemicu, latar inert. Proses yang belum aman dibatalkan diberi penjelasan. |
| Table | Header semantik, sort berlabel dan aria-sort, empty/error state, pagination; tidak seluruh baris menjadi tombol tanpa alternatif link. |
| Badge | Teks lengkap, ikon opsional; label penting boleh membungkus. Badge bukan tombol kecuali memang filter interaktif. |
| Toast | Untuk konfirmasi ringan; error penting tetap di halaman. Pengumuman status melalui live region yang tidak berulang. |
| Tabs | Keyboard/aria sesuai perilaku; jika berpindah halaman gunakan link navigasi biasa, jangan role tab palsu. |
| Skeleton | Bentuk menyerupai konten; aria-busy dan label memuat; hindari shimmer pada reduced-motion. |

Gunakan copy singkat: “Simpan Perubahan”, “Kirim Lamaran”, “Tinjau Pelamar”. Hindari “Submit”, “Something went wrong”, dan “Error 500” sebagai satu-satunya penjelasan. Jangan membeberkan detail infrastruktur kepada pengguna.

## 14. Keadaan kosong, gagal, dan akses terbatas

| Keadaan | Pesan dan pemulihan |
|---|---|
| Volunteer baru | “Belum ada lamaran.” → Cari Event. |
| Organizer baru | “Belum ada event.” → Buat Event. |
| Filter tanpa hasil | “Tidak ada hasil sesuai filter.” → Hapus Filter. |
| Grafik kosong | “Belum ada data pada periode ini.” → Ubah Periode. |
| Gagal memuat | “Data belum dapat dimuat.” → Coba Lagi; jangan mengganti data dengan nol. |
| Sesi habis | “Sesi Anda berakhir. Masuk kembali untuk melanjutkan.” Jangan menyimpan dokumen/token di localStorage. |
| Akses ditolak | Halaman 403/404 sesuai kebijakan server; tombol Kembali ke Aktivitas Saya atau Dashboard Admin sesuai area; tidak bocorkan nama file/pemilik. |
| Halaman tidak ditemukan | Penjelasan singkat dan tautan Kembali ke Beranda atau area yang diizinkan. |
| Jaringan terputus | Banner koneksi; jangan menampilkan konfirmasi simpan palsu. Saat pulih periksa status sebelum mengulang aksi penting. |

## 15. Aksesibilitas, privasi, dan kinerja

Target desain: praktik WCAG 2.2 AA yang relevan, tanpa menyatakan kepatuhan sebelum pengujian. Normal text minimal 4,5:1; teks besar/non-text penting minimal 3:1. Nilai token diperiksa pada pasangan penggunaan nyata, termasuk hover/focus. Kontrol utama 44 CSS px, keyboard lengkap, skip link, heading terurut, alt relevan, dan icon button bernama aksesibel. Jangan mematikan zoom atau paste password.

Grafik memiliki tabel data; status tidak hanya warna; timer tidak terus menginterupsi pembaca layar. Animasi menyesuaikan reduced-motion. Poster dekoratif di dekat teks yang sudah menjelaskan event dapat memakai alt kosong; poster yang membawa informasi tambahan perlu alternatif teks.

Jangan mengirim data pelamar lain lalu menyembunyikannya dengan CSS. Blade menerima data yang sudah diotorisasi. File privat tidak ditampilkan lewat URL publik. Form mengizinkan password manager. Hindari pelacak pihak ketiga yang merekam isi formulir dan CV.

Optimasi gambar dengan dimensi/aspect ratio yang disediakan, lazy-load hanya gambar nonkritis, dan poster responsive. Jangan lazy-load gambar utama di atas lipatan. Daftar memakai pagination server-side, bukan mengambil ribuan kandidat. Muat library grafik hanya pada halaman analitik. Tidak diperlukan GSAP/animasi besar. Target pengukuran UI awal: LCP ≤2,5 detik, INP ≤200 ms, CLS ≤0,1 pada kondisi uji yang dicatat; ini target, bukan hasil pengukuran aplikasi saat ini.

## 16. Panduan implementasi untuk AI agent

1. Baca PRD dan design.md sebelum menulis halaman. Gunakan aturan bisnis server sebagai sumber kebenaran.
2. Implementasikan token, komponen, lalu layout web utama, aktivitas Volunteer/Organizer, dan panel Admin yang terpisah. Gunakan anonymous Blade components untuk primitive yang berulang.
3. Struktur yang disarankan: `resources/views/layouts/`, `resources/views/components/`, `resources/views/public/`, `resources/views/volunteer/`, `resources/views/organizer/`, `resources/views/admin/`. Token pada stylesheet utama; script interaksi melalui pipeline proyek.
4. Gunakan Alpine untuk drawer, dialog, disclosure, dan interaksi ringan. Filter katalog dapat GET form/query string; form bisnis tetap memiliki validasi serta otorisasi Laravel. Jangan memilih React/Next.js hanya untuk dashboard.
5. Grafik boleh Chart.js jika disepakati saat implementasi; tabel alternatif tetap tersedia. Jangan memasukkan library sebelum memeriksa dependensi proyek. Konfigurasi Tailwind/Blade mengikuti versi yang benar-benar dipasang, bukan diasumsikan dari skill.
6. Implementasikan satu alur lengkap: katalog → lamaran → dokumen → assessment → tinjauan Organizer. Setelah itu hubungkan paket, pembayaran, attendance, Ringkasan Aktivitas, dan dashboard Admin.
7. Gunakan fixture bertanda demo pada prototipe. Ganti dengan backend nyata sebelum menyatakan fitur berfungsi; tidak ada tombol pura-pura, timer palsu, pembayaran sukses palsu, atau grafik acak.
8. Ketika backend belum tersedia, tampilkan state yang jujur dan catat integrasi tertunda pada dokumentasi developer, bukan menaruh istilah teknis pada alur pengguna.
9. Pertahankan keamanan dan aturan bisnis saat membuat mobile layout. Menyembunyikan tombol tidak menggantikan Policy.
10. Catat penyimpangan visual material atau konflik kebutuhan sebelum memperluas scope. Penyesuaian kecil responsif tidak membutuhkan perubahan konsep produk.

## 17. Checklist penerimaan desain

Checklist ini harus diuji pada aplikasi hasil implementasi; belum dinyatakan lulus hanya karena design.md selesai.

- [ ] Dashboard analitik hanya tersedia bagi Admin; Volunteer/Organizer memiliki Ringkasan Aktivitas pada web utama.
- [ ] Login, layout, sidebar, dan menu akun Admin terpisah sesuai PRD v1.3.
- [ ] Header, sidebar, typography, spacing, dan komponen konsisten.
- [ ] Katalog/detail event nyaman pada 360 px dan desktop; tidak ada body overflow.
- [ ] Form lamaran mempertahankan input dan menampilkan error summary serta error inline.
- [ ] File pending/rejected/scan_failed tidak memberi tombol unduh aktif.
- [ ] Organizer lain tidak menerima metadata privat atau isi dokumen melalui request.
- [ ] Match Score dan assessment terpisah, dengan rincian bobot yang benar.
- [ ] Semua grafik memiliki definisi periode, unit, empty state, dan tabel alternatif.
- [ ] Status moderasi, pembayaran, publikasi, dan pendaftaran dapat dibedakan.
- [ ] Paket/harga berasal dari konfigurasi; fixture diberi label contoh; sandbox diberi label simulasi.
- [ ] Keyboard, focus management, screen reader dasar, kontras, dan reduced-motion diperiksa.
- [ ] Login, halaman error, jaringan terputus, dan sesi habis memiliki alur pemulihan.
- [ ] Review visual mencakup 360/375/768/1024/1440 px serta zoom/reflow.
- [ ] Tidak ada statistik, testimoni, klaim keamanan absolut, atau fitur berbayar yang dibuat-buat.
- [ ] Semua tindakan kritis diuji dengan backend, termasuk double submit dan kegagalan proses.

## 18. Keputusan terbuka dan batas dokumen

Belum final: harga/manfaat paket produksi, kategori event rinci, ambang assessment, retensi dokumen, dan aturan upgrade/refund. Ikuti baseline PRD dan penanda usulan; jangan menyelesaikannya diam-diam dalam desain. Dark mode, inline PDF preview, chat, sertifikat, bulk acceptance, dan ekspor lanjutan tidak ditambahkan ke MVP melalui dokumen ini.

Sumber desain adalah PRD versi 1.3, pembahasan SkillMatch pada percakapan ini, serta hasil skill UI/UX Pro Max dan quick-reference web yang telah ditinjau. Dokumen ini sengaja berdiri sendiri sebagai acuan visual; tidak perlu membaca seluruh ZIP untuk menerapkannya.


## 19. Arahan visual revisi: elegan, minimalis, dan kaya informasi

Bagian ini memperjelas standar kualitas visual versi 1.1. Aturan ini berlaku bersama spesifikasi komponen dan alur sebelumnya. Implementasi starter terdahulu belum memenuhi standar ini; revisi dokumen tidak otomatis mengubah kode atau screenshot.

### 19.1 Diagnosis implementasi terdahulu

| Temuan pada screenshot/kode starter | Perbaikan yang diwajibkan |
|---|---|
| Hampir seluruh konten berada dalam kartu putih berbatas sama | Variasikan komposisi menurut fungsi: section terbuka untuk narasi, kartu bergambar untuk event, daftar untuk aktivitas, tabel untuk administrasi. |
| Hero berupa dua kartu teks miring; kartu event memakai bidang ungu kosong | Gunakan visual komunitas yang relevan atau ilustrasi yang dirancang; sediakan fallback bermakna. Jangan menjadikan bidang kosong sebagai visual utama. |
| Font implementasi hanya system-ui; font utama dokumen tidak dipasang | Sediakan font Plus Jakarta Sans lokal dengan lisensi, bobot yang diperlukan, dan periksa hasil render; fallback hanya untuk kondisi font gagal. |
| Banyak judul dan teks sama-sama tebal, uppercase, dan berwarna | Tetapkan hierarki; gunakan uppercase hanya untuk label pendek yang benar-benar perlu. Body normal dan metadata lebih tenang. |
| Pengguna baru melihat kartu nol dan beberapa panel kosong besar | Gunakan satu onboarding utama lalu daftar yang relevan; hindari ruang kosong yang menyerupai modul belum selesai. |
| Manfaat Organizer, pencarian cepat, dan cakupan fitur tidak tersaji utuh di landing | Gunakan susunan bagian 19.3 dan pemetaan fitur 19.4. |
| Sidebar serta dashboard diterapkan juga kepada pengguna umum | Terapkan PRD v1.3: web utama dan aktivitas pengguna, panel Admin sendiri. |
| Pemeriksaan fungsi dianggap cukup untuk kualitas desain | Lakukan review visual tersendiri pada halaman representatif serta keadaan berisi/kosong. |

### 19.2 Bahasa visual yang harus terlihat

**Elegan:** susunan rapi, tipografi terkendali, warna hemat, dan gambar yang dipilih dengan sengaja. **Minimalis:** sedikit elemen bersaing pada satu saat, dengan informasi lengkap tersusun sesuai prioritas. **Fitur terlihat:** setiap kemampuan utama memiliki label, titik masuk, dan lokasi yang konsisten.

- Gunakan latar utama off-white `#FAFAF8` untuk area publik; permukaan putih, teks gelap `#17212B`, metadata `#52606D`, pemisah `#E5E7EB`. Token primer indigo dan aksen teal tetap digunakan. Warna semantik status tidak berubah.
- Dominasi warna netral; indigo terutama untuk CTA utama/menu aktif. Teal hanya untuk informasi kecocokan tertentu. Jangan mewarnai semua tautan navigasi dan semua badge dengan ungu sekaligus.
- Header publik 72 px, wordmark tenang, link navigasi berwarna teks, satu tombol utama. Header tidak harus diberi bayangan.
- Hero desktop 52–56 px/1,12, bobot 600–700, lebar teks sekitar 12–16 karakter per baris sesuai copy; mobile 34–38 px/1,18. Maksimal tiga baris tanpa pemenggalan manual yang merusak responsivitas. H1 halaman biasa 28–32 px. Body 16 px/1,6. Metadata 13–14 px.
- Gunakan hanya 3–4 tingkat tipografi yang tampak dalam satu bagian. Hindari menebalkan seluruh paragraf, judul event, nama penyelenggara, dan metadata bersamaan.
- Jarak antarbagian publik 72–96 px desktop, 40–56 px mobile; jarak dalam kelompok 8–24 px. Jarak besar harus memisahkan kelompok bermakna, bukan memperbesar kotak kosong.
- Radius kartu event 14–16 px, kontrol 8–10 px. Border lembut; shadow tipis hanya jika membantu kedalaman. Kartu tidak diputar, tidak membesar saat hover, dan tidak memakai gradien sebagai pengisi ruang.
- Tombol utama solid, sekunder outline ringan, aksi tambahan text link. Satu tujuan utama per kelompok keputusan; daftar event tetap boleh memiliki satu tautan detail pada setiap item.
- Ikon outline konsisten mendukung label; hindari simbol dekoratif yang tidak membantu pemahaman.

### 19.3 Komposisi halaman publik yang konkret

Urutan landing page:

1. **Navbar:** logo, Cari Event, Cara Kerja, Untuk Organizer, Paket; Masuk dan Daftar. Mobile memakai menu yang tetap menampilkan seluruh tujuan tersebut.
2. **Hero:** teks kiri sekitar 55%, visual kanan 45%. Judul usulan: “Temukan peranmu. Beri dampak nyata.” Subjudul menjelaskan skill, jadwal, dan lokasi. CTA Cari Event; tautan sekunder Untuk Organizer. Visual menggunakan foto kegiatan volunteer yang berlisensi atau ilustrasi orisinal yang relevan, tanpa kartu KPI palsu atau testimoni rekaan.
3. **Pencarian cepat:** kata kunci, kota, kategori; satu baris desktop dan satu kolom mobile. Ditempatkan langsung sesudah teks hero/di bawah hero agar menjadi alat yang nyata, bukan hiasan.
4. **Event terbuka:** judul, penjelasan satu kalimat, Lihat Semua Event, lalu 3–6 kartu. Poster 16:9, kategori kecil, judul, penyelenggara, tanggal/kota, tenggat, dan aksi detail. Poster menjadi fokus visual; metadata tidak ditumpuk menjadi banyak badge.
5. **Cara kerja:** empat langkah dalam section terbuka dengan nomor kecil dan garis penghubung sederhana; tidak harus empat kotak besar. Profil → pilih posisi → seleksi → berpartisipasi.
6. **Fitur yang membantu:** tiga kelompok manfaat: kecocokan skill/jadwal/lokasi; dokumen dan assessment; status seleksi serta riwayat. Tampilkan contoh potongan UI berlabel ilustrasi bila belum berasal dari data pengguna.
7. **Untuk Organizer:** satu section dua kolom, visual daftar kandidat dan penjelasan alur event–pelamar–seleksi–attendance. CTA Buat Event dan tautan Lihat Paket. Tidak menampilkan dokumen/identitas pelamar nyata sebagai materi promosi.
8. **Paket:** ringkasan batas utama per event dan tautan perbandingan lengkap; harga hanya dari konfigurasi. Jangan mengulang seluruh isi halaman paket di landing.
9. **FAQ dan footer:** pertanyaan singkat tentang pendaftaran, seleksi, dokumen, dan pembayaran; tautan bantuan, privasi, ketentuan. Klaim privasi harus sesuai implementasi.

Pada desktop 1440 px, area awal harus memperlihatkan identitas, manfaat, CTA, visual, dan akses pencarian tanpa hero menghabiskan tinggi berlebihan. Pada ponsel, CTA dan pencarian tidak boleh terdorong jauh oleh gambar dekoratif. Ukuran akhir ditinjau pada screenshot, bukan dipenuhi dengan fixed height yang memotong isi.

Tidak semua fitur harus berteriak di hero. Landing mengenalkan manfaat; navigasi dan halaman sesuai konteks menyediakan fungsi lengkap. Panel pengelolaan Admin tidak menjadi fitur promosi publik.

### 19.4 Peta keterlihatan fitur

| Fitur | Titik penemuan | Lokasi tindakan utama |
|---|---|---|
| Cari dan filter event | Navbar, hero, pencarian cepat | Katalog event |
| Skill, availability, lokasi | Cara kerja, pengingat kelengkapan | Profil Volunteer |
| Posisi dan persyaratan | Kartu/detail event | Detail posisi |
| Lamaran dan CV | Detail posisi, Aktivitas Saya | Form/detail lamaran |
| Assessment | Notifikasi dan tindakan mendesak | Halaman assessment |
| Match Score dan screening | Penjelasan cara kerja; detail seleksi | Detail lamaran/kandidat sesuai izin |
| Status dan riwayat | Menu Lamaran Saya/Riwayat | Daftar dan detail aktivitas |
| Buat event dan kelola posisi | Untuk Organizer, Event Saya | Editor/pengelolaan event |
| Tinjau kandidat dan seleksi | Antrean aktivitas, tab Pelamar | Daftar/detail kandidat |
| Attendance | Tugas menjelang/setelah event, tab Kehadiran | Kehadiran event |
| Paket dan pembayaran | Navbar Paket, tab Paket event | Pilih paket, checkout, Transaksi Saya |
| Analitik dan kendali platform | Sidebar khusus Admin | Panel Admin |

Fitur penting tidak boleh hanya dapat ditemukan melalui hover, ikon tanpa label, atau menu overflow. Fitur yang bersyarat ditampilkan pada tahap yang relevan beserta alasan bila belum tersedia; tidak membuat CTA aktif yang pasti gagal.

### 19.5 Penggunaan gambar dan data contoh

Poster asli penyelenggara digunakan jika tersedia. Untuk prototipe, gunakan aset yang sah dan tandai event sebagai contoh. Jika belum ada poster, gunakan fallback kategori dengan ikon/komposisi tipografi yang dirancang, bukan persegi panjang ungu kosong. Jangan mengarang identitas mitra, rating, jumlah pengguna, logo, atau bukti keberhasilan. Gambar mempunyai rasio dan cropping konsisten serta alt sesuai fungsi.

Jika aset visual final belum tersedia, catat sebagai pekerjaan tersisa. Jangan menyebut fallback sementara sebagai hasil visual final tanpa penjelasan.

### 19.6 Penerimaan visual sebelum hasil dinyatakan selesai

Implementasikan dahulu lima layar representatif: landing, katalog/detail event, aktivitas Volunteer, pengelolaan event Organizer, dan dashboard Admin. Periksa screenshot desktop dan mobile pada data yang masuk akal. Setelah bahasa visual konsisten, terapkan ke formulir serta halaman lainnya.

- [ ] Dalam lima detik pembaca dapat mengenali tujuan halaman dan tindakan utamanya.
- [ ] Setiap fitur utama pada peta 19.4 memiliki titik masuk yang terlihat dan label yang dipahami.
- [ ] Tidak ada deretan kartu seragam untuk seluruh jenis konten; setiap jenis mempunyai bentuk yang sesuai.
- [ ] Gambar/ilustrasi, font utama, ikon, dan spacing benar-benar diterapkan, bukan hanya disebut di dokumen.
- [ ] Tidak ada bidang kosong besar, teks demo internal yang mendominasi hero, atau kartu KPI nol pada onboarding pengguna umum. Penanda demo/sandbox tetap jelas dan dekat konteksnya.
- [ ] Aktivitas pengguna memakai web utama; Admin memakai panel terpisah dan hanya Admin mendapat analitik.
- [ ] Tampilan diuji dengan judul panjang, banyak status, tanpa foto, belum ada aktivitas, data terisi, validasi gagal, dan loading yang relevan.
- [ ] Elemen sekunder tidak menyaingi CTA utama; informasi penting tidak dihilangkan demi ruang putih.
- [ ] Screenshot pada 375/768/1440 px diperiksa, kemudian keyboard, fokus, kontras, dan reflow diverifikasi.
- [ ] Laporan akhir membedakan kelulusan fungsi, responsivitas, dan review visual; lolos tes backend tidak disebut bukti desain sudah elegan.

Skill UI/UX membantu menentukan aturan dan pola; kualitas hasil tetap memerlukan komposisi yang sengaja dirancang, aset yang sesuai, implementasi yang setia, serta review visual. Kata “minimalis” tidak menjadi alasan menghilangkan fitur, dan kata “lengkap” tidak menjadi alasan memenuhi satu layar dengan semua modul.


## 20. Implementasi dan bukti visual (1 Oktober 2026)

Implementasi memakai token netral, Plus Jakarta Sans lokal (400/500/600/700; SIL OFL), ilustrasi komunitas SVG orisinal, ikon outline orisinal yang konsisten, fallback kategori 16:9, daftar aktivitas pengguna, dan tabel pengelolaan Admin. Keluarga ikon orisinal digunakan tanpa menambah dependensi Lucide; stroke/ukuran konsisten pada komponen `icon`. Tidak ada foto, rating, testimoni, atau mitra rekaan. Poster asli/upload poster belum tersedia.

Halaman umum menggunakan `components/layout.blade.php`; panel memakai `components/admin-layout.blade.php` tanpa navbar/footer pemasaran. Grafik Admin memakai SVG server-side dengan tabel harian yang dapat dibuka melalui keyboard. Form tradisional menampilkan status proses saat submit; tidak ada permintaan data asinkron yang membutuhkan skeleton. Error agregasi dibedakan dari hasil kosong.

Runner `scripts/visual-review.mjs` menghasilkan screenshot pada 375/768/1440 px dengan fixture terpisah, termasuk judul panjang, kategori tanpa poster, aktivitas terisi, onboarding, katalog kosong, dan analitik kosong. Screenshot ditinjau terpisah dari PHPUnit. Lihat `PENGUJIAN.md` untuk batas pemeriksaan; seluruh checklist target tidak otomatis dianggap lulus.
