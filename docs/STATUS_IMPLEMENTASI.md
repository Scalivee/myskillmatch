# Status implementasi SkillMatch

Tanggal pemeriksaan: 1 Oktober 2026. Implementasi diperbarui untuk PRD v1.3/design v1.1 dan dapat dijalankan secara lokal, bukan pernyataan bahwa seluruh PRD telah selesai. PRD dan design.md tetap menjadi target produk; kode saat ini memiliki cakupan berikut.

| Area | Tersedia | Batasan / tindak lanjut |
|---|---|---|
| Akun | Registrasi Volunteer/Organizer, guard web/admin terpisah, login/logout per konteks, akun Admin, verifikasi email, reset password, role dan pembatasan akses | SMTP perlu konfigurasi; MFA Admin belum tersedia |
| Aktivitas & analitik | Aktivitas pribadi Volunteer/Organizer; dashboard Admin dengan periode WIB, tren harian, tabel alternatif, kondisi saat ini dan antrean | Ekspor belum tersedia; timestamp historis kosong dikecualikan dan diberi penanda |
| Profil | Kota, bio, skill, interval ketersediaan | Penyempurnaan UX profil masih dapat dilakukan |
| Event | Katalog, filter, draft Organizer, posisi, soal, verifikasi Admin, publikasi | Moderasi tangguh/pulih dengan alasan tersedia; pembatalan event dan upload poster belum tersedia |
| Lamaran | Draft, submit, screening, assessment, ranking, seleksi manual, attendance | Belum ada assessment adaptif atau rekomendasi berbasis model AI |
| Matching | Skill 50%, waktu 30%, kota 20%; persyaratan wajib dan pemeriksaan benturan/kuota | Kesesuaian dihitung dari data yang diisi pengguna |
| Dokumen | PDF maks. 5 MB, karantina privat, MIME, job ClamAV, policy download, audit | Instalasi scanner/signature diperlukan; penghapusan retensi otomatis belum tersedia |
| Pembayaran | Midtrans sandbox, order server, signature webhook, verifikasi status/nominal, idempotensi | Verifikasi ulang via gateway tersedia di Admin/Organizer dengan audit; kredensial nyata, production, refund, dan chargeback belum diuji/tersedia |
| Paket | Free/Standard/Premium, snapshot batas event, editor Admin | Harga Standard/Premium belum ditetapkan dan default kosong |
| Administrasi | Pencarian/filter pengguna dan event, master kategori/skill/kota dengan nonaktif, paket/status tersedia, konten publik teks biasa, email bantuan/status layanan, audit baca-saja | Tidak ada editor HTML, secret, penghapusan riwayat, atau override scanner/paid |
| Notifikasi | Notifikasi dalam aplikasi | Kanal WhatsApp/push belum tersedia |
| Keamanan | CSRF, session, password hashing, role/ownership, rate limit auth, CSP, private files, audit, transaksi database | Belum audit/pentest; MFA, backup/restore teruji, monitoring, hardening deployment masih diperlukan |
| Desain | Blade/Tailwind/Alpine CSP, responsif, tema indigo/teal, menu seluler | Font lokal berlisensi, ilustrasi SVG orisinal, fallback kategori, review screenshot 375/768/1440; uji pembaca layar dan audit kontras menyeluruh belum dilakukan |

## Batas paket awal

| Paket | Posisi/event | Lamaran/event | Durasi publikasi maksimum | Harga |
|---|---:|---:|---:|---|
| Free | 2 | 30 | 7 hari | Rp0 |
| Standard | 5 | 100 | 30 hari | Belum ditetapkan |
| Premium | 10 | 300 | 60 hari | Belum ditetapkan |

Ini konfigurasi awal yang dapat ditinjau ulang melalui Admin. Paket berlaku per event. Pembayaran sukses tidak menggantikan persetujuan Admin; publikasi membutuhkan verifikasi event dan status pembayaran yang benar. Redirect browser dari pembayaran saja tidak mengaktifkan paket.

## Dokumen dan perekrut

Volunteer mengunggah CV PDF serta dokumen pendukung PDF/JPG/PNG pada lamaran. Metadata disimpan di database; byte file berada pada penyimpanan privat. Worker memindai file, lalu hanya dokumen lolos yang dapat diunduh pemilik atau Organizer event terkait. Perekrut lain dan Admin tidak otomatis dapat membaca CV. File yang gagal dipindai tetap terkunci. Hak perekrut berakhir setelah jendela akses yang ditentukan policy; penghapusan fisik berdasarkan retensi belum otomatis.

## Sebelum digunakan publik

Selesaikan integrasi SMTP, ClamAV dan sandbox pembayaran terlebih dahulu. Setelah itu lakukan review keamanan, penerapan HTTPS serta cookie aman/APP_DEBUG=false, MFA Admin, pengelolaan rahasia, backup dan uji pemulihan, penghapusan retensi, pemantauan worker/scheduler, dan pengujian beban. Sesuaikan kebijakan privasi, persetujuan dokumen, serta ketentuan transaksi dengan operasi produk yang benar-benar dipilih. Paket ini tidak langsung melakukan deployment.
