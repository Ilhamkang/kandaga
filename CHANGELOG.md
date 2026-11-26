# v3.1.67 — Kerapian halaman Tentang & Disclaimer dan pengerasan keamanan rilis

- Merapikan redaksi Ketentuan Penggunaan & Penafian: pernyataan persetujuan aktif disederhanakan, klaim hak cipta sepihak dirumuskan ulang, dan keterbukaan kode dideklarasikan eksplisit (repo GitHub publik AGPL-3.0, data CC BY-NC-SA 4.0, kanal pelaporan kerentanan).
- Merapikan grid logo dukungan, foto profil, badge versi, roadmap, dan kotak dukungan pada halaman Tentang.
- Menambahkan `SECURITY.md` untuk pelaporan kerentanan privat, versi yang didukung, timeline respons, dan proses rilis keamanan.
- Menambahkan `DEPENDENCIES.md` dan menyelaraskan `THIRD_PARTY_NOTICES.md` sebagai inventaris pustaka pihak ketiga.
- Menghapus `assets/js/dompdf/` beserta 317 berkasnya karena tidak dipakai aplikasi; berkas lama akan dihapus saat pembaruan lewat daftar `delete` manifest.
- Menonaktifkan perilaku `sticky` pada sidebar halaman Tentang di layar kecil.

Tidak ada migrasi SQL, perubahan tabel, backup database, atau perubahan fitur katalog. Paket ini code-only dengan penghapusan 317 berkas dompdf.

# v3.1.66 — Kanal rilis GitHub untuk Update Center

- Menambahkan GitHub Actions untuk mem-build paket saat tag `v*` di-push dan mempublikasikan channel `version.json` serta ZIP ke branch `gh-pages`.
- Menambahkan `tools/build-release.php` untuk membuat manifest, checksum per berkas, checksum ZIP, dan metadata rilis dari berkas yang ter-track di Git.
- Mengarahkan Update Center dan indikator versi admin ke `https://raw.githubusercontent.com/Ilhamkang/kandaga/gh-pages/`.
- Menambahkan `.gitignore` untuk konfigurasi lokal, kredensial, cache, maintenance lock, upload, dan artefak build.
- Menghapus kredensial database plaintext dari `config/database.php`; gunakan `KANDAGA_DB_*` atau berkas lokal.

Tidak ada migrasi SQL, perubahan tabel, backup database, atau perubahan fitur katalog. Paket ini code-only.

# v3.1.65 — Tahap 3 hardening dan stabilisasi operasional

- Menambahkan pemeriksaan same-origin sebagai lapisan tambahan pada request POST yang sudah dilindungi CSRF.
- Memperketat validasi CSRF dan merotasi token setelah login berhasil untuk mengurangi risiko session fixation.
- Menegaskan konfigurasi session cookie-only, HttpOnly, dan SameSite Lax; logout kini menghapus cookie dengan atribut yang sama.
- Menambahkan pemeriksaan akhir agar hasil upload gambar benar-benar berupa file media non-eksekutabel setelah permission 0644 diterapkan.
- Menambahkan heartbeat dan usia aktivitas pada job Update Center; halaman pembaruan memberi peringatan jika tidak ada tick selama beberapa menit.
- Mengurangi frekuensi polling Update Center dan memaksa request status memakai same-origin credentials serta cache no-store.

Tidak ada migrasi SQL, perubahan tabel, backup database, perubahan Zotero, atau perubahan fitur katalog.

# v3.1.64 — Tahap 2 hardening keamanan berisiko rendah

- Menambahkan Content-Security-Policy-Report-Only dengan sumber kompatibel untuk aset lokal, Google Analytics, Zotero, viewer, font, dan media; kebijakan belum memblokir resource.
- Memperluas proteksi .htaccess terhadap direktori tools, migrasi, contoh impor, metadata rilis, manifest lokal, dan skrip diagnostik.
- Menambahkan perlindungan pada folder aset agar PHP, CGI, dan skrip server-side tidak dapat dieksekusi dari lokasi upload/media.
- Menetapkan permission file gambar upload ke 0644 setelah validasi MIME dan struktur gambar berhasil.
- Menambahkan diagnostik maintenance aktif dan unggahan paket yang tertunda di Pusat Pembaruan tanpa menghapus data secara otomatis.

Tidak ada migrasi SQL, perubahan tabel, backup database, perubahan Zotero, atau perubahan fitur katalog. Paket ini code-only.

# v3.1.63 — Tahap 1 baseline keamanan

- Menghapus API key Zotero dari kode sumber; halaman Referensi kini membaca `KANDAGA_ZOTERO_API_KEY` dari environment server.
- Menambahkan timeout, verifikasi TLS, pembatasan redirect, cache singkat di temporary directory, validasi respons, dan escaping output Zotero.
- Memperketat perlindungan direktori `config`, `includes`, helper Goah, engine impor, file konfigurasi, metadata repositori, dan berkas deployment melalui `.htaccess`.
- Menyamakan penjaga akses pada form admin yang dapat dipanggil langsung, termasuk form naskah, kabar, dan pendaftaran pengguna.
- Mengubah respons kegagalan API import agar tidak membocorkan pesan SQL/internal; detail tetap dicatat pada log server.

Tidak ada migrasi SQL, perubahan tabel, atau backup database. Fitur katalog, importer, SMTP, bookmark, statistik katalog, dan tampilan publik dipertahankan.

# v3.1.62 — Akses digital ringkas dan bibliografi responsif

- Indikator PDF/IIIF pada search menjadi ikon inline non-interaktif di samping metadata bahan.
- Memperluas aturan mobile untuk Statistik, Tentang, Kontributor, Disclaimer, Indeks Judul, dan Bibliografi.
- Menata ulang kartu monograf dan modal naskah terkait agar tidak terpotong pada mobile.
- Menampilkan cover monograf jika tersedia dan sitasi otomatis bergaya APSA tanpa label gaya.
- Menambahkan pagination ringkas dan batas 40 judul unik per halaman pada Indeks Judul.
- Menambahkan stylesheet indeks dan shared content yang diperlukan oleh halaman publik.

Tidak ada migrasi SQL atau backup database. API key Zotero pada halaman Referensi tidak diubah dan tetap menjadi agenda update keamanan terpisah.

# v3.1.61 — Fondasi layout mobile dan akses digital search

- Menambahkan aturan layout mobile bersama untuk mengurangi nested container pada frontend dan Goah.
- Mengurangi gutter homepage dan wrapper modul embedded tanpa mengubah logika Naskah Pilihan.
- Mengurangi padding bertingkat Goah serta menghapus padding global pada card mobile.
- Memperbaiki justified alignment pada field deskriptif detail naskah dan cache-busting stylesheet.
- Menambahkan ikon akses PDF/IIIF pada card search thumbnail dan list dengan validasi URL.
- Tidak ada migrasi SQL dan backup database.

# v3.1.60 — KRIR: gambar detail, editor naskah, dan artikel mobile

- Menetapkan batas ukuran gambar utama detail naskah secara terpisah untuk desktop dan mobile.
- Menambahkan alignment justified untuk isi deskripsi metadata.
- Memperbaiki tampilan HTML legacy pada field rich-text form edit naskah tanpa migrasi data.
- Menambahkan stylesheet responsif khusus kabar/artikel dan menghapus nested container pada Kabar Terbaru homepage.
- Tidak menyentuh Naskah Pilihan, SQL, migrasi, atau database; code-only tanpa backup database.

# v3.1.59 — Kompatibilitas tampilan gambar detail

- Gambar utama halaman detail kembali terpusat dan mempertahankan rasio asli pada desktop maupun mobile.
- Detail naskah tidak lagi menampilkan placeholder ketika gambar lokal/URL tidak tersedia atau gagal dimuat.
- Hover kartu Naskah Terkait dipulihkan dengan aturan CSS yang hanya berlaku pada halaman single.
- Tidak ada perubahan pada Naskah Pilihan halaman depan.
- Code-only; tanpa migrasi SQL dan tanpa backup database.

# v3.1.58 — Sinkronisasi statistik dan UX katalog

- Memperbaiki penghitungan status relasi naskah dengan penelitian agar memakai jumlah naskah unik dan tetap konsisten terhadap data bibliografi lama.
- Menyamakan tinggi dua kartu statistik trafik publik.
- Menyeragamkan pemisah daftar metadata dengan ampersand sebelum item terakhir.
- Menambahkan layout metadata detail manuskrip satu kolom untuk mobile.
- Menyeragamkan ikon sidebar terlipat dan menambahkan tooltip aksesibel.
- Mengizinkan kode inventaris kosong serta membuat dropdown lembaga penyimpanan dapat dicari.
- Code-only; tanpa migrasi SQL dan tanpa backup database.

# v3.1.57 — Konsistensi tipografi dan korespondensi

- Menambahkan sistem tipografi global dan token desain bersama.
- Menambahkan `assets/css/module-fonts.css` dan `assets/css/mobile-compat.css`.
- Menambahkan lapisan `style-admin-consistency.css` untuk seluruh modul Goah.
- Menyeragamkan tampilan Inbox Feedback dan Feedback Naskah pada desktop serta mobile.
- Memindahkan aturan inline korespondensi Inbox ke stylesheet bersama.
- Tidak ada migrasi SQL dan backup database.

# v3.1.56 — Balasan SMTP Inbox Feedback Kontak

- Menambahkan balasan langsung pada Inbox Feedback kontak.
- Menambahkan thread dan riwayat balasan tanpa mengubah `tb_inbox`.
- Memakai SMTP dan konfigurasi Email & Notifikasi yang sama dengan Feedback Naskah.
- Memperkuat validasi form kontak dan menambahkan CSRF.
- Menambahkan pencarian, filter, dan pagination inbox.
- Tidak ada migrasi SQL dan backup database.

# v3.1.55 — Korespondensi feedback dan Pengaturan Email

- Menambahkan thread korespondensi feedback dengan riwayat balasan terpisah dari tabel pesan asli.
- Menambahkan SMTP server-side untuk balasan admin dan email uji.
- Menambahkan menu Pengaturan yang responsif untuk layanan email dan notifikasi.
- Kredensial SMTP tidak pernah disimpan di browser, tabel feedback, atau ZIP pembaruan.
- SMTP tetap nonaktif sampai koneksi diuji berhasil; tidak ada migrasi dan tidak ada backup database.

# v3.1.54 — Statistik katalog tanpa log trafik mentah

- Menghentikan penulisan dan pembacaan aktif `website_visits` serta `website_presence` dari request publik dan admin.
- Menambahkan tabel agregat harian yang dibuat lazy tanpa migrasi dan tanpa backup database.
- Mempertahankan `naskah_views` serta statistik internal katalog dan relasi penelitian.
- Mempertahankan empat ringkasan agregat di footer dan menghapus indikator online.
- Menghapus rincian IP, geolokasi, perangkat, crawler, dan pengunjung unik dari panel Statistik Katalog.
- GA4 tetap hanya sebagai telemetri eksternal opsional.

# v3.1.53 — UI statistik, bookmark, dan bibliografi responsif

- Mengembalikan statistik Hari ini, 7 hari terakhir, Bulan ini, dan Total di footer.
- Mempertahankan label indikator aktif sebagai Pengunjung Online.
- Mengubah bookmark pada kartu thumbnail pencarian menjadi ikon bookmark outline dengan tanda plus di tengah tanpa kotak atau lingkaran latar.
- Mengubah warna ikon bookmark menjadi biru saat tersimpan.
- Memodernisasi kartu status relasi naskah dengan penelitian dan menambahkan modal rincian yang ringan.
- Membuat modal naskah terkait pada halaman bibliografi kompatibel dengan layar mobile menggunakan dialog scrollable, layout bertumpuk, dan tombol detail selebar konten.
- Menambahkan stylesheet bibliografi publik agar aturan responsif benar-benar ikut dalam paket.
- Tidak menambah migrasi SQL; backup database tetap dinonaktifkan.

# v3.1.52 — Event kunjungan ramping dan update code-only

- Mengurangi payload event baru `website_visits` ke field inti counter, pencarian, dan klasifikasi.
- Membiarkan kolom diagnostik historis tetap nullable tanpa mengisinya pada request baru.
- Menjaga crawler di luar `website_visits` sehingga log server menjadi sumber diagnostik crawler.
- Memaksa updater memakai backup kode saja; backup database dinonaktifkan untuk rilis tanpa migrasi.
- Tidak menambah migrasi SQL dan tidak menghapus data lama secara otomatis dalam proses pemasangan.

# v3.1.51 — Counter online ringkas dan kurasi gambar landing

- Footer frontend hanya memuat penghitung **Pengunjung online** dari presence heartbeat terverifikasi.
- Mengurangi query statistik pada setiap halaman publik dengan tidak lagi menghitung KPI pageview di footer.
- Memperbaiki pemilihan Naskah Pilihan agar hanya memakai upload yang ada atau URL gambar valid.
- Mengecualikan placeholder dan referensi file upload yang sudah hilang dari kartu Naskah Pilihan.
- Tidak ada migrasi SQL.

# v3.1.50 — Statistik admin ringkas dan efisien

- Merampingkan panel statistik admin menjadi ringkasan KPI, grafik harian ringan, dan rincian berbasis modal.
- Menghapus tampilan serta permintaan Google Analytics dari panel admin; telemetri GA4 publik tetap berjalan terpisah.
- Menghapus rincian geolokasi/IP dari panel statistik dan menghentikan pencatatan IP/user-agent baru pada jalur log modern.
- Menambahkan cache server singkat untuk penghitung dan seri harian agar pembukaan panel tidak memicu query berulang.
- Menambahkan endpoint rincian admin yang aman, terparameterisasi, dibatasi periode, dan dimuat hanya saat modal dibuka.
- Menambahkan indeks statistik halaman untuk mempercepat daftar halaman populer dan pencarian.

# v3.1.49 — Konsistensi analitik dan presence dinamis

- Memindahkan heartbeat ke footer bersama agar beranda dan halaman publik yang memakai footer ikut terverifikasi.
- Memperbarui angka aktif pada footer secara dinamis setelah heartbeat berhasil.
- Menyamakan indikator aktif dengan jendela lima menit berbasis heartbeat, sebagai pendekatan lokal terhadap GA4 Realtime.
- Menyatukan classifier crawler pada statistik popularitas naskah dan `website_visits`.
- Menghitung sidik pencarian berdasarkan kata kunci beserta filter, bukan kata kunci saja.
- Membersihkan token presence lama secara bertahap dan menambahkan indeks heartbeat.
- Menjelaskan batas retensi log mentah pada panel admin agar statistik rinci tidak disalahartikan sebagai arsip seluruh waktu.
- Menyelaraskan ringkasan bulanan dengan raw log dan rollup harian.

# v3.1.48 — Rekonsiliasi analitik dan online presence

- Memusatkan definisi trafik manusia pada `is_bot=0` dan `traffic_class='human'`.
- Memperbaiki penghitungan pageview dengan menggabungkan log aktif dan ringkasan harian tanpa duplikasi.
- Menambahkan identitas pengunjung per hari untuk menghitung pengunjung unik lintas periode tanpa menjumlahkan unik harian.
- Mengubah indikator online menjadi presence terverifikasi melalui heartbeat browser; reload tidak menaikkan jumlah online.
- Menstandarkan batas periode statistik menggunakan timezone aplikasi Asia/Jakarta.
- Menyatukan sumber data grafik harian panel admin dengan tabel raw dan rollup.
- Menambahkan rekonsiliasi visitor-day bertahap tanpa menghapus katalog atau log secara langsung.

# v3.1.45 — Pembaruan dapat dilanjutkan otomatis

- Pusat Pembaruan otomatis melanjutkan job aktif setelah admin masuk kembali.
- Status tahap, cursor, backup, dan maintenance tetap disimpan di server.
- Penutupan browser tidak lagi mengharuskan upload ulang paket.

# Riwayat Kandaga

## v3.1.42
- Menghubungkan form tambah/edit naskah dengan tabel struktur katalog tahap 1.
- Menambahkan input naskah induk/unit, identifier alternatif, dan lokasi historis.
- Menjaga importer lama tanpa perubahan.

## v3.1.41

## v3.1.41
- Menambahkan struktur aditif untuk naskah induk/unit, identifier alternatif, dan lokasi historis.
- Menjaga importer v3.1.40 tanpa perubahan perilaku.
- Menambahkan migrasi idempoten `013_catalog_structure_stage1`.

## v3.1.40
- Memperbaiki penghitung Pengunjung online berbasis token presence unik.
- Menormalisasi path cookie visitor untuk domain-root dan subdirektori.
- Mengecualikan crawler dari presence footer.
- Menambahkan pencarian aman pada daftar bibliografi admin.
- Menambahkan migrasi 012_website_presence.

## v3.1.39
- Mereklasifikasi nama crawler dan kelas trafik historis.
- Mengubah default bot menjadi `Unknown crawler`.
- Memperketat filter trafik manusia untuk statistik dan online counter.
- Menambahkan peringatan log analitik yang tidak konsisten.
- Menambahkan migrasi 011_analytics_reconciliation.

## v3.1.38
- Menambahkan taksonomi nama crawler untuk Meta, Google, PetalBot, dan crawler lain.
- Menambahkan panel crawler terdeteksi pada analitik admin.
- Menambahkan `noindex,follow` pada hasil pencarian dan `robots.txt` untuk mengurangi URL dinamis yang dirayapi.
- Menambahkan migrasi 010_bot_taxonomy tanpa menghapus data lama.

## v3.1.37
- Memisahkan pageview manusia dari bot/crawler pada statistik analitik.
- Menambahkan token pengunjung anonim, klasifikasi trafik, referrer, dan fingerprint request.
- Menambahkan metrik pengunjung unik dan IP berintensitas tinggi pada panel admin.
- Footer mempertahankan format ringkas dengan label “Total tampilan”.
- Menambahkan migrasi 009_analytics_reliability.

## v3.1.36
- Menambahkan resolver gambar terpusat untuk tampilan publik dan admin.
- Menormalkan URL IIIF/VHMML dengan slash ganda pada path.
- Menambahkan fallback placeholder saat gambar eksternal tidak dapat dimuat.
- Menambahkan tombol uji URL gambar pada form edit naskah.
- Tidak ada migrasi SQL.

## v3.1.35
- Sanitasi konten artikel dan validasi URL eksternal diperkuat.
- Validasi ukuran, dimensi, dan jumlah piksel upload gambar ditambahkan.
- Pusat Pembaruan menampilkan ringkasan perubahan setelah update selesai.
- Tidak ada migrasi SQL.

## v3.1.33
- Memperbaiki helper resolusi cover bibliografi untuk beberapa format path dan lokasi upload.
- Memulihkan tampilan thumbnail cover di seluruh modul Monograf dan Katalog.
- Menyamakan jarak atas container indeks Katalog dengan halaman Monograf.
- Tidak ada migrasi SQL.

## v3.1.32
- Memindahkan metadata detail katalog ke sidebar kanan.
- Menyeragamkan latar indeks katalog dengan halaman Monograf.
- Menghapus label navigasi/deskripsi yang berulang.
- Menetapkan ukuran thumbnail cover pada modal relasi monograf.
- Tidak ada migrasi SQL.

## v3.1.31
- Menyeragamkan tampilan latar dan header modul Monograf/Katalog.
- Menghapus tab Katalog dari halaman Monograf.
- Membuat detail katalog sebagai card cover + metadata APSA yang responsif.
- Menambahkan thumbnail cover monograf pada modal relasi manuskrip.
- Tidak ada migrasi SQL.

## v3.1.30
- Memuat helper versi secara eksplisit di `includes/navbar.php`.
- Memperbaiki HTTP 500 pada halaman beranda setelah v3.1.29.
- Tidak ada migrasi SQL.

## v3.1.29
- Memperbaiki konflik `public-mobile.css` dengan layout thumbnail search mobile.
- Mengembalikan card thumbnail menjadi susunan vertikal yang normal.
- Menghilangkan card mobile yang membesar dan teks lokasi yang pecah menjadi satu huruf per baris.
- Menambahkan cache-busting dinamis untuk stylesheet navbar, public mobile, search, dan script bookmark.
- Tidak ada migrasi SQL.

## v3.1.28
- Memperbaiki susunan kartu thumbnail search mobile: gambar di atas, metadata di bawah, dan tidak lagi tersusun vertikal per huruf.
- Menampilkan aksi Bookmark dan Detail Naskah sebagai ikon saja di pojok kanan atas kartu.
- Default pagination responsif: 16 item untuk mobile dan 15 item untuk desktop; pilihan 50/100 tetap tersedia.
- Memadatkan spacing front-end mobile dan menambahkan utilitas justified untuk konten deskriptif tanpa mengubah judul.
- Tidak ada migrasi SQL.

## v3.1.27
- Membuat mode Thumbnail dan List pada search mobile lebih berbeda secara visual.
- Mode thumbnail menjadi dua kolom; mode list dipadatkan dengan thumbnail dan metadata ringkas.
- Memindahkan Bookmark dan Detail Naskah ke pojok kanan atas card sebagai ikon.
- Menambahkan panel filter mobile dengan pilihan sementara dan konfirmasi Terapkan.
- Menambahkan token radius global untuk menyelaraskan sudut card, modal, tombol, input, dan badge.
- Tidak ada migrasi SQL.

## v3.1.26
- Memadatkan tampilan detail naskah pada perangkat mobile.
- Menjadikan metadata naratif justified dengan pengecualian untuk URL, kode, tombol, dan elemen teknis.
- Menyamakan ukuran tombol Lapor/Beri Masukan dengan Bookmark.
- Membatasi Naskah Terkait Lainnya menjadi empat kartu berisi gambar, judul, kode, dan lokasi.
- Mempertahankan algoritma pemeringkatan relasi tanpa menampilkan keterangan teknisnya.
- Tidak ada migrasi SQL.

## v3.1.25
- Menyatukan favicon seluruh halaman ke `assets/images/favicon.png`.
- Menata ulang detail naskah pada mobile menjadi label dan nilai bertumpuk.
- Mengoptimalkan referensi katalog/bibliografi, deskripsi panjang, dan lokasi koleksi agar memakai lebar layar secara proporsional.
- Layout desktop dua kolom tetap dipertahankan.
- Tidak ada migrasi SQL.

## v3.1.24
- Menambahkan deteksi tampilan mobile berbasis viewport dan stylesheet mobile-first.
- Menghilangkan margin container yang terlalu lebar pada layar kecil tanpa mengubah struktur data.
- Memperbaiki akses daftar pustaka dan form bibliografi untuk kontributor.
- Penghapusan bibliografi dan import RIS tetap khusus administrator.
- Tidak ada migrasi SQL.

## v3.1.23
- Menambahkan form terpisah tambah/edit bibliografi dengan upload cover lokal atau URL cover.
- Menampilkan cover dan sitasi APSA ringkas pada daftar bibliografi, katalog, dan relasi detail naskah.
- Menghilangkan pengulangan judul pada popup relasi bibliografi.
- Menambahkan migrasi idempoten `008_bibliography_covers`.

## v3.1.22
- Memperbaiki upload thumbnail artikel baru dan edit artikel.
- Validasi gambar kini berbasis MIME/struktur gambar yang valid, bukan kecocokan ekstensi yang terlalu ketat.
- Batas upload server ditampilkan di form dan error upload diteruskan ke dialog yang informatif.

## v3.1.21
- Memperbaiki bug `<small class=` yang masuk ke nilai Kode Inventaris pada form edit naskah.
- Menstabilkan penyimpanan artikel baru/edit, slug unik otomatis, draf tanpa gambar, dan pratinjau thumbnail.
- Menambahkan indikator proses berputar dan pelanjutan otomatis pada Pusat Pembaruan.

## v3.1.20
- Menetapkan property ID GA4 `558112895` sebagai default konfigurasi.
- Menghapus card Aktivitas kontribusi katalog dari dashboard Goah.
- Memperbarui header “Sampurasun” agar lebih proporsional, modern, dan responsif.

Dokumentasi rilis dikonsolidasikan ke berkas ini agar instalasi server tidak dipenuhi berkas catatan versi terpisah.

## v3.1.18

- Rilis rekonsiliasi code-only untuk menyamakan basis kode lokal dan online.
- Tidak membawa skrip deduplikasi, recovery, atau impor ulang data kunjungan.
- Database lokal `katalogus` dan online `kairag23_kamus` tidak ditimpa.
- Statistik internal Kandaga tetap dipisahkan dari Google Analytics 4.
- Konfigurasi database, analitik, gambar, upload, dan tabel backup dipertahankan.

## v3.1.17

- Menambahkan kartu status Google Analytics 4 pada dashboard statistik admin.
- Measurement ID ditampilkan dalam bentuk tersamarkan; konfigurasi tetap lokal dan opsional.
- Memperjelas pemisahan metrik GA4 dari statistik internal `website_visits`.
- Tidak ada migrasi SQL dan tidak ada impor data GA4.

## v3.1.16

- Baseline stabil setelah pemulihan database online.
- Statistik internal Kandaga (`website_visits`) dipisahkan tegas dari metrik Google Analytics 4.
- Tidak ada impor ulang histori kunjungan atau penggabungan angka GA4 ke tabel internal.
- Konfigurasi database, GA4, gambar, dan upload server dipertahankan oleh mekanisme update.
- Tidak ada migrasi SQL baru; rilis ini berfokus pada konsistensi kode dan metadata versi.

## v3.1.15

- Navbar Bibliografi menjadi dropdown Monograf dan Katalog dengan URL bersih `/monograf` dan `/katalog`; URL PHP lama tetap dialihkan.
- Modul Monograf dan Katalog diselaraskan tanpa hero biru; istilah katalog dan sitasi disederhanakan.
- Naskah Pilihan dapat memakai gambar lokal maupun URL eksternal.
- Naskah Terkait Lainnya dirangking berdasarkan kemiripan judul, lokasi, aksara, bahasa, dan alas tulis.
- Status relasi penelitian hanya menghitung sumber primer/sekunder dan ditampilkan sebagai pie chart dengan tanggal pembaruan katalog.
- Tombol Lapor/Beri Masukan diseragamkan dengan gaya kotak bookmark; kartu statistik populer dan pencarian diseimbangkan.

## v3.1.14

- Arsip Referensi hanya memuat publikasi primer dan sekunder; tab panduan dihapus.
- Tab Katalog menampilkan katalog sumber, jumlah manuskrip terkait, dan halaman daftar manuskrip per katalog.
- Modal relasi membalik narasi secara benar: sitasi bibliografi berada di atas, manuskrip yang dirujuk berada di bawah.
- Relasi manuskrip menampilkan thumbnail, judul, kode inventaris, lokasi penyimpanan, dan tautan detail.

## v3.1.13

- Menambahkan navigasi balik dari bibliografi primer dan sekunder ke naskah yang terkait.
- Tombol modal menampilkan judul, kode inventaris, dan tautan ke detail naskah.
- Sumber berkategori `katalog` tetap diberi penanda khusus; perlakuan reverse relation-nya akan dikembangkan terpisah.
- Memperbaiki tampilan bibliografi agar relasi sumber–naskah lebih mudah dipahami di desktop dan mobile.
- Riwayat rilis lama mulai v3.1.2 hingga v3.1.12 dipertahankan dalam dokumen ini.

## v3.1.12

- Integrasi Google Analytics 4 opsional melalui konfigurasi lokal.
- Card status penelitian berdasarkan relasi `naskah_bibliografi`.
- Tanggal pembaruan katalog terakhir.
- Ringkasan kunjungan publik dipusatkan di footer.
- Ikon sosial dipindahkan ke kolom Kontak Kami dan tipografi footer diselaraskan.

## v3.1.11

- Statistik footer harian, mingguan, bulanan, total, dan pengunjung online.
- Dashboard analitik dengan garis tren, kartu halaman populer, dan perbaikan geo-IP.
- Sidebar Goah dapat digulir sampai menu terakhir.

## v3.1.10

- Pusat Pembaruan dengan upload paket, pratinjau, checksum, backup, maintenance, migrasi, dan pemulihan.
- Feedback Naskah dan importer katalog cetak.

## v3.1.9

- Feedback Naskah untuk pembaca dan administrator.

## v3.1.8

- Dasar importer katalog dan moderasi entri.

## v3.1.7

- Importer metadata dengan staging dan pemeriksaan charset.

## v3.1.6

- Perbaikan paket importer dan validasi lokal.

## v3.1.5

- Penyempurnaan statistik, dashboard, dan tampilan responsif.

## v3.1.4

- Penyelarasan tabel Goah, statistik, dan navigasi katalog.

## v3.1.3

- Analitik halaman publik, pencarian, perangkat, negara, dan kota.

## v3.1.2

- Mode Search Thumbnail/List dan pilihan 15/50/100 entri per halaman.
- Font modul, bookmark anonim, statistik katalog, dan timeline kontributor.
## v3.1.19
- Menyamakan format konfigurasi GA4 lokal dan online.
- Menambahkan panel grafik Google Analytics 4 berbasis Data API di Statistik Goah.
- Analitik GA4 tetap terpisah dari `website_visits`; tidak ada migrasi SQL.

# v3.1.34 — Security hardening

- Menutup endpoint debug/test yang dapat membocorkan informasi sistem.
- Menambahkan autentikasi langsung pada halaman admin yang sebelumnya dapat dipanggil sebagai fragment.
- API import tidak lagi menyimpan secret di source code; ditambah rate limit dan batas payload.
- Menambah header keamanan, strict session mode, throttling login, sanitizer konten artikel, serta validasi proxy PDF.
- Menonaktifkan display error pada deployment produksi.

# v3.1.43 — Optimasi analitik dan crawler

- Menghentikan penyimpanan detail crawler baru pada `website_visits`.
- Memindahkan audit crawler ke access log server.
- Menghapus rincian request mentah dari panel admin.
- Mempertahankan statistik ringkas trafik manusia dan Google Analytics 4.
- Tidak ada migrasi database dan tidak ada penghapusan data historis.

# v3.1.44 — Retensi analitik otomatis

- Menambahkan ringkasan statistik harian.
- Menghapus log crawler lama secara bertahap setelah tujuh hari.
- Mempertahankan ringkasan trafik manusia saat log mentah berusia lebih dari 90 hari dibersihkan.
- Tidak mengubah data katalog atau akun.

## 3.1.46 — 23 Oktober 2026

- Card pencarian lebih bersih: ikon view dihapus dan bookmark diberi latar kontras.
- Carousel kontributor menampilkan empat item per siklus dan responsif.
- Disclaimer diperbarui dan disederhanakan.
- Detail naskah memiliki Open Graph/Twitter Card dinamis dengan cover naskah.

# v3.1.46 — Penyempurnaan tampilan dan analitik

- Pengunjung online footer hanya menghitung token manusia dengan sedikitnya dua pageview dalam 10 menit dan aktivitas maksimal tiga menit terakhir.
- Placeholder hanya disembunyikan pada foto utama metadata; naskah terkait tetap mempertahankan fallback placeholder.
- Card/list pencarian tidak lagi menampilkan ikon mata/view; bookmark tetap menjadi aksi utama.
- Tombol bookmark dibuat persegi panjang dengan latar dan hover yang selalu terbaca.
- Menghilangkan pesan sukses bookmark yang mengganggu tampilan.

# v3.1.47 — Peningkatan SEO katalog

- Menambahkan metadata SEO dinamis untuk halaman detail naskah dan beranda.
- Menambahkan canonical URL, meta description, Open Graph, Twitter Card, dan JSON-LD.
- Menambahkan sitemap XML dinamis untuk URL publik dan detail naskah.
- Mempertahankan hasil pencarian sebagai `noindex,follow` tanpa memblokir crawler melalui `robots.txt`.
- Tidak mengubah struktur database maupun data katalog.

