# Product Requirements Document (PRD)
# SkillMatch Volunteer

Versi: 1.3 — Dashboard analitik khusus Admin dan pemisahan panel pengelolaan  
Tanggal: 1 Oktober 2026  
Platform: Website responsif  
Rencana pengerjaan: 9 minggu

## 1. Dasar penyusunan dan status keputusan

PRD ini disusun berdasarkan konteks percakapan SkillMatch sebelumnya yang berhasil ditemukan. Tujuannya adalah memberikan acuan pembangunan website dan pengujian penerimaan.

| Status | Isi |
|---|---|
| Konsep yang dinyatakan pengguna sebelumnya | SkillMatch Volunteer merupakan website rekrutmen volunteer dengan tiga role: Volunteer, Organizer, dan Admin. Pencocokan menggunakan skill, availability, dan lokasi dengan bobot 50/30/20 serta metode rule-based. |
| Rancangan yang telah dibahas | Screening otomatis, assessment pilihan ganda, dokumen volunteer, Match Score, keputusan akhir manual oleh Organizer/EO, attendance sederhana, dan riwayat partisipasi. Target event kampus, komunitas, serta hiburan skala kecil–menengah dengan kategori yang dibatasi. |
| Usulan teknis dari pembahasan sebelumnya | Laravel/PHP, Blade, Tailwind CSS, Alpine.js, MySQL, Eloquent, Storage, Midtrans Sandbox, PHPUnit/Pest; rencana pengerjaan sembilan minggu. |
| Detail baru dalam PRD ini | Rumus normalisasi tiap komponen skor, status dan transisinya, batas unggahan, indikator keberhasilan, detail hak akses, serta kriteria penerimaan merupakan usulan operasional untuk membuat rancangan dapat diimplementasikan. |

Harga paket, manfaat tiap paket, daftar kategori final, kebijakan refund, serta hubungan nilai assessment dengan kelayakan belum ditemukan sebagai keputusan final. Bagian terkait ditandai sebagai usulan dan perlu diputuskan sebelum modul tersebut difinalkan.

Revisi 1.1 menindaklanjuti permintaan pengguna mengenai database yang cocok, unggah dokumen yang tampil pada akun perekrut terkait, dan keamanan website. Baseline teknis yang direkomendasikan adalah MySQL dengan InnoDB serta Laravel Storage privat. Batas dan kebijakan baru di bawah merupakan rancangan implementasi, bukan klaim bahwa sistem sudah dibangun atau telah aman.

### 1.1 Keputusan revisi 1.3 dan hasil peninjauan

Keputusan pengguna: **dashboard analitik hanya untuk Admin**. Volunteer dan Organizer menggunakan **Ringkasan Aktivitas** dalam web utama. Panel Admin merupakan area pengelolaan tersendiri, dengan login, URL, layout, dan navigasi terpisah dari halaman publik maupun profil pengguna.

Ketidakjelasan versi sebelumnya terdapat pada FR-17 yang mencampurkan tiga jenis ringkasan dan bagian 11 yang menamai seluruh halaman awal sebagai Dashboard. Revisi ini mengganti struktur kebutuhan tersebut, bukan hanya mengganti judul halaman. Grafik analitik platform, laporan lintas akun, dan kendali administratif hanya tersedia di panel Admin.

PRD v1.3 menjadi acuan untuk pemisahan area ini apabila design.md atau paket kode sebelumnya masih menampilkan dashboard untuk semua role. Revisi dokumen ini belum berarti kode ZIP sebelumnya sudah diubah. Implementasi perlu diselaraskan menurut bagian 14.2 dan dibuktikan dengan UAT-23–30.

## 2. Ringkasan produk

SkillMatch Volunteer adalah website yang membantu penyelenggara event menemukan dan mengelola volunteer sesuai kebutuhan posisi. Volunteer dapat melengkapi profil, mencari event, mengirim lamaran dan dokumen, mengikuti assessment, memantau hasil seleksi, serta melihat riwayat partisipasi.

Sistem melakukan screening berdasarkan persyaratan wajib dan menghitung Match Score menggunakan aturan yang transparan. Organizer meninjau hasil screening, dokumen, nilai assessment, dan skor kecocokan sebelum menerima atau menolak pelamar. Sistem juga mendukung pembayaran paket organizer dalam lingkungan sandbox serta pencatatan kehadiran sederhana.

## 3. Masalah dan tujuan

### 3.1 Masalah yang ingin diselesaikan

- Data pelamar dan dokumen tersebar sehingga sulit dibandingkan secara konsisten.
- Penyaringan manual membutuhkan waktu ketika jumlah pelamar meningkat.
- Keterampilan, jadwal tersedia, dan lokasi pelamar belum tersaji sebagai dasar kecocokan yang terukur.
- Volunteer kesulitan memantau tahapan seleksi dan menyimpan riwayat kegiatan.
- Seleksi, pembayaran layanan, dan pencatatan kehadiran belum berada dalam satu alur.

Pernyataan masalah ini merupakan hipotesis produk yang perlu divalidasi melalui wawancara atau uji pengguna; belum merupakan hasil penelitian lapangan.

### 3.2 Tujuan produk

1. Menyatukan alur rekrutmen dari publikasi event sampai riwayat partisipasi.
2. Memberikan hasil screening dan skor kecocokan yang dapat dijelaskan.
3. Membantu Organizer memprioritaskan peninjauan kandidat tanpa menggantikan keputusan manusia.
4. Memberikan status lamaran yang jelas kepada Volunteer.
5. Menyediakan demonstrasi pembayaran paket dan pelaksanaan event yang dapat diuji.

### 3.3 Ukuran keberhasilan usulan

| Indikator | Definisi dan target awal |
|---|---|
| Keberhasilan alur utama | Seluruh skenario kritis pada bagian 16 lulus sebelum demo akhir. |
| Konsistensi perhitungan | 100% hasil pada dataset uji tetap sesuai rumus dan dapat direproduksi. |
| Kejelasan status | Setiap perubahan status tersimpan dengan waktu, pelaku, serta alasan bila relevan. |
| Kemudahan penggunaan | Minimal 80% dari sedikitnya 5 peserta uji dapat melamar atau meninjau kandidat sesuai perannya tanpa bantuan langsung. |
| Efisiensi peninjauan | Ukur waktu peninjauan kandidat pada dataset yang sama sebelum dan sesudah memakai sistem; target perbaikan ditetapkan setelah baseline tersedia. |

## 4. Pengguna dan hak akses

| Aktor | Kebutuhan dan akses |
|---|---|
| Pengunjung | Melihat landing page, katalog event yang sudah dipublikasikan, detail event, serta halaman login/registrasi. |
| Volunteer | Mengelola profil sendiri, skill, availability, dokumen, lamaran, assessment, hasil seleksi, dan riwayat sendiri. |
| Organizer/EO | Mengelola event miliknya, posisi, persyaratan, assessment, kandidat, keputusan seleksi, pembayaran paket, attendance, serta rekap operasional event sendiri melalui web utama. |
| Admin | Mengelola platform melalui panel Admin terpisah: dashboard analitik, seluruh akun/event, master data, paket, transaksi, konten publik, pengaturan operasional, dan audit aktivitas. Rincian batas kewenangan pada bagian 11.4. |

Pengunjung bukan role akun tambahan. Coordinator/PIC adalah stakeholder operasional, bukan role keempat pada MVP. Penugasan melalui akun bersama tidak diperbolehkan; delegasi akun PIC dapat menjadi pengembangan selanjutnya. Akun Admin dibuat melalui mekanisme internal, bukan registrasi publik.

## 5. Ruang lingkup

### 5.1 Wajib tersedia pada MVP (P0)

- Registrasi, login, logout, pemulihan kata sandi, dan pembatasan akses per role.
- Profil volunteer, master skill, lokasi, serta ketersediaan waktu.
- Event, posisi volunteer, kuota, persyaratan wajib, dan periode pendaftaran.
- Verifikasi event oleh Admin serta publikasi event.
- Katalog dan pencarian event.
- Lamaran dan unggahan dokumen.
- Screening otomatis, assessment pilihan ganda, dan Match Score.
- Seleksi manual Organizer dan notifikasi di dalam website.
- Paket organizer dan transaksi Midtrans Sandbox.
- Attendance sederhana, penyelesaian event, dan riwayat volunteer.
- Dashboard analitik dan pengelolaan platform khusus Admin pada panel terpisah.
- Ringkasan Aktivitas Volunteer dan Organizer pada web utama; rekap operasional event tetap terbatas milik Organizer.

### 5.2 Pengembangan berikutnya (P1)

Email notifikasi kegiatan (di luar email autentikasi), sertifikat digital, ekspor laporan lanjutan, daftar tunggu, dan akun khusus PIC. Fitur ini tidak menjadi syarat penyelesaian MVP.

### 5.3 Di luar ruang lingkup MVP

Rekomendasi AI/machine learning, aplikasi mobile native, chat real-time, proctoring assessment, absensi GPS/biometrik/QR, penggajian volunteer, pembayaran uang nyata, serta integrasi job portal eksternal. SkillMatch berfokus pada rekrutmen volunteer event.

## 6. Alur pengguna

### 6.1 Volunteer

1. Mendaftar sebagai Volunteer, masuk melalui /login, lalu membuka Ringkasan Aktivitas di /volunteer/aktivitas dalam web utama.
2. Mengisi profil, kota, skill, interval ketersediaan, dan dokumen yang diperlukan.
3. Mencari event dan membaca posisi, jadwal, kuota, serta persyaratan.
4. Memilih posisi dan mengirim lamaran sebelum batas waktu.
5. Sistem menyimpan snapshot lamaran dan metadata dokumen. Dokumen menjalani pemeriksaan keamanan; screening berjalan setelah seluruh dokumen wajib berstatus ready. Perekrut dapat melihat entri lamaran terkirim, tetapi tombol unduh hanya aktif untuk dokumen ready.
6. Jika lolos screening, Volunteer mengikuti assessment posisi.
7. Setelah assessment selesai, lamaran masuk ke daftar peninjauan Organizer bersama Match Score dan rincian hasil.
8. Volunteer melihat keputusan penerimaan atau penolakan.
9. Jika diterima, Volunteer mengikuti event; Organizer mencatat kehadiran.
10. Setelah event selesai, hasil partisipasi tersedia di riwayat.

### 6.2 Organizer

1. Mendaftar sebagai Organizer, masuk melalui /login, lalu membuka Ringkasan Aktivitas di /organizer/aktivitas dan melengkapi profil organisasi.
2. Membuat draft event, posisi, kuota, jadwal, persyaratan, dan assessment.
3. Memilih paket layanan dan mengajukan verifikasi event.
4. Jika perlu revisi, memperbaiki draft dan mengajukan kembali.
5. Setelah disetujui, melakukan pembayaran sandbox jika menggunakan paket berbayar.
6. Event dipublikasikan setelah syarat persetujuan dan pembayaran terpenuhi.
7. Meninjau kandidat, membandingkan skor dan dokumen, lalu menerima atau menolak secara manual.
8. Mencatat attendance, menyelesaikan event, dan melihat rekap operasional pada detail event sendiri.

### 6.3 Admin

1. Admin membuka /admin/login; tidak mendaftar melalui formulir publik.
2. Setelah autentikasi Admin, sistem membuka /admin/dashboard dengan layout khusus pengelolaan.
3. Admin meninjau indikator platform, tren, transaksi, dan antrean yang memerlukan tindakan.
4. Admin membuka modul terkait untuk mengelola akun, moderasi event, master data, paket, transaksi, konten publik, pengaturan, dan audit.
5. Aksi sensitif memerlukan konfirmasi, alasan bila relevan, serta pencatatan audit. Admin tidak mengambil alih keputusan seleksi rutin Organizer.
6. Tautan “Lihat Website” membuka halaman publik sebagai tujuan terpisah; menu pengelolaan tidak dipasang pada profil atau navigasi utama website.

## 7. Kebutuhan fungsional dan kriteria penerimaan

| ID | Fitur | Kebutuhan dan kriteria penerimaan |
|---|---|---|
| FR-01 | Autentikasi dan otorisasi | Email akun unik; registrasi hanya menawarkan Volunteer/Organizer; akses lintas role atau objek milik pengguna lain ditolak di server. |
| FR-02 | Profil Volunteer | Nama, kota, skill, dan availability wajib untuk melamar. Kolom yang belum lengkap ditunjukkan sebelum pengiriman. |
| FR-03 | Master data | Admin dapat mengelola kategori, skill, dan kota. Data yang sudah direferensikan dinonaktifkan, bukan dihapus sampai merusak riwayat. |
| FR-04 | Event dan posisi | Organizer menentukan judul, kategori, deskripsi, kota, lokasi, jadwal, batas pendaftaran, posisi, kuota, dan persyaratan. Tanggal tidak valid serta kuota kurang dari satu ditolak. |
| FR-05 | Verifikasi event | Admin dapat menyetujui atau meminta revisi dengan catatan. Event belum disetujui tidak muncul di katalog publik. |
| FR-06 | Katalog | Pengunjung dapat mencari judul dan memfilter kategori, kota, serta tanggal. Hasil dipaginasi dan menampilkan keadaan kosong bila tidak ditemukan. |
| FR-07 | Lamaran | Pengiriman hanya pada event yang membuka pendaftaran. Permintaan ganda tidak membuat lamaran duplikat. Volunteer melihat status dan waktu pengiriman. |
| FR-08 | Dokumen | Jenis dokumen wajib ditetapkan per posisi. PDF/JPG/PNG maksimal 5 MB per file, maksimal 5 file per lamaran, dengan validasi server, karantina, dan pemindaian. Metadata muncul di detail pelamar event terkait; unduh hanya diizinkan setelah status ready dan pemeriksaan kepemilikan melalui Policy. Rincian pada bagian 12.2–12.5. |
| FR-09 | Screening | Sistem memeriksa kelengkapan, dokumen wajib, dan syarat wajib terstruktur. Setiap kegagalan memiliki alasan spesifik. Pemeriksaan unggahan tidak dianggap sebagai verifikasi keaslian dokumen. |
| FR-10 | Assessment | Pelamar yang lolos screening dapat mengikuti satu percobaan assessment pilihan ganda. Jawaban dan skor dihitung di server; kunci jawaban tidak dikirim ke browser peserta. |
| FR-11 | Matching | Sistem menghasilkan skor 0–100 serta rincian skill, availability, dan lokasi. Data dan versi aturan yang dipakai tersimpan. |
| FR-12 | Seleksi | Organizer hanya meninjau kandidat event miliknya. Penerimaan tidak melebihi kuota, termasuk ketika dua permintaan diproses bersamaan. Penolakan memiliki alasan singkat. |
| FR-13 | Notifikasi | Perubahan screening, keputusan seleksi, verifikasi event, dan pembayaran menghasilkan notifikasi kepada pemilik terkait tanpa duplikasi dari pemrosesan ulang. |
| FR-14 | Paket dan pembayaran | Organizer melihat ringkasan paket dan nominal sebelum checkout. Pembayaran terverifikasi mengaktifkan hak paket tepat satu kali. Redirect browser saja tidak mengubah transaksi menjadi paid. |
| FR-15 | Attendance | Hanya peserta diterima yang dapat diberi status hadir/tidak hadir oleh Organizer pemilik event. Perubahan attendance dicatat di audit log. |
| FR-16 | Riwayat | Setelah event selesai, Volunteer melihat event, posisi, tanggal, dan attendance. Kandidat diterima tetapi tidak hadir tidak ditampilkan sebagai partisipasi berhasil. |
| FR-17 | Dashboard analitik Admin | Hanya Admin mengakses /admin/dashboard. Menampilkan indikator dan tren platform berdasarkan definisi bagian 11.5, filter periode, keadaan kosong, dan waktu pembaruan data. |
| FR-18 | Ringkasan Aktivitas Volunteer | /volunteer/aktivitas menampilkan lamaran terakhir, assessment yang harus dikerjakan, jadwal event diterima, notifikasi, dan kelengkapan profil. Data hanya milik akun aktif; tidak memuat grafik analitik platform. |
| FR-19 | Ringkasan Aktivitas Organizer | /organizer/aktivitas menampilkan event sendiri, pelamar yang perlu ditinjau, status moderasi/pembayaran, tenggat, dan tugas attendance. Rekap jumlah per event boleh tersedia untuk membantu tindakan; tidak menyediakan dashboard analitik tersendiri. |
| FR-20 | Pemisahan panel Admin | Semua modul pengelolaan berada pada /admin/* dengan login dan layout Admin. Tidak menggunakan layout profil atau navbar publik; akun non-Admin ditolak oleh server pada halaman maupun endpoint data/aksi. |
| FR-21 | Pengelolaan platform | Admin mengelola akun, moderasi seluruh event, master data, paket, pemantauan transaksi, konten publik, dan pengaturan operasional sesuai bagian 11.4. Aksi sensitif diaudit; status paid tidak dapat ditetapkan bebas dari form. |
| FR-22 | Navigasi dan autentikasi per area | /login melayani Volunteer/Organizer; /admin/login khusus Admin. Redirect dan intended URL divalidasi sesuai role. Keluar dari panel kembali ke /admin/login; akun Admin dan akun pengguna umum tidak saling berubah role melalui UI. |

## 8. Aturan screening, assessment, dan Match Score

### 8.1 Pemisahan fungsi

Screening menentukan apakah persyaratan wajib terpenuhi. Assessment memberi bukti pengetahuan dasar. Match Score mengurutkan kecocokan profil dengan kebutuhan posisi. Penerimaan tetap menjadi tindakan manual Organizer, dan skor tinggi tidak menjamin penerimaan.

### 8.2 Screening otomatis

Usulan persyaratan wajib mencakup kelengkapan profil, keberadaan dokumen, skill yang ditandai wajib, dan cakupan jadwal apabila Organizer mensyaratkan kehadiran penuh. Aturan hanya menggunakan data terstruktur yang dapat diperiksa sistem. Pengalaman yang tertulis dalam CV tetap ditinjau Organizer.

Syarat yang bersifat preferensi tidak menyebabkan gagal screening; pengaruhnya berada pada skor kecocokan. UI harus membedakan syarat wajib dan preferensi. Lamaran gagal screening tidak diteruskan ke assessment. Usulan MVP: keputusan screening tidak dapat di-override; kesalahan aturan diperbaiki melalui mekanisme revisi terkontrol sebelum pendaftaran dibuka.

### 8.3 Rumus skor kecocokan

Bobot dari konsep sebelumnya:

**Match Score = (0,50 × S) + (0,30 × A) + (0,20 × L)**

Usulan definisi implementasi:

| Komponen | Definisi operasional |
|---|---|
| S — Skill | 100 × jumlah skill posisi yang cocok dengan profil / jumlah seluruh skill yang dibutuhkan posisi. Setiap posisi wajib memiliki minimal satu skill; ID skill digunakan agar ejaan tidak memengaruhi kecocokan. |
| A — Availability | 100 × durasi kebutuhan jadwal posisi yang tercakup availability volunteer / total durasi kebutuhan jadwal posisi. Interval tumpang tindih digabung sebelum dihitung dan skor dibatasi maksimal 100. |
| L — Lokasi | 100 jika kota profil sama dengan kota event dan 0 jika berbeda. MVP memakai pencocokan kota, bukan estimasi jarak. Lokasi berbeda tidak menggagalkan lamaran kecuali dinyatakan sebagai syarat wajib. |

Contoh: tiga dari empat skill cocok menghasilkan S = 75; enam dari delapan jam tercakup menghasilkan A = 75; kota sama menghasilkan L = 100. Skor akhir = 37,5 + 22,5 + 20 = **80**. Contoh ini berlaku jika skill yang tidak cocok dan kekurangan dua jam bukan pelanggaran syarat wajib.

Aturan tambahan:

- Jadwal posisi wajib memiliki durasi positif. Data kosong memblokir pengiriman, bukan menyebabkan pembagian dengan nol.
- Sistem menghitung presisi penuh dan menampilkan dua angka desimal.
- Pengurutan: Match Score tertinggi, lalu nilai assessment tertinggi, lalu waktu pengiriman paling awal, lalu ID lamaran untuk urutan stabil.
- Perubahan profil setelah melamar tidak mengubah skor historis. Data profil, aturan, dan jadwal disimpan sebagai snapshot saat pengiriman.
- Match Score dapat dihitung saat pengiriman, tetapi daftar kandidat siap ditinjau hanya memuat pelamar yang telah menyelesaikan assessment.

### 8.4 Assessment pilihan ganda

Usulan MVP: Organizer membuat set assessment per posisi menggunakan kategori kompetensi yang dibatasi. Setiap soal memiliki satu jawaban benar. Set tidak dapat dipublikasikan jika belum memiliki soal atau kunci valid.

**Nilai assessment = jumlah jawaban benar / jumlah soal × 100.**

Nilai ditampilkan terpisah dari Match Score; tidak ada bobot keempat. Usulan satu percobaan dengan durasi yang ditentukan sebelum event dipublikasikan. Jawaban disimpan selama pengerjaan, timer memakai waktu server, dan waktu habis menyebabkan pengiriman otomatis dengan jawaban kosong bernilai salah. Penyegaran halaman tidak mengulang timer.

Ambang kelulusan assessment belum ditetapkan. Baseline PRD ini menggunakan nilai sebagai bahan pertimbangan Organizer, tanpa penolakan otomatis berdasarkan nilai. Versi soal dan jawaban peserta disimpan agar hasil tidak berubah ketika bank soal diperbarui.

## 9. Status, transisi, dan aturan bisnis

### 9.1 Pisahkan status event

| Dimensi | Nilai dan aturan |
|---|---|
| Moderasi | draft → pending_review → approved atau revision_required; revision_required dapat diajukan ulang. |
| Publikasi | unpublished → published hanya jika approved dan paket memenuhi syarat; suspended dapat diterapkan Admin dengan alasan. |
| Pelaksanaan | upcoming → ongoing → completed; cancelled merupakan status akhir alternatif. |
| Pendaftaran | Diturunkan dari periode pendaftaran, publikasi, pembatalan, dan sisa kuota; event completed/cancelled tidak menerima lamaran. |

Perubahan persyaratan, assessment, kuota, dan jadwal dibekukan setelah ada lamaran pada MVP. Koreksi material setelah itu memerlukan proses Admin dan komunikasi kepada pelamar; implementasi otomatis koreksi massal berada di luar MVP.

### 9.2 Status lamaran

draft → submitted → documents_pending → screening_failed atau assessment_pending → under_review → accepted atau rejected.

Draft hanya terlihat oleh Volunteer pemilik. Status documents_pending menunggu pemeriksaan dokumen; jika semua dokumen sudah ready saat pengiriman, tahap ini langsung dilewati. Dokumen ditolak/gagal diperiksa tidak otomatis dianggap gagal kualifikasi: Volunteer diberi kesempatan memperbaiki sebelum tenggat. Setelah tenggat, dokumen belum lengkap ditangani sebagai screening_failed dengan alasan kelengkapan. Waktu penerimaan unggahan sebelum tenggat disimpan agar keterlambatan scanner tidak merugikan pelamar; scan yang masih berjalan mempertahankan status pending sampai selesai.

Usulan status withdrawn tersedia sebelum keputusan akhir. Status screening_failed, rejected, dan withdrawn bersifat akhir pada MVP. Koreksi keputusan setelah penerimaan harus dicatat dan bukan tindakan bebas tanpa audit. Event cancelled tetap ditampilkan bersama status lamaran terakhir sehingga riwayat tidak hilang.

Usulan batas satu lamaran per Volunteer per event, untuk satu posisi. Batas ini perlu dikonfirmasi sebelum desain database dibekukan. Volunteer tidak dapat diterima pada dua event dengan interval tugas yang bertumpang tindih; pemeriksaan dilakukan kembali secara transaksional saat penerimaan.

### 9.3 Kehadiran dan penyelesaian

Status attendance: not_recorded, present, atau absent. Usulan MVP memakai satu catatan kehadiran per peserta per event; absensi per sesi belum termasuk cakupan. Event hanya dapat diselesaikan setelah jadwal berakhir dan attendance seluruh peserta diterima sudah diisi. Status completed berarti event selesai, sedangkan partisipasi berhasil mensyaratkan attendance present.

## 10. Paket dan pembayaran

Pembahasan sebelumnya mengusulkan paket organizer dan Midtrans Sandbox. **Organizer adalah pembayar paket layanan publikasi event**, sedangkan Admin memverifikasi event. Volunteer tidak dipungut biaya pendaftaran dalam baseline PRD ini. Penetapan pembayar dan objek transaksi ini merupakan penjelasan operasional atas usulan sebelumnya, bukan keputusan harga final.

Usulan model MVP: satu pembelian paket berlaku untuk satu event; paket gratis dapat tersedia jika disepakati. Harga dan manfaat paket disimpan sebagai konfigurasi Admin dan sebagai snapshot pada pesanan.

Alur: pilih paket → ajukan event → persetujuan Admin → checkout jika berbayar → verifikasi notifikasi pembayaran di server → publikasi jika syarat lengkap. Event gratis dapat dipublikasikan setelah disetujui.

Status internal transaksi: pending, paid, failed, expired, dan cancelled. Implementasi pemetaan status penyedia harus diverifikasi terhadap dokumentasi resmi ketika modul dibangun. PRD ini tidak menetapkan detail API atau versi SDK.

Aturan penerimaan modul:

- Nominal berasal dari konfigurasi server, bukan input browser.
- Server memverifikasi keaslian notifikasi, order, nominal, dan status pembayaran sebelum aktivasi.
- Notifikasi duplikat tidak mengaktifkan paket dua kali.
- Notifikasi terlambat tidak menurunkan transaksi paid kembali menjadi pending.
- Status meragukan dapat direkonsiliasi melalui pemeriksaan server ke penyedia.
- Transaksi gagal atau kedaluwarsa dapat dicoba ulang dengan order baru; riwayat order lama tetap disimpan.
- Pembayaran pada event yang telah dibatalkan tidak otomatis memublikasikan event; kasus masuk penanganan Admin.
- Refund otomatis belum termasuk MVP. Kebijakan pembatalan/refund wajib diputuskan sebelum penggunaan uang nyata.

## 11. Struktur halaman, panel Admin, dan ringkasan aktivitas

### 11.1 Pemisahan area dan URL

Baseline menggunakan satu aplikasi Laravel dan satu database MySQL, dengan dua area antarmuka yang terpisah. “Terpisah dari web utama” berarti pintu masuk, route, layout, navigasi, dan kewenangan Admin berdiri sendiri. Panel Admin bukan tab di profil atau sekadar menu tambahan pada navbar publik. Pemisahan ini tidak mensyaratkan dua proyek atau database terpisah.

| Area | URL acuan | Fungsi dan tampilan |
|---|---|---|
| Web utama publik | /, /events, /events/{id}, /packages | Landing page, katalog, detail event, dan informasi paket dengan navbar publik. |
| Login pengguna | /login, /register | Autentikasi Volunteer/Organizer; registrasi tidak menawarkan Admin. |
| Aktivitas Volunteer | /volunteer/aktivitas | Ringkasan tugas dan status pribadi, memakai layout web utama. |
| Aktivitas Organizer | /organizer/aktivitas | Ringkasan pengelolaan event milik sendiri, memakai layout web utama. |
| Profil pengguna | /profil | Profil dan pengaturan akun Volunteer/Organizer; halaman terpisah dari Ringkasan Aktivitas. |
| Login Admin | /admin/login | Halaman login khusus panel pengelolaan. |
| Dashboard Admin | /admin/dashboard | Satu-satunya dashboard analitik platform. |
| Modul Admin | /admin/users, /admin/events, /admin/master-data, /admin/packages, /admin/transactions, /admin/content, /admin/settings, /admin/audit-logs | Pengelolaan platform melalui layout Admin. |
| Akun Admin | /admin/account | Pengaturan akun dan keamanan Admin dalam panel; bukan profil pengguna umum. |

URL di atas adalah target route PRD. Subdomain seperti admin.skillmatch.example dapat menjadi pilihan deployment berikutnya, tetapi tidak wajib untuk MVP. Semua pemeriksaan akses tetap wajib meskipun URL Admin tidak ditampilkan di web utama.

### 11.2 Ringkasan Aktivitas Volunteer

Nama menu: **Aktivitas Saya**. Judul halaman: **Ringkasan Aktivitas**. Setelah login, Volunteer langsung melihat hal yang perlu dilakukan:

- Kelengkapan profil dan tombol melengkapinya.
- Daftar lamaran terbaru dengan status, event, posisi, dan tautan detail.
- Assessment yang menunggu pengerjaan beserta tenggat dan tombol lanjut.
- Event yang telah menerima Volunteer, jadwal, dan status kehadiran jika sudah tersedia.
- Notifikasi terbaru serta akses ke riwayat partisipasi.

Jumlah sederhana seperti “3 lamaran menunggu” boleh menjadi badge/kartu kecil untuk memandu tindakan. Tidak ada grafik tren, laporan lintas akun, filter analitik periode, atau menu Dashboard. Ketika belum memiliki aktivitas, tampilkan ajakan melengkapi profil atau mencari event.

Navigasi web utama Volunteer: Beranda, Cari Event, Aktivitas Saya, Lamaran Saya, Riwayat, Notifikasi, dan Profil.

### 11.3 Ringkasan Aktivitas Organizer

Nama menu: **Aktivitas Saya**. Judul halaman: **Ringkasan Aktivitas**. Konten berfokus pada pekerjaan pengelolaan event sendiri:

- Event draft, menunggu verifikasi, perlu revisi, atau sedang membuka pendaftaran.
- Jumlah pelamar yang siap ditinjau per event dengan tombol Tinjau Pelamar.
- Sisa kuota posisi di dalam event terkait.
- Pembayaran paket yang menunggu penyelesaian dan status paket per event.
- Jadwal terdekat, attendance yang belum dicatat, dan event yang siap diselesaikan.
- Notifikasi serta tombol Buat Event.

Jumlah pelamar, peserta diterima, dan attendance merupakan rekap operasional milik event tersebut. Tampilkan daftar, status, badge, serta tindakan; tidak membuat dashboard analitik atau grafik pertumbuhan bagi Organizer. Rekap event tetap dapat dibuka dari detail event tanpa mengakses data penyelenggara lain.

Navigasi web utama Organizer: Beranda, Cari Event, Aktivitas Saya, Event Saya, Transaksi Saya, Notifikasi, dan Profil Organisasi. Paket dibeli dari alur event terkait agar objek pembeliannya jelas.

### 11.4 Panel Admin dan cakupan pengelolaan penuh

Panel memakai sidebar administratif, header khusus, pencarian/filter tabel sesuai modul, dan identitas “SkillMatch Admin”. Navbar publik dan footer pemasaran tidak digunakan di panel. Profil/keamanan Admin berada pada menu akun panel. Dashboard menyajikan analitik dan pintasan; operasi rinci dilakukan pada halaman modul, bukan menumpuk seluruh form pada dashboard.

| Modul | Kewenangan Admin |
|---|---|
| Dashboard | Mengamati kondisi platform, tren, status transaksi, dan antrean tindakan. |
| Pengguna | Mencari akun Volunteer/Organizer, melihat status, menangguhkan atau mengaktifkan kembali dengan alasan. Tidak melihat password, menyamar sebagai pengguna, atau memberi role Admin dari registrasi publik. |
| Event & moderasi | Meninjau seluruh event, menyetujui, meminta revisi, menangguhkan/memulihkan publikasi dengan alasan, serta menindaklanjuti laporan/insiden melalui catatan operasional. Pemulihan publikasi tetap memeriksa persetujuan, pembayaran, dan lifecycle event. |
| Master data | Mengelola kategori, skill, dan kota; menonaktifkan data yang sudah dipakai untuk menjaga riwayat. |
| Paket | Mengatur harga, status tersedia, jumlah posisi, batas lamaran, dan durasi. Perubahan tidak menimpa snapshot pembelian/event yang sudah berlaku. |
| Transaksi | Melihat order, pemilik, event, paket, nominal, status, dan riwayat verifikasi; meminta sinkronisasi status melalui gateway. Tidak mengubah paid atau nominal transaksi historis secara bebas. Refund otomatis tetap di luar MVP. |
| Konten publik | Mengelola teks informasi platform, FAQ, kontak bantuan, dan pengumuman sederhana. Tidak menyediakan editor kode/script atau HTML bebas tanpa sanitasi. |
| Pengaturan | Mengelola konfigurasi operasional yang diizinkan, seperti kontak bantuan dan status layanan. Secret gateway, APP_KEY, kredensial database, serta bobot matching tidak menjadi input bebas pada panel. |
| Audit & operasional | Membaca log tindakan sensitif dan status job pemindaian/gagal untuk penanganan. Log tidak dapat dihapus atau ditulis ulang melalui UI; Admin tidak dapat menandai dokumen aman tanpa pemindaian. |

Pengelolaan penuh berarti kendali administratif lintas platform dengan otorisasi dan jejak audit. Seleksi rutin kandidat tetap dilakukan Organizer pemilik event. Admin tidak otomatis mendapat akses isi CV; batas akses dokumen pada bagian 12.3 tetap berlaku. Perubahan data historis, penghapusan permanen, dan pengecualian proses tidak boleh dilakukan tanpa prosedur yang ditentukan.

### 11.5 Isi dan definisi dashboard analitik Admin

Dashboard memuat kartu indikator, grafik sederhana, filter periode, serta daftar pekerjaan mendesak. Filter awal: 30 hari terakhir; pilihan 7 hari, 30 hari, dan rentang tanggal. Tanggal ditampilkan dalam WIB. Indikator keadaan saat ini harus dibedakan dari indikator aktivitas selama periode agar angka tidak menyesatkan.

| Indikator | Definisi dan sumber |
|---|---|
| Total akun saat ini | Akun Volunteer dan Organizer tersimpan, dipisah menurut role/status; akun Admin ditampilkan terpisah. Tidak berubah hanya karena filter periode. |
| Akun baru | Jumlah akun Volunteer/Organizer menurut created_at dalam periode, disertai tren harian. |
| Event aktif saat ini | Event published, bukan cancelled/completed, dan jadwal akhir belum lewat. Event aktif tidak selalu sedang membuka pendaftaran. |
| Event dibuat dalam periode | Event menurut created_at; distribusi status diberi label sebagai status terkini cohort tersebut. |
| Lamaran terkirim | Lamaran dengan submitted_at dalam periode, menghitung application_id unik; draft dikecualikan. Tampilkan tren harian serta distribusi status terkini dari lamaran dalam periode itu. |
| Pembayaran terverifikasi | Order unik paid menurut paid_at dalam periode; jumlahkan nominal snapshot. Pending/failed/expired tidak dihitung. Untuk MVP berlabel “Nilai transaksi sandbox”, bukan pendapatan uang nyata. |
| Penggunaan paket | Jumlah aktivasi paket per event menurut activated_at dalam periode; pisahkan Free/Standard/Premium. Pembayaran belum terverifikasi tidak dihitung sebagai aktivasi berbayar. |
| Antrean tindakan saat ini | Event pending_review, job dokumen gagal, dan transaksi yang memerlukan pemeriksaan. Menggunakan kondisi saat ini, terpisah dari grafik periode. |

Grafik P0 cukup untuk tren akun baru, event dibuat, lamaran terkirim, dan transaksi terverifikasi, ditambah komposisi paket/status yang relevan. Data kosong menghasilkan nilai 0 dan pesan informatif; data gagal dimuat tidak boleh ditampilkan seolah-olah bernilai 0. Tooltip/label menjelaskan cakupan dan satuan. Tampilkan waktu data terakhir dihitung; endpoint agregasi dan cache hanya dapat dibaca Admin.

Simpan paid_at dan activated_at saat perubahan pertama yang sah; pemrosesan ulang tidak memindahkan timestamp historis. Jangan menggunakan updated_at umum sebagai pengganti tanggal pembayaran/aktivasi. Jika data lama tidak mempunyai waktu valid, tandai tidak tersedia dan jangan mengarang tanggal.

### 11.6 Ketentuan desain dan akses per area

- Bahasa Indonesia; form berlabel, keyboard dapat digunakan, dan layout responsif.
- Web utama Volunteer/Organizer tetap berorientasi katalog dan aktivitas dengan navbar yang konsisten. Panel Admin memakai layout dan sidebar terpisah; pada ponsel sidebar dapat dibuka-tutup.
- Menu Dashboard hanya ada di panel Admin. Menu pengelolaan global tidak muncul di web utama atau profil.
- Login umum hanya membentuk sesi pengguna umum; akun Admin diarahkan memakai pintu masuk Admin. Login Admin menolak akun Volunteer/Organizer dengan pesan autentikasi umum tanpa membocorkan detail akun.
- Pengunjung yang membuka halaman Admin terproteksi diarahkan ke login Admin. Pengguna non-Admin yang sudah masuk mendapat 403; endpoint data tidak mengirim agregat atau data parsial.
- Pisahkan konteks autentikasi Admin dan pengguna umum dengan guard/namespace sesi yang sesuai implementasi. Membuka area lain tidak otomatis mengubah role. Terapkan kebijakan logout dan intended redirect secara konsisten pada konteks terkait.
- Kartu event menampilkan judul, penyelenggara, kota, jadwal, tenggat, dan status. Detail kandidat menampilkan alasan screening serta komponen Match Score.
- Setiap halaman data memiliki keadaan kosong, gagal, berhasil, dan indikator proses jika ada permintaan asinkron. Aksi sensitif memiliki konfirmasi; warna status selalu disertai teks.

## 12. Data utama dan relasi

| Entitas | Data dan relasi penting |
|---|---|
| users | Identitas, email, password_hash, role, status akun. |
| volunteer_profiles / organizer_profiles | Profil sesuai role, terkait ke users. |
| skills / user_skills | Master skill dan relasi skill Volunteer. |
| availability_slots | Interval waktu tersedia milik Volunteer. |
| categories / cities | Master kategori dan lokasi terstandardisasi. |
| events | Organizer pemilik, kategori, kota, periode, status moderasi/publikasi/pelaksanaan. |
| positions / position_skills / position_schedules | Posisi event, kuota, kebutuhan skill wajib/preferensi, interval tugas. |
| position_requirements | Persyaratan terstruktur dan dokumen wajib. |
| applications | Volunteer, event, posisi, status, snapshot, komponen skor dan versi aturan; keunikan sesuai kebijakan satu lamaran per event. |
| application_documents / screening_results | Metadata file privat, application_id, uploader_id, disk, storage_key acak, original_name, MIME hasil deteksi, size_bytes, checksum SHA-256, status pemindaian, version, timestamps, hasil screening dan alasan kegagalan. |
| assessments / questions / question_options | Set dan versi soal per posisi; kunci jawaban hanya di server. |
| assessment_attempts / assessment_answers | Waktu mulai/berakhir, jawaban, nilai, status percobaan. |
| packages / orders / payments | Konfigurasi paket, snapshot harga/manfaat, event terkait, referensi transaksi unik. |
| attendance | Satu catatan per lamaran diterima, status, waktu, dan pencatat. |
| notifications / audit_logs | Pesan untuk penerima dan jejak perubahan penting. |

Riwayat partisipasi dapat diturunkan dari applications, events, dan attendance sehingga tidak memerlukan salinan data yang mudah berbeda. Foreign key dan unique constraint menjaga integritas. Penghapusan akun atau data master tidak boleh menghapus riwayat transaksi dan seleksi secara tidak sengaja.

### 12.1 Pilihan database dan integritas data

**Rekomendasi: MySQL dengan engine InnoDB**, menggunakan versi yang masih didukung dan kompatibel dengan Laravel serta hosting tim. Untuk pengembangan dan pengujian integrasi, gunakan keluarga database yang sama dengan deployment agar perilaku constraint dan transaksi konsisten. phpMyAdmin atau MySQL Workbench merupakan alat pengelolaan, bukan database tersendiri.

Alasan pemilihan: struktur SkillMatch memiliki banyak relasi; InnoDB mendukung foreign key dan transaksi sehingga cocok untuk menjaga hubungan lamaran, posisi, peserta, dan pembayaran [R1]. Keputusan ini mengikuti kebutuhan produk dan stack Laravel yang sudah dibahas, bukan klaim bahwa database lain tidak mampu.

| Data | Tempat penyimpanan |
|---|---|
| Akun, event, posisi, lamaran, skor, transaksi, attendance | Tabel MySQL melalui Eloquent dan migrations. |
| File CV, sertifikat, dan dokumen pendukung | Laravel Storage pada disk privat di server untuk MVP satu server; object storage privat jika deployment memerlukan penyimpanan bersama. |
| Metadata dokumen | Tabel application_documents; file biner tidak disimpan sebagai BLOB dalam baseline ini. |
| Poster event publik | Disk publik terpisah; tidak dicampur dengan dokumen pelamar. |

Relasi penentu akses: **documents.application_id → applications.position_id → positions.event_id → events.organizer_id**. Istilah documents di sini merujuk ke application_documents. Server menurunkan pemilik event dari relasi tersebut; organizer_id dari formulir klien tidak dipercaya. Jika applications juga menyimpan event_id, nilainya harus konsisten dengan posisi melalui constraint komposit atau validasi transaksional terpusat.

Constraint/index minimum: users.email unik; applications(volunteer_id, event_id) unik sesuai baseline; attendance.application_id unik; orders.reference unik; application_documents.storage_key unik; foreign key untuk relasi; indeks events(organizer_id, status yang relevan), applications(position_id, status), dan application_documents(application_id, status). Definisi indeks disesuaikan nama kolom final dan pola query, bukan dibuat tanpa pengukuran.

Penerimaan peserta dilakukan dalam transaksi dengan penguncian baris posisi dan baris Volunteer dalam urutan konsisten. Setelah lock, periksa ulang kuota dan bentrok jadwal sebelum menerima. Foreign key saja tidak mencegah kuota berlebih atau bentrok jadwal. Gunakan akun database aplikasi dengan hak minimum, tanpa akses root; port database tidak dibuka ke internet publik.

### 12.2 Konsep unggah sampai tampil pada akun perekrut

1. Volunteer yang sudah login dan memverifikasi email membuka draft lamaran untuk posisi tertentu. Server memastikan draft miliknya dan event masih dapat dilamar.
2. Volunteer mengunggah file dari halaman lamaran. CV menggunakan PDF; dokumen pendukung dapat PDF/JPG/PNG sesuai persyaratan posisi. Tidak perlu integrasi Google Drive atau pengiriman email untuk alur ini.
3. Laravel memeriksa jumlah, ukuran, ekstensi yang diizinkan, serta MIME berdasarkan isi. Nilai user_id, application_id, dan lokasi file ditentukan/diverifikasi server. File disimpan menggunakan nama acak pada area karantina privat.
4. Metadata disimpan di application_documents dengan status quarantined. Pemindaian dijalankan melalui job setelah transaksi metadata berhasil disimpan. Antarmuka menunjukkan “Sedang diperiksa”.
5. Jika pemeriksaan lulus, status menjadi ready. Jika terdeteksi ancaman, status rejected; jika pemindai gagal, status scan_failed dan tersedia percobaan ulang terkontrol. Keduanya tetap tidak dapat diunduh oleh perekrut.
6. Volunteer mengirim lamaran. Permintaan pengiriman bersifat idempoten; metadata lamaran kemudian muncul pada web utama **Organizer → Event Saya → Pelamar → Detail Pelamar → Dokumen**. Draft tidak pernah dibagikan kepada perekrut.
7. Organizer pemilik event melihat nama pelamar, jenis dokumen, nama file, ukuran, tanggal unggah, dan status pemeriksaan. Tombol Unduh tersedia hanya untuk file ready. Notifikasi internal dibuat setelah commit; dokumen tidak dikirim sebagai lampiran notifikasi.
8. Saat Unduh ditekan, server menjalankan DocumentPolicy, memeriksa lamaran/event pemilik, status file, dan status akun. Setelah lolos, server mengalirkan file privat sebagai attachment dan mencatat akses.

“Tampil di akun perekrut” berarti halaman perekrut membaca data lamaran terkait dari database, bukan memindahkan atau menggandakan file ke akun perekrut. MVP memperbarui daftar saat halaman dimuat ulang; WebSocket tidak diperlukan. Semua lamaran terkirim dapat dilihat pada tab Semua Pelamar, sedangkan tab Siap Ditinjau hanya memuat status under_review. Perekrut tidak dapat menerima kandidat sebelum tahap kelayakan selesai.

### 12.3 Penyimpanan, endpoint, dan izin

Tetapkan disk private_documents secara eksplisit pada config/filesystems.php dengan root di luar direktori public, misalnya storage/app/private/volunteer-documents. Laravel menyediakan abstraksi storage dan respons unduh [R2]. Dokumen tidak boleh berada di public/storage atau diakses menggunakan Storage::url pada disk publik. Jangan membuat symlink publik ke folder dokumen.

| Endpoint konseptual | Akses dan perilaku |
|---|---|
| POST /applications/{application}/documents | Volunteer pemilik, draft atau masa perbaikan dokumen, sebelum tenggat; validasi dan karantina. |
| GET /applications/{application}/documents | Pemilik lamaran; atau Organizer pemilik event untuk lamaran terkirim; hanya metadata aman. |
| GET /documents/{document}/download | Pemilik atau Organizer terkait; file harus ready dan akses masih aktif. |
| DELETE /documents/{document} | Pemilik pada draft atau masa perbaikan; setelah dikirim tidak dapat menghapus bukti seleksi secara bebas. |

DocumentPolicy memeriksa relasi kepemilikan setiap request [R3]; middleware role saja tidak cukup. UUID/ULID dapat mengurangi kemudahan menebak ID tetapi tidak menggantikan otorisasi. Route tidak menerima path file dari klien. storage_key berasal dari database dan tidak ikut ditampilkan ke publik.

Admin tidak otomatis berhak membaca semua isi dokumen. Untuk penanganan kasus, akses dokumen memerlukan permission khusus dan alasan yang dicatat; jika alur ini belum diimplementasikan, Admin hanya melihat metadata. Akun nonaktif dan lamaran withdrawn kehilangan akses perekrut ke dokumen. Retensi data tidak berarti akses perekrut harus tetap terbuka.

Baseline MVP menggunakan download melalui controller, sehingga izin diperiksa pada setiap permintaan. Jika kelak memakai signed URL object storage, URL hanya diterbitkan setelah otorisasi dan harus berumur pendek; selama belum kedaluwarsa URL dapat dipakai ulang oleh pemegangnya. Jangan menyebut signed URL sebagai pengganti pemeriksaan izin.

### 12.4 Validasi dan keamanan file

Desain memakai pertahanan berlapis yang sejalan dengan OWASP File Upload Cheat Sheet [R5]. Detail batas berikut merupakan keputusan rancangan SkillMatch:

- Maksimal 5 MB per file dan 5 file per lamaran; server menolak file tambahan. Batas web server/PHP diselaraskan agar pesan kesalahan dapat ditangani. Terapkan pembatasan frekuensi unggah per akun dan pemantauan kapasitas.
- Allowlist PDF/JPG/PNG; tolak SVG, HTML, script, executable, arsip, serta dokumen bermakro. Periksa ekstensi dan MIME hasil pembacaan isi [R4], bukan hanya Content-Type dari browser. Pemeriksaan tipe tidak membuktikan file bebas malware.
- Nama penyimpanan dihasilkan server; nama asli dibatasi panjangnya, dinormalisasi untuk tampilan, dan di-escape. Nama asli tidak digunakan sebagai path atau perintah shell.
- Gunakan pemindai malware server-side yang dipelihara, misalnya ClamAV. File terenkripsi/password-protected atau tidak dapat diperiksa ditolak dengan instruksi unggah ulang. Scanner timeout/error tidak boleh mengubah status menjadi ready.
- Pemindaian dilakukan secara lokal/layanan privat yang disetujui; dokumen pribadi tidak dikirim ke layanan pemindaian publik. Lingkungan demo tanpa scanner hanya menggunakan dokumen sintetis; tidak boleh dinyatakan siap menerima unggahan publik.
- Baseline hanya menyediakan download attachment dengan Content-Type terdeteksi, X-Content-Type-Options: nosniff, dan Cache-Control: private, no-store. Preview inline PDF tidak termasuk MVP; pengembangan preview memerlukan isolasi origin/sandbox atau konversi aman.
- Backend tidak mengeksekusi file unggahan. Scanner adalah pengurangan risiko, bukan jaminan bahwa seluruh ancaman baru pasti terdeteksi.

### 12.5 Konsistensi, versi, dan siklus hidup dokumen

Penyimpanan file dan transaksi MySQL bukan satu transaksi atomik. Jika penulisan file gagal, jangan menyimpan metadata ready. Jika database gagal setelah file tersimpan, hapus file sementara melalui kompensasi; job pembersihan berkala menghapus orphan setelah memastikan tidak direferensikan. Job pemindaian harus idempoten dan memeriksa status dokumen agar file yang sudah dihapus tidak diaktifkan kembali.

Dokumen dapat diganti pada draft. Setelah dikirim, snapshot dokumen dikunci; pengecualian hanya perbaikan file rejected/scan_failed sebelum tenggat. Penggantian membuat versi baru dan pemeriksaan ulang, bukan menimpa bukti sebelumnya tanpa catatan. Screening hanya berjalan setelah seluruh dokumen wajib ready. Kesalahan sistem pemindaian yang melewati tenggat ditangani Admin sebagai insiden operasional, bukan otomatis menolak pelamar.

Usulan retensi untuk dikonfirmasi: orphan/draft terbengkalai dibersihkan setelah 7 hari; isi dokumen lamaran dihapus 90 hari setelah event selesai/dibatalkan. Akses perekrut berakhir ketika lamaran withdrawn, akun ditangguhkan, atau masa akses pasca-event berakhir. Job retensi menghapus file dan metadata sensitif, meninggalkan catatan audit minimum serta riwayat partisipasi tanpa lampiran. Siklus kedaluwarsa backup dan penerapan ulang daftar penghapusan setelah restore harus didokumentasikan agar file terhapus tidak kembali aktif. Angka retensi ini merupakan pilihan produk, bukan klaim kewajiban hukum.

## 13. Kebutuhan nonfungsional

| Area | Target penerimaan usulan |
|---|---|
| Keamanan | Password di-hash; validasi server; proteksi sesi/CSRF; pembatasan percobaan login; pemeriksaan kepemilikan setiap objek; secret di konfigurasi lingkungan. |
| Dokumen privat | File disimpan privat dengan nama internal acak dan diakses melalui pemeriksaan otorisasi; URL publik permanen tidak digunakan. |
| Privasi | Katalog tidak menampilkan CV, kontak pribadi, atau dokumen Volunteer. Penggunaan data dijelaskan pada formulir. Kebijakan retensi perlu diputuskan sebelum produksi. |
| Kinerja | Target p95 respons server katalog, dashboard Admin, Ringkasan Aktivitas, dan daftar kandidat ≤2 detik pada dataset 100 event/1.000 lamaran dan 20 pengguna bersamaan di lingkungan uji yang dicatat; tidak termasuk jaringan gateway. |
| Integritas | Penerimaan kandidat, pengecekan bentrok, perubahan kuota, dan aktivasi paket konsisten saat ada permintaan bersamaan. |
| Aksesibilitas | Form dapat dioperasikan dengan keyboard, fokus terlihat, label jelas, dan pesan kesalahan menjelaskan tindakan perbaikan. |
| Responsivitas | Alur utama dapat dijalankan pada lebar layar 360 px, 768 px, dan 1280 px tanpa kehilangan fungsi. |
| Waktu | Simpan waktu secara konsisten; usulan MVP menampilkan Asia/Jakarta (WIB) secara eksplisit pada event dan assessment. |
| Pemulihan | Sediakan backup database dan file privat, serta buktikan setidaknya satu pemulihan di lingkungan uji. |

### 13.1 Kontrol keamanan wajib dan bukti penerimaan

| ID | Risiko | Persyaratan dan bukti penerimaan |
|---|---|---|
| SEC-01 | Akses akun/event/dokumen orang lain | Gunakan Policy untuk objek dan query terikat pemilik. Uji Organizer A tidak dapat membaca, mengunduh, atau mengubah objek milik B melalui manipulasi ID maupun request langsung. |
| SEC-02 | Pengambilalihan akun | Hash password dengan fasilitas Laravel; verifikasi email sebelum melamar/mempublikasikan; reset token terbatas waktu dan sekali pakai; rate limit login/reset/unggah. MFA Admin menjadi syarat deployment publik. |
| SEC-03 | Pembajakan sesi | HTTPS; cookie Secure/HttpOnly/SameSite sesuai alur; regenerasi sesi saat login; invalidasi saat logout/reset password; timeout sesi untuk akun sensitif. |
| SEC-04 | CSRF dan manipulasi form | Proteksi CSRF pada seluruh aksi browser yang mengubah data. Webhook pembayaran memakai verifikasi server tersendiri; pengecualian CSRF hanya untuk endpoint tersebut. |
| SEC-05 | SQL injection dan mass assignment | Eloquent/query builder dengan binding; allowlist field tervalidasi; role, skor, status seleksi, harga, dan owner_id tidak diisi dari input bebas. Uji payload tidak dapat menaikkan role atau mengubah harga. |
| SEC-06 | XSS dan konten berbahaya | Escape tampilan Blade, termasuk nama file/deskripsi. Hindari HTML mentah untuk input pengguna; jika rich text dibutuhkan gunakan sanitasi teruji. Terapkan CSP yang diuji kompatibel dengan checkout, bukan wildcard luas. |
| SEC-07 | Malware dan kebocoran unggahan | Terapkan bagian 12.4; file karantina dan scan_failed tidak dapat diunduh. Pengujian menggunakan fixture aman pemindai, bukan malware nyata. |
| SEC-08 | Kebocoran konfigurasi | APP_DEBUG=false saat deploy; .env dan secret tidak masuk repository/log; akses database minimum; web root hanya direktori public. Uji .env dan folder storage tidak dapat diakses dari web. |
| SEC-09 | Manipulasi transaksi/kuota | Verifikasi webhook, nominal, dan order; idempotensi; transaksi dan locking untuk penerimaan. Uji persaingan dua request dan webhook berulang. |
| SEC-10 | Audit dan minimisasi data | Catat login relevan, keputusan seleksi, pengunduhan, perubahan izin, dan transaksi beserta pelaku/waktu/objek. Jangan mencatat password, token, isi CV, atau signed URL. Akses log dibatasi. |
| SEC-11 | Kerentanan dependensi dan deployment | Jalankan audit Composer/npm, tinjau temuan yang relevan, patch kerentanan kritis yang dapat dieksploitasi sebelum rilis; pisahkan kredensial development dan deployment. |
| SEC-12 | Kehilangan data | Backup database dan storage privat secara terkoordinasi, enkripsi backup, batasi akses, dan uji restore. Usulan target: backup harian, RPO 24 jam dan RTO 1 hari untuk MVP. |

Semua kontrol di atas adalah persyaratan yang perlu dibuktikan melalui pengujian; penggunaan Laravel atau MySQL tidak otomatis memenuhi persyaratan tersebut. Audit dependensi saja juga tidak menggantikan pengujian otorisasi dan alur bisnis.

## 14. Rancangan teknologi

Mengikuti usulan teknis yang telah dibahas: backend Laravel/PHP; frontend Blade, Tailwind CSS, dan Alpine.js; database MySQL/InnoDB melalui Eloquent; file melalui Laravel Storage privat, karantina dan job pemindaian malware; payment gateway Midtrans Sandbox; pengujian PHPUnit atau Pest.

Gunakan arsitektur monolitik untuk memudahkan integrasi tim. Pisahkan aturan screening, matching, seleksi, dan pembayaran ke layanan aplikasi agar dapat diuji. Versi framework, pustaka, serta prosedur integrasi ditetapkan saat inisialisasi sesuai lingkungan tim; PRD ini tidak mengunci versi yang belum disepakati.

Notifikasi internal wajib tersedia. Email untuk verifikasi akun dan pemulihan password termasuk kebutuhan autentikasi; email notifikasi kegiatan tetap P1. Antrean database Laravel dapat dipakai untuk job pemindaian dan notifikasi; worker dipantau dan job gagal dapat dicoba ulang. Ini menghindari kebutuhan Redis pada baseline MVP.

### 14.1 Setup lingkungan, database, dan backend

Pengguna tidak perlu membuat tabel atau seluruh backend secara manual. Implementasi harus menyertakan kode backend, migration, seeder, konfigurasi contoh, dan panduan menjalankan proyek. PRD ini merupakan spesifikasi; file aplikasi belum dibuat hanya karena PRD tersedia.

| Komponen | Cara penyiapan |
|---|---|
| PHP, Composer, Node.js/npm, dan MySQL | Dipasang/disediakan sekali pada komputer pengembangan atau server. Periksa lingkungan yang sudah tersedia sebelum memasang ulang. |
| Database MySQL kosong | Sediakan database skillmatch beserta akun database yang sesuai. Dapat dibuat melalui SQL, alat pengelolaan database, atau skrip provisioning; tabel aplikasi dikelola migration. |
| Konfigurasi lingkungan | Salin .env.example menjadi .env dan isi koneksi database, URL, email, serta kredensial sandbox sesuai lingkungan. Secret asli tidak disertakan dalam repository. |
| Struktur tabel, indeks, dan foreign key | Ditulis sebagai file migration Laravel dan diterapkan dengan php artisan migrate. Pengguna tidak membuat tabel satu per satu melalui phpMyAdmin. |
| Data awal | Seeder mengisi master kategori, skill, dan paket yang disepakati. Data demo dipisahkan dari data wajib; jangan menyertakan password Admin tetap untuk deployment publik. |
| Backend | Model, relasi, controller, Form Request, Policy, service screening/matching/payment, route, dan job unggahan harus diimplementasikan. Artisan dapat membuat kerangka file, tetapi tidak otomatis menulis aturan bisnis PRD. |
| Dokumen dan worker | Sertakan konfigurasi disk privat, karantina, koneksi scanner, serta instruksi menjalankan queue worker dan scheduler. Folder dokumen tidak menggunakan symlink publik. |

Urutan menjalankan proyek setelah kode implementasi lengkap: pasang dependensi Composer/npm sesuai lockfile → salin dan isi .env → buat application key untuk instalasi baru → pastikan MySQL/database tersedia → jalankan migration → jalankan seeder yang sesuai → build frontend → jalankan aplikasi, worker, dan scheduler. Untuk deployment, key yang sudah digunakan harus dipertahankan; jangan dibuat ulang pada setiap rilis.

Perintah inti untuk menerapkan migration yang sudah ditulis:

```bash
php artisan migrate
```

Perintah tersebut menerapkan struktur yang didefinisikan migration pada koneksi database proyek [R6]. Menjalankannya pada proyek Laravel kosong hanya membuat tabel dari migration yang tersedia, bukan seluruh fitur SkillMatch. Migration perubahan berikutnya harus menjaga data yang sudah ada; migrate:fresh bukan perintah pembaruan rutin karena menghapus tabel.

Hasil implementasi wajib menyertakan README langkah demi langkah, .env.example tanpa secret, migrations, seeders, backend terintegrasi, konfigurasi storage/worker, dan pengujian. Setup dapat dibantu skrip untuk mengurangi pekerjaan berulang, tetapi kredensial dan ketersediaan layanan tetap mengikuti komputer/server tujuan.

### 14.2 Dampak revisi terhadap implementasi sebelumnya

Implementasi berikutnya wajib menyelaraskan design.md, route, controller, query, layout, menu, panduan menjalankan, dan pengujian dengan PRD v1.3:

1. Ganti halaman awal Volunteer/Organizer menjadi Ringkasan Aktivitas dengan daftar tindakan; hapus grafik analitik dan label Dashboard pada area tersebut.
2. Pindahkan route tujuan ke /volunteer/aktivitas dan /organizer/aktivitas. Route dashboard lama, bila dipertahankan sementara, hanya mengarahkan pemilik role yang benar ke URL aktivitas baru.
3. Pisahkan login, layout, navigasi, dan konteks autentikasi Admin. Lindungi seluruh /admin/*, termasuk endpoint grafik, unduhan laporan, dan aksi pengelolaan.
4. Bangun agregasi analitik serta modul Admin sesuai bagian 11.4–11.5. Tambahkan migration timestamp pembayaran/aktivasi jika belum ada; pemisahan antarmuka sendiri tidak memerlukan database kedua.
5. Perbarui pengujian dan dokumentasi. Kelulusan tes ZIP terdahulu bukan bukti bahwa persyaratan revisi ini telah terpenuhi.

## 15. Jadwal pengembangan

### 15.1 Rencana sembilan minggu

| Minggu | Hasil yang harus tersedia |
|---|---|
| 1 | Finalisasi PRD, keputusan prioritas, ERD, wireframe, repository, autentikasi dan role dasar. |
| 2 | Master data, profil, event/posisi, dan katalog awal. |
| 3 | Draft/lamaran, dokumen privat, validasi, scanner/worker, DocumentPolicy, dan screening terintegrasi. |
| 4 | Matching teruji dan assessment dapat dikerjakan end-to-end. |
| 5 | Daftar kandidat, seleksi, kuota, konflik jadwal, notifikasi. |
| 6 | Verifikasi event, paket, pembayaran sandbox, dan aktivasi publikasi terintegrasi. |
| 7 | Attendance, penyelesaian event, riwayat, Ringkasan Aktivitas pengguna, panel Admin dan analitik. |
| 8 | Pengujian integrasi, otorisasi, transaksi bersamaan, webhook, dan perbaikan bug. |
| 9 | UAT, perbaikan akhir, dokumentasi, persiapan deployment dan demo. |

Jadwal merupakan rencana pengerjaan, bukan estimasi yang sudah dibuktikan. Fitur P1 tidak dimasukkan sebelum alur P0 stabil.

## 16. Skenario penerimaan utama

| ID | Skenario | Hasil yang diharapkan |
|---|---|---|
| UAT-01 | Volunteer melengkapi profil, melamar, lolos screening, dan mengikuti assessment. | Satu lamaran tersimpan, skor sesuai rumus, status under_review. |
| UAT-02 | Dokumen atau skill wajib tidak terpenuhi. | Screening gagal dengan alasan; akses assessment ditolak. |
| UAT-03 | Dua permintaan penerimaan memperebutkan satu kuota. | Hanya satu berhasil; jumlah accepted tidak melebihi kuota. |
| UAT-04 | Kandidat sudah diterima pada jadwal lain yang bertumpang tindih. | Penerimaan kedua ditolak dengan penjelasan konflik. |
| UAT-05 | Organizer mencoba membuka dokumen pelamar event milik Organizer lain. | Akses ditolak walaupun mengetahui ID atau URL objek. |
| UAT-06 | Timer assessment habis, halaman disegarkan, atau pengiriman diulang. | Batas waktu dan satu percobaan tetap berlaku; tidak muncul nilai/attempt ganda. |
| UAT-07 | Skor memakai contoh S=75, A=75, L=100. | Hasil 80,00 dan rincian komponen sesuai. |
| UAT-08 | Webhook pembayaran valid dikirim dua kali atau datang tidak berurutan. | Pembayaran dan hak paket konsisten, aktivasi hanya satu kali. |
| UAT-09 | Browser mengirim klaim pembayaran sukses tanpa verifikasi server. | Transaksi tidak menjadi paid dan event tetap belum memenuhi syarat publikasi. |
| UAT-10 | Attendance diisi lalu event diselesaikan. | Riwayat menampilkan status kehadiran yang benar; absent tidak diakui sebagai partisipasi berhasil. |
| UAT-11 | Lamaran dikirim dua kali atau setelah tenggat. | Tidak ada duplikasi; kiriman melewati tenggat ditolak menggunakan waktu server. |
| UAT-12 | Event gratis/berbayar belum disetujui atau pembayaran belum lunas. | Tidak dapat dipublikasikan sebelum memenuhi syarat masing-masing. |

### 16.1 Tambahan pengujian database, dokumen, dan keamanan

| ID | Skenario | Hasil yang diharapkan |
|---|---|---|
| UAT-13 | Volunteer mengunggah CV dan mengirim lamaran ke event Organizer A. | Entri terlihat pada A setelah refresh; tidak terlihat pada B; draft tidak terlihat sebelum dikirim. |
| UAT-14 | Pengguna mengganti ID dokumen, application_id, atau path pada request. | Akses di luar hak ditolak tanpa membocorkan storage_key atau isi file. |
| UAT-15 | File lebih dari 5 MB, file keenam, atau script diberi ekstensi PDF. | Validasi menolak tanpa membuat file ready; file sementara dibersihkan. |
| UAT-16 | Scanner menemukan fixture uji atau sedang tidak tersedia. | File rejected atau scan_failed, tidak dapat diunduh; retry aman dan status jelas. |
| UAT-17 | Unggah berhasil tetapi penyimpanan metadata gagal, atau sebaliknya. | Tidak ada dokumen ready yang hilang; file orphan terhapus melalui kompensasi/pembersihan. |
| UAT-18 | Nama file berisi HTML/script, traversal, atau karakter khusus. | Nama ditampilkan sebagai teks; tidak memengaruhi path atau mengeksekusi script. |
| UAT-19 | Organizer mengunduh file ready lalu Volunteer menarik lamaran. | Unduhan pertama tercatat; permintaan berikutnya ditolak. Salinan yang sudah diunduh di perangkat penerima tidak dapat ditarik kembali oleh sistem. |
| UAT-20 | Request mengubah role/skor/harga atau mengirim aksi tanpa CSRF. | Perubahan tidak sah ditolak; nilai sensitif tetap berasal dari server. |
| UAT-21 | Retensi dijalankan dan backup dipulihkan pada lingkungan uji. | File kedaluwarsa tidak aktif kembali; data relasional dan file yang masih sah konsisten. |
| UAT-22 | Pengiriman ulang lamaran/job/notification atau penerimaan paralel. | Tidak ada lamaran, aktivasi, atau notifikasi ganda; constraint dan locking bekerja pada MySQL. |

### 16.2 Penerimaan pemisahan panel dan analitik

| ID | Skenario | Hasil yang diharapkan |
|---|---|---|
| UAT-23 | Volunteer/Organizer login melalui web utama. | Mendarat di Ringkasan Aktivitas sesuai role; tidak ada menu Dashboard atau grafik analitik platform. |
| UAT-24 | Admin login melalui /admin/login. | Mendarat di /admin/dashboard dengan sidebar/header khusus; tidak memakai layout profil atau navbar publik. |
| UAT-25 | Non-Admin meminta halaman, endpoint agregasi, atau aksi Admin secara langsung. | Seluruh akses ditolak di server tanpa membocorkan data; menyembunyikan menu saja tidak dianggap lulus. |
| UAT-26 | Organizer A/Volunteer A membuka aktivitas milik akun B. | Data hanya milik akun aktif; manipulasi parameter tidak membuka data pihak lain. |
| UAT-27 | Admin mengganti periode dashboard dan menguji data kosong/gagal. | Angka cocok dengan fixture berdasarkan timestamp yang ditetapkan; kartu keadaan saat ini tetap berlabel jelas; gagal tidak disamarkan menjadi 0. |
| UAT-28 | Callback pembayaran diterima dua kali dan ada order pending/gagal. | Nilai paid hanya dihitung sekali; paid_at/activated_at tidak berubah; label transaksi sandbox jelas. |
| UAT-29 | Admin mengubah paket, menangguhkan event, atau menangani akun. | Kebijakan server berlaku, alasan/audit tersedia, snapshot historis terjaga, tanpa akses CV atau perubahan paid bebas. |
| UAT-30 | Login lintas pintu masuk, intended URL lintas role, dan logout. | Konteks sesi/redirect tetap sesuai role; login pengguna tidak membuka panel Admin; logout Admin mengakhiri konteks Admin dan kembali ke login Admin. |

## 17. Risiko dan keputusan yang masih terbuka

| Hal yang perlu ditetapkan | Baseline/usulan dalam PRD | Dampak jika berubah |
|---|---|---|
| Kategori event dan kompetensi awal | Dibatasi pada event kampus, komunitas, dan hiburan kecil–menengah; rincian kategori belum final. | Master data, bank soal, dan cakupan UAT. |
| Paket, harga, dan manfaat | Satu paket per event; harga ditentukan Admin; paket gratis opsional. | Checkout, entitlement, dan syarat publikasi. |
| Posisi assessment | Nilai terpisah sebagai pertimbangan Organizer; tanpa ambang gagal otomatis. | Status lamaran dan aturan kelayakan. |
| Banyak lamaran | Satu Volunteer melamar satu posisi per event. | Unique constraint dan alur perubahan posisi. |
| Lokasi dan availability | Kota sama/berbeda; proporsi jam tersedia; WIB untuk MVP. | Rumus dan input profil/jadwal. |
| Durasi dan jumlah soal | Dikonfigurasi sebelum publikasi; satu percobaan. | UX assessment dan data uji. |
| Pembatalan, koreksi, dan refund | Pembatalan disimpan; koreksi diaudit; refund otomatis di luar MVP. | Penanganan transaksi dan dukungan pengguna. |
| Data dokumen | Storage privat + scanner; usulan retensi draft 7 hari dan lampiran 90 hari pasca-event perlu dikonfirmasi. | Kapasitas, penghapusan, backup, dan kesiapan publik. |

Risiko utama adalah data skill yang hanya dinyatakan sendiri, subjektivitas bank soal, perluasan fitur, dan beban integrasi pembayaran. Mitigasinya: tampilkan asal data dan hasil assessment secara terpisah; tinjau soal sebelum publikasi; pertahankan cakupan P0; dan uji pembayaran sejak minggu keenam. Skor kecocokan tidak boleh ditampilkan sebagai jaminan kompetensi.

## 18. Definition of Done

MVP dinyatakan selesai ketika seluruh kebutuhan P0 terintegrasi; skenario UAT kritis lulus; tidak terdapat bug yang membuka data lintas pengguna, merusak status pembayaran, atau melampaui kuota; alur Volunteer, Organizer, dan Admin dapat didemonstrasikan; serta dokumentasi instalasi, konfigurasi sandbox, akun demo, ERD, dan panduan penggunaan tersedia.

Revisi 1.1 menambahkan syarat selesai: UAT-13–22 lulus, kontrol SEC-01–12 memiliki bukti pemeriksaan, dan alur dokumen benar-benar diuji pada dua akun Organizer yang berbeda. Sebelum menerima unggahan publik, scanner harus aktif, akses privat terverifikasi, dan kebijakan retensi difinalkan.

Revisi 1.3 menambahkan syarat selesai: FR-17–22 dan UAT-23–30 terpenuhi; analitik hanya tersedia bagi Admin; Ringkasan Aktivitas pengguna tetap pada web utama; panel Admin memiliki login, layout, navigasi, dan proteksi akses tersendiri. design.md, kode, serta README harus konsisten dengan pembagian ini.

Kesiapan produksi dengan uang nyata merupakan tahap terpisah setelah keputusan paket, pembatalan/refund, retensi data, serta konfigurasi operasional difinalkan.


## 19. Referensi teknis revisi 1.1

Referensi ditinjau 30 September 2026. Versi API final mengikuti versi framework yang dipilih tim; angka batas, retensi, dan target waktu adalah rancangan produk, bukan ketentuan dari referensi.

- [R1] MySQL — InnoDB Best Practices: https://dev.mysql.com/doc/refman/8.0/en/innodb-best-practices.html
- [R2] Laravel — File Storage: https://laravel.com/docs/filesystem
- [R3] Laravel — Authorization: https://laravel.com/docs/authorization
- [R4] Laravel — File Validation: https://laravel.com/docs/validation
- [R5] OWASP — File Upload Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html

- [R6] Laravel — Database Migrations: https://laravel.com/docs/migrations


## 18. Catatan implementasi revisi (1 Oktober 2026)

Acuan fungsi tetap PRD v1.3. Implementasi memakai guard `web`/`admin`, route aktivitas pengguna, dan panel Admin terpisah sebagaimana bagian 11. Guard menggunakan namespace berbeda dalam satu sesi; logout meregenerasi sesi dan mengakhiri konteks terkait. Intended redirect diabaikan dan diganti tujuan role yang tetap. Grafik disajikan server-side sebagai SVG dengan tabel alternatif; tidak ada endpoint agregasi publik. Rentang khusus dibatasi maksimal 366 hari.

Kategori/skill/kota dipertahankan sebagai label historis dan dapat dinonaktifkan. Perubahan nama dilakukan dengan menonaktifkan entri lama lalu membuat entri baru. Kota menyediakan saran master dan input nama baru; nama master nonaktif tidak dapat dipilih untuk data baru. Konten publik dikelola sebagai teks biasa; status layanan merupakan pemberitahuan, bukan sakelar penghentian transaksi. Aset event memakai fallback kategori karena upload poster belum tersedia.

Bukti fungsi revisi berada pada `tests/Feature/RevisionTest.php` (UAT-23–30), pengujian bisnis lama pada `SkillMatchTest.php`, dan hasil browser pada `storage/app/visual-review`. Hasil serta batas verifikasi dicatat di `PENGUJIAN.md`; target lain di dokumen ini tidak otomatis dinyatakan selesai.
