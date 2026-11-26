# Kandaga v3.1.65

## v3.1.65 — Tahap 3 hardening dan stabilisasi operasional

- Menambahkan pemeriksaan same-origin pada request POST yang sudah melewati autentikasi dan CSRF.
- Merotasi token CSRF setelah login, memperketat validasi tipe token, dan menegaskan session cookie-only dengan HttpOnly/SameSite Lax.
- Menghapus cookie sesi saat logout menggunakan atribut keamanan yang konsisten.
- Memastikan hasil upload gambar setelah dipindahkan tetap berupa file media non-eksekutabel dengan permission 0644.
- Menambahkan heartbeat `last_activity_at` pada job dan `maintenance.lock` untuk membedakan proses aktif dari maintenance yang kehilangan heartbeat.
- Menambahkan peringatan Update Center ketika tidak ada heartbeat selama lima menit, tanpa pembersihan otomatis.
- Menambahkan `credentials: same-origin`, `cache: no-store`, dan jeda polling yang lebih wajar pada Update Center.

### KRIR — ruang lingkup dan dampak

- **File berubah:** `config/bootstrap.php`, `admin/includes/request.php`, `actions/proses-login.php`, `admin/logout.php`, `admin/includes/upload.php`, `admin/includes/update-engine.php`, `admin/update.php`, `admin/assets/js/update-center.js`, `version.json`, `CHANGELOG.md`, `RELEASE.md`, `VALIDASI.md`, dan `update-manifest.json`.
- **SQL/database:** tidak ada migrasi, perubahan tabel, query skema, atau backup database. Paket tetap code-only dengan backup kode saja.
- **Fitur dipertahankan:** katalog, detail naskah, pencarian, bookmark, bibliografi, importer, SMTP/korespondensi, statistik katalog, SEO, Zotero server-side, dan layout desktop/mobile.
- **Dampak yang mungkin terlihat:** request POST lintas origin dengan token yang salah akan ditolak; ini tidak mengubah form normal dari domain Kandaga. Update Center menampilkan peringatan heartbeat jika browser/worker berhenti, tetapi tidak menghapus job, maintenance, atau unggahan.
- **Kompatibilitas:** PHP >= 8.1, MySQL >= 8.0, MariaDB >= 10.11, dan ekstensi rilis sebelumnya. Tidak ada dependency frontend baru atau perubahan struktur database.
- **Rollback:** pulihkan file kode ke v3.1.64. Tidak ada migrasi atau data database yang perlu dibatalkan.

### Pemeriksaan KRIR sebelum deployment

1. Uji login gagal/berhasil, rotasi sesi, CSRF, logout, dan akses role admin/kontributor.
2. Uji seluruh form POST publik/admin dari domain Kandaga; pastikan Origin normal diterima dan Origin berbeda ditolak.
3. Uji upload gambar naskah, profil, lokasi, bibliografi, dan kabar; pastikan gambar tetap tampil dan tidak dapat dieksekusi sebagai skrip.
4. Uji Update Center dengan paket code-only: preview, backup kode, install, reload browser, resume, heartbeat, dan penyelesaian maintenance.
5. Uji halaman publik, admin, bookmark, importer, SMTP/Inbox, statistik katalog, Zotero tanpa key, SEO, serta desktop/mobile.
6. Periksa CSP Report-Only dan akses metadata internal seperti pada validasi v3.1.64.
7. Pastikan tidak ada migrasi SQL, perubahan tabel, backup database, atau perubahan data katalog.

# Kandaga v3.1.64

## v3.1.64 — Tahap 2 hardening keamanan berisiko rendah

- Menambahkan header Content-Security-Policy-Report-Only terpusat melalui config/bootstrap.php. Mode laporan sengaja tidak memblokir inline script, viewer, bookmark, SMTP, telemetri, atau aset eksternal yang sedang dipakai.
- Memperluas aturan root .htaccess untuk menolak akses langsung ke tools/, migrations/, import-examples/, metadata rilis, manifest lokal, dan skrip diagnostik.
- Menambahkan assets/.htaccess agar file PHP, PHTML, PHAR, CGI, Perl, Python, shell, ASP, dan ASPX di folder aset/upload tidak dapat dieksekusi.
- Menetapkan permission file media hasil upload ke 0644 setelah validasi MIME dan struktur gambar.
- Menampilkan peringatan diagnostik jika maintenance aktif lebih dari 30 menit atau ada unggahan paket yang tertunda lebih dari satu jam. Tidak ada pembersihan otomatis pada tahap ini.

### KRIR — ruang lingkup dan dampak

- **File yang berubah:** .htaccess, robots.txt, assets/.htaccess, config/bootstrap.php, admin/includes/upload.php, admin/includes/update-engine.php, admin/update.php, admin/assets/js/update-center.js, version.json, CHANGELOG.md, RELEASE.md, dan update-manifest.json.
- **SQL/database:** tidak ada migrasi, perubahan tabel, query skema, atau backup database. Paket ditetapkan sebagai code-only.
- **Fitur yang dipertahankan:** katalog, detail naskah, pencarian, bookmark, bibliografi, importer, SMTP/korespondensi, statistik katalog, SEO, Zotero server-side, serta layout desktop/mobile.
- **Dampak yang mungkin terlihat:** browser dapat menampilkan laporan CSP di console karena kebijakan masih Report-Only; halaman tidak diblokir. Akses langsung ke metadata rilis, contoh impor, tools, dan migrasi kini ditolak.
- **Kompatibilitas:** PHP >= 8.1; tidak ada perubahan dependency frontend atau struktur database. Aturan upload hanya memblokir eksekusi skrip, bukan penyajian gambar/media.
- **Rollback:** pulihkan file kode dari v3.1.63. Tidak ada migrasi atau data database yang perlu dibatalkan.

### Pemeriksaan KRIR sebelum deployment

1. Uji halaman publik, admin, login, bookmark, modal, SMTP, importer, dan halaman Referensi saat key Zotero belum tersedia.
2. Uji upload gambar naskah, profil, lokasi, dan thumbnail kabar.
3. Periksa header Content-Security-Policy-Report-Only dengan curl -I.
4. Pastikan akses config/, includes/, tools/, migrations/, import-examples/, .sql, .zip, version.json, dan update-manifest.json ditolak.
5. Pastikan admin/actions/ serta proses Update Center tetap dapat dipanggil oleh sesi admin.
6. Pastikan diagnostik maintenance hanya memberi informasi dan tidak menghapus unggahan atau job.

# Kandaga v3.1.63

## v3.1.63 — Tahap 1 baseline keamanan

- Menghapus kredensial API Zotero dari source code. Konfigurasikan `KANDAGA_ZOTERO_API_KEY` pada environment server setelah key lama dicabut/dirotasi pada Tahap 0; `KANDAGA_ZOTERO_USER_ID` opsional dan default-nya tetap kompatibel dengan instalasi lama.
- Menambahkan timeout koneksi dan request, TLS certificate verification, HTTPS-only redirect policy, User-Agent, validasi HTTP/JSON, cache 60 detik di temporary directory, dan escaping judul/penulis dari Zotero.
- Memperkuat `.htaccess` agar direktori konfigurasi, helper internal, engine impor, metadata repositori, file environment, dan artefak deployment tidak disajikan langsung.
- Menyamakan autentikasi halaman yang sering dimuat AJAX dengan akses langsung: form naskah, form edit naskah, form kabar, dan pendaftaran pengguna sekarang memiliki guard eksplisit.
- Menyamarkan detail exception API import dari klien; rincian teknis tetap tersedia pada error log server untuk diagnosis.

### KRIR — ruang lingkup dan dampak

- **File yang berubah:** `pages/referensi.php`, `admin/actions/api-import.php`, empat halaman form admin, `.htaccess`, `includes/.htaccess`, serta guard `.htaccess` baru pada `config`, `admin/includes`, dan `admin/engines`.
- **SQL/database:** tidak ada migrasi, query skema, atau perubahan data. Backup database tidak diperlukan; paket ini ditetapkan code-only.
- **Fitur yang dipertahankan:** katalog dan importer, metadata serta bibliografi, SMTP/korespondensi, bookmark, statistik katalog, SEO, serta seluruh layout publik/admin v3.1.62.
- **Kompatibilitas:** PHP >= 8.1 dan ekstensi yang sudah diwajibkan v3.1.62. Jika API Zotero tidak digunakan atau environment key belum diisi, halaman Referensi menampilkan pesan konfigurasi yang aman, bukan error internal.
- **Dampak yang mungkin terlihat:** halaman Referensi tidak lagi mengambil data ketika `KANDAGA_ZOTERO_API_KEY` belum diatur; ini disengaja untuk mencegah kredensial kembali masuk ke source code.
- **Rollback:** pulihkan berkas kode v3.1.62. Tidak ada migrasi atau perubahan database yang perlu dibatalkan.

### Konfigurasi server sebelum membuka Referensi

1. Cabut/rotasi API key Zotero lama dari Tahap 0.
2. Set environment `KANDAGA_ZOTERO_API_KEY` pada PHP-FPM/LSAPI/hosting panel, bukan pada ZIP atau database.
3. Opsional, set `KANDAGA_ZOTERO_USER_ID`; jika tidak, nilai kompatibel lama tetap digunakan.
4. Restart/reload worker PHP sesuai mekanisme hosting, lalu uji `/referensi` dan respons tanpa key.

# Kandaga v3.1.62

## v3.1.62 — Akses digital ringkas dan halaman bibliografi mobile

- Mengubah indikator PDF/IIIF pada card pencarian menjadi ikon inline non-interaktif di samping metadata alas tulis; link akses tetap tersedia pada halaman detail naskah.
- Menghilangkan label, kotak, border, dan blok terpisah pada indikator akses digital search; kode inventaris dapat membungkus tanpa dipotong.
- Memperluas fondasi layout mobile ke Statistik, Tentang, Kontributor, Detail Kontributor, Disclaimer, Indeks Judul, dan Bibliografi Monograf.
- Menata ulang daftar Bibliografi Monograf agar sitasi, cover, tombol, DOI, dan URL tidak saling menekan atau terpotong pada layar sempit.
- Memperbaiki modal “Lihat naskah terkait”: tinggi mengikuti viewport mobile, header memakai warna tema Kandaga, cover ditampilkan jika tersedia, dan sitasi otomatis ditampilkan dalam format APSA tanpa label gaya tambahan.
- Menambahkan fallback cover yang aman ketika gambar tidak tersedia atau gagal dimuat.
- Membatasi Indeks Judul menjadi 40 judul unik per halaman, menambahkan navigasi Sebelumnya/Berikutnya, pagination ringkas, validasi nomor halaman, dan informasi rentang hasil.
- Menambahkan stylesheet indeks judul serta stylesheet shared content yang sebelumnya dirujuk tetapi belum tersedia dalam paket.
- Tidak mengubah SQL, migrasi, statistik kunjungan, importer, SMTP, bookmark, atau logika Naskah Pilihan.
- Rilis code-only; backup database dinonaktifkan.

### Dampak dan rollback (KRIR)

- Target kode: card search, `digital-access.php`, layout mobile halaman khusus, Bibliografi Monograf, modal relasi, Indeks Judul, dan stylesheet publik.
- Risiko rendah: indikator search bersifat visual saja; link digital detail tidak berubah. Perubahan mobile dibatasi class halaman dan breakpoint.
- Backup yang disarankan: backup berkas kode yang berubah; tidak perlu backup SQL.
- Rollback: pulihkan berkas dari v3.1.61 atau pasang kembali paket v3.1.61; tidak ada migrasi yang perlu dibatalkan.

# Kandaga v3.1.61

## v3.1.61 — Fondasi layout mobile dan akses digital search

- Menambahkan fondasi layout mobile bersama dengan gutter tunggal, wrapper halaman, dan aturan embedded module agar container tidak bertumpuk.
- Merapikan ruang mobile homepage dengan mengubah wrapper internal Naskah Pilihan dan Kontributor tanpa mengubah logika data atau algoritma kurasi.
- Mengurangi padding bertingkat pada Goah dan menghapus padding global yang memaksa semua card memiliki ruang tambahan.
- Memperbaiki alignment justified pada Riwayat Naskah, Deskripsi Fisik, dan Deskripsi Isi; stylesheet detail kini memakai cache-busting versi dinamis.
- Menambahkan indikator akses digital PDF dan IIIF pada card hasil pencarian dalam mode thumbnail maupun list.
- Indikator hanya muncul untuk URL HTTP(S) yang valid, dapat dibuka, memiliki label aksesibel, dan tidak bergantung pada ikon PNG IIIF.
- Tidak mengubah SQL, migrasi, statistik, importer, SMTP, bookmark, atau logika Naskah Pilihan.
- Rilis code-only; backup database dinonaktifkan.

### Dampak dan rollback (KRIR)

- Target kode: fondasi CSS mobile, wrapper homepage, Goah mobile shell, detail naskah, search card, dan helper akses digital.
- Risiko rendah: perubahan layout dibatasi breakpoint mobile; indikator akses membaca field katalog yang sudah ada.
- Backup yang disarankan: backup berkas kode yang berubah; tidak perlu backup SQL.
- Rollback: pulihkan berkas dari v3.1.60 atau pasang kembali paket v3.1.60; tidak ada migrasi yang perlu dibatalkan.

# Kandaga v3.1.60

## v3.1.60 — KRIR: gambar detail, editor naskah, dan artikel mobile

- Membatasi ukuran bingkai serta gambar utama pada detail naskah dengan aturan desktop dan mobile yang terpisah; gambar tetap terpusat dan rasio aslinya dipertahankan.
- Menjustifikasi isi deskripsi metadata detail, termasuk paragraf dan elemen HTML yang berasal dari editor.
- Menormalkan entitas HTML legacy pada tiga textarea TinyMCE di form edit naskah tanpa menulis ulang data di database.
- Menambahkan lapisan responsif khusus halaman artikel/kabar untuk mengurangi frame bertumpuk pada layar mobile, menjaga konten media tidak meluber, dan memadatkan kartu daftar kabar.
- Menghilangkan nested `.container` pada modul **Kabar Terbaru** agar padding homepage tidak berlapis; scope tidak menyentuh `Naskah Pilihan`.
- Tidak mengubah SQL, migrasi, struktur database, SMTP, statistik, bookmark, atau perilaku desktop yang sudah ada.
- Rilis code-only; backup database dinonaktifkan.

### Dampak dan rollback (KRIR)

- Target kode: `assets/css/style-detail-naskah.css`, `assets/css/style-kabar-responsive.css`, `admin/form-edit.php`, `pages/post.php`, `pages/daftar-post.php`, `index.php`, dan `includes/post.php`.
- Risiko rendah: CSS baru aktif terutama pada breakpoint mobile dan helper editor hanya memengaruhi nilai textarea rich-text saat edit.
- Backup yang disarankan: backup berkas kode yang tercantum; tidak perlu backup SQL.
- Rollback: pulihkan berkas kode dari backup atau pasang kembali paket v3.1.59; tidak ada migrasi yang perlu dibatalkan.

# Kandaga v3.1.59

## v3.1.59 — Kompatibilitas tampilan gambar detail

- Memulihkan tampilan gambar utama pada halaman single naskah dengan posisi terpusat, rasio asli, dan batas ukuran desktop/mobile yang terpisah.
- Gambar utama single hanya memakai file lokal atau URL eksternal yang valid; placeholder tidak digunakan dan gambar gagal dimuat tidak diganti placeholder.
- Memulihkan hover kartu **Naskah Terkait** tanpa mengubah jumlah, ranking, atau sumber gambarnya.
- Menambahkan scope `.manuscript-detail-page` agar CSS tidak memengaruhi `Naskah Pilihan`, pencarian, halaman publik lain, atau Goah.
- Tidak mengubah `includes/naskah-pilihan.php`, database, migrasi SQL, maupun perilaku fitur v3.1.58 lainnya.
- Rilis code-only tanpa backup database.

# Kandaga v3.1.58

## v3.1.58 — Statistik katalog dan formulir naskah

- Menyelaraskan status relasi naskah dengan penelitian menggunakan perhitungan naskah unik berbasis `EXISTS`; kategori bibliografi dinormalisasi terhadap variasi huruf besar dan spasi.
- Menyamakan tinggi kartu **Naskah Terpopuler** dan **10 kata kunci pencarian teratas** pada halaman Statistik.
- Memperbaiki format daftar aksara, bahasa, dan alas tulis menjadi `A, B, & C` untuk tiga item atau lebih.
- Menambahkan layout detail manuskrip satu kolom pada layar mobile, dengan nilai metadata berada di bawah label.
- Menyamakan ukuran ikon sidebar Goah saat mode terlipat dan menambahkan tooltip yang dapat diakses melalui mouse serta keyboard.
- Membolehkan kode inventaris kosong pada formulir input dan edit naskah karena koleksi perorangan dapat tidak memiliki shelfmark.
- Mengaktifkan pencarian Select2 pada dropdown Lembaga Penyimpan dengan placeholder yang lebih jelas.
- Tidak menambah migrasi SQL atau backup database; paket ini bersifat code-only.

# Kandaga v3.1.57

## v3.1.57 — Konsistensi tipografi dan korespondensi

- Menambahkan fondasi tipografi global untuk admin, kontributor, dan halaman publik dengan Source Sans Pro sebagai stack UI utama.
- Menyatukan ukuran teks, bobot, line-height, warna, serta font monospace melalui token CSS terpusat.
- Menambahkan stylesheet responsif bersama yang sebelumnya dirujuk modul tetapi belum tersedia dalam paket.
- Menambahkan lapisan konsistensi Goah untuk heading, label, input, tabel, tombol, sidebar, dashboard, dan metadata teknis.
- Menyelaraskan tampilan Inbox Feedback dan Feedback Naskah menggunakan topbar, daftar pesan, thread, form balasan, tombol aksi, dan breakpoint mobile yang sama.
- Menghapus CSS inline khusus korespondensi dari halaman Inbox dan menggunakan cache-busting versi dinamis.
- Menambahkan alias kompatibilitas untuk kelas Bootstrap 5 yang muncul pada template lama tanpa mengubah fungsi modul.
- Tidak ada migrasi SQL, perubahan struktur database, atau backup database.

## Catatan deployment

Paket ini adalah pembaruan code-only dari v3.1.56. SMTP, thread korespondensi, statistik katalog, dan data pengguna tidak diubah.

# Kandaga v3.1.56

## v3.1.56 — Balasan SMTP Inbox Feedback Kontak

- Menambahkan balasan langsung dari **Inbox Feedback** menggunakan SMTP yang sama dengan Feedback Naskah.
- Menambahkan thread dan riwayat balasan terpisah melalui tabel lazy `kandaga_inbox_threads` dan `kandaga_inbox_replies`.
- Menambahkan status thread terbuka, sudah dibalas, dan ditutup.
- Menambahkan idempotensi agar klik ganda tidak mengirim balasan yang sama dua kali.
- Memperkuat validasi form kontak: CSRF, UTF-8, batas panjang, email, dan perlindungan header.
- Menambahkan pencarian, filter status, dan pagination Inbox agar tidak memuat seluruh pesan sekaligus.
- SMTP tetap memakai konfigurasi v3.1.55; password tetap berada di file rahasia lokal server.
- Code-only tanpa migrasi SQL dan tanpa backup database.

Paket ini diperbarui dari v3.1.55.

# Kandaga v3.1.55

## v3.1.55 — Korespondensi feedback dan Pengaturan Email

- Menambahkan thread feedback di panel admin: riwayat balasan, status buka/tutup, dan idempotensi pengiriman.
- Menambahkan menu **Pengaturan** dengan halaman **Email & Notifikasi** untuk konfigurasi SMTP mailbox `katalog@kairaga.com`.
- SMTP tetap nonaktif setelah disimpan dan baru aktif setelah email uji berhasil; password disimpan di file rahasia lokal server, bukan database atau paket ZIP.
- Menambahkan pengiriman SMTP dependency-free dengan TLS/SSL, validasi header, timeout, dan pesan error yang tidak membocorkan kredensial.
- Korespondensi tidak mengubah `tb_naskah_feedback`; tabel thread dan balasan dibuat lazy saat modul dipakai.
- Paket code-only tanpa migrasi SQL dan tanpa backup database.

Paket ini diperbarui dari v3.1.54. Isi SMTP di Pengaturan, simpan, lalu kirim email uji untuk mengaktifkan balasan.

# Kandaga v3.1.54

## v3.1.54 — Statistik katalog tanpa log trafik mentah

- Menghentikan penulisan dan pembacaan aktif tabel `website_visits` serta `website_presence` pada jalur publik dan panel admin.
- Menambahkan agregat ringan `kandaga_page_daily` dan `kandaga_search_daily` yang dibuat secara lazy oleh aplikasi; tidak ada migrasi SQL dan tidak ada backup database.
- Mempertahankan `naskah_views`, statistik metadata katalog, relasi penelitian, halaman populer, dan pencarian teratas.
- Footer tetap menampilkan empat ringkasan: Hari ini, 7 hari terakhir, Bulan ini, dan Total tampilan; indikator Pengunjung Online dihapus.
- Panel admin menjadi Statistik Katalog: tanpa IP, geolokasi, user-agent, perangkat, crawler, atau daftar pengunjung unik.
- Google Analytics 4 tetap opsional sebagai telemetri eksternal dan tidak dicampur dengan statistik internal Kandaga.

Paket ini diperbarui dari v3.1.53. Backup database dinonaktifkan; updater hanya melakukan backup kode.

# Kandaga v3.1.53

## v3.1.53 — UI statistik, bookmark, dan bibliografi responsif

- Footer kembali menampilkan **Hari ini**, **7 hari terakhir**, **Bulan ini**, dan **Total**.
- Label indikator aktif tetap ringkas sebagai **Pengunjung Online**.
- Nilai statistik memakai cache counter yang sudah ada; tidak menambah tabel atau migrasi SQL.
- Tombol bookmark pada kartu thumbnail pencarian berubah menjadi ikon bookmark outline dengan tanda tambah di tengah, tanpa kotak atau lingkaran latar.
- Ikon bookmark yang tersimpan berubah menjadi biru dan tetap memiliki label aksesibilitas untuk pembaca layar.
- Kartu status relasi naskah dengan penelitian diringkas menjadi KPI, progress bar, legenda jumlah, dan modal rincian.
- Modal naskah terkait pada halaman bibliografi menjadi responsif pada layar mobile, dapat digulir, dan menata tombol detail secara vertikal.
- Menambahkan stylesheet bibliografi publik yang hilang dari paket sebelumnya agar aturan responsif ikut terpasang.
- Ikon memakai Font Awesome dan CSS, sehingga tidak memerlukan PNG/SVG tambahan.
- Tampilan tombol detail tetap mempertahankan label teks agar aksesibilitas dan konteks tetap jelas.

Paket ini diperbarui dari v3.1.52. Backup database tetap dinonaktifkan; updater hanya membuat backup kode.

---

# Kandaga v3.1.52

## v3.1.52 — Event kunjungan ramping dan update code-only

- Jalur pencatatan modern `website_visits` hanya menulis field inti yang dipakai untuk counter, pencarian, dan klasifikasi trafik.
- Kolom diagnostik lama seperti IP, user-agent, referrer, dan hash permintaan tetap dipertahankan nullable demi kompatibilitas, tetapi tidak lagi diisi pada event baru.
- Crawler tidak membuat baris `website_visits`; aktivitas crawler tetap dapat diperiksa dari access log server.
- Footer, presence, rollup harian, dan pembersihan otomatis tetap memakai algoritma rilis sebelumnya.
- Updater rilis ini memaksa mode **code-only** dan tidak memulai, memilih, atau menyalin backup database.
- Tidak ada migrasi SQL; paket aman dipasang di atas v3.1.51 tanpa perubahan destruktif pada tabel.

Paket ini diperbarui dari v3.1.51. Ikuti alur Pusat Pembaruan; backup kode tetap dilakukan sebagai perlindungan rollback.

---

# Kandaga v3.1.51

## v3.1.51 — Counter online ringkas dan kurasi gambar landing

- Footer frontend hanya menampilkan indikator **Pengunjung online** berbasis presence heartbeat terverifikasi; KPI pageview tidak lagi dimuat pada setiap halaman.
- Bagian **Naskah Pilihan** hanya memilih naskah dengan berkas upload yang benar-benar tersedia atau URL gambar HTTP/HTTPS yang valid.
- Nilai placeholder dan berkas upload yang sudah hilang dilewati, sehingga placeholder tidak muncul sebagai rekomendasi pilihan.
- URL gambar eksternal yang gagal dimuat tidak diganti menjadi placeholder pada kartu pilihan; kartu tersebut disembunyikan agar landing page tetap bersih.
- Tidak ada perubahan tabel atau migrasi SQL. Rencana counter event/rollup terverifikasi tetap dapat diterapkan pada rilis analitik berikutnya tanpa menghapus `website_visits`.

Paket ini diperbarui dari v3.1.50. Tidak diperlukan tindakan manual selain mengikuti alur Pusat Pembaruan.

---

# Kandaga v3.1.50

## v3.1.50 — Statistik admin ringkas dan efisien

- Panel statistik admin kini berfokus pada KPI, grafik harian ringan, dan tiga kelompok rincian yang dibuka melalui modal.
- Integrasi Google Analytics tidak lagi dipanggil atau ditampilkan dari panel statistik admin. Tag telemetri GA4 publik tetap opsional dan tidak dicampur dengan `website_visits`.
- Rincian geolokasi dan IP dihapus dari antarmuka statistik. Jalur pencatatan modern tidak lagi menulis IP/user-agent baru; tabel lama dipertahankan untuk kompatibilitas dan tidak dihapus otomatis.
- Penghitung dan seri harian memakai cache server singkat. Endpoint rincian memakai cache, prepared statement, batas 90 hari, dan hanya memuat data saat diminta.
- Migrasi `017_statistics_performance` menambahkan indeks komposit secara idempoten tanpa menghapus katalog, log kunjungan, atau cache geolokasi.

Paket ini diperbarui dari v3.1.49. Migrasi 017 bersifat aditif dan idempoten. Tidak diperlukan tindakan manual selain mengikuti alur Pusat Pembaruan.

---

# Kandaga v3.1.49

## v3.1.49 — Konsistensi analitik dan presence dinamis

- Heartbeat pengunjung dipindahkan ke footer bersama sehingga halaman beranda tidak lagi kehilangan verifikasi browser.
- Footer memperbarui angka **Aktif 5 menit terakhir** secara dinamis setelah heartbeat, tanpa menghitung reload sebagai pengunjung baru.
- Endpoint heartbeat mengembalikan jumlah aktif terverifikasi untuk memperbarui tampilan tanpa memuat ulang halaman.
- Batas aktif diseragamkan menjadi lima menit; metrik ini merupakan estimasi browser aktif, bukan jumlah koneksi permanen.
- Pencatatan popularitas detail naskah memakai classifier crawler yang sama dengan statistik website.
- Sidik pencarian mempertimbangkan kata kunci dan filter lokasi, aksara, bahasa, serta alas tulis.
- Token presence yang tidak aktif dibersihkan bertahap; indeks heartbeat ditambahkan melalui migrasi `016_presence_maintenance`.
- Panel admin membedakan total rollup historis dari rincian log mentah dalam periode retensi.

Migrasi `016_presence_maintenance` bersifat aditif dan idempoten. Tidak ada katalog, akun, atau riwayat kunjungan yang dihapus oleh pemasangan migrasi. Pembersihan token presence berjalan bertahap maksimal 5.000 baris per siklus.

Catatan: angka total pageview tetap mengikuti perilaku pageview, sehingga reload dapat menambah tampilan. Pengunjung unik dan indikator aktif memakai identitas browser anonim dengan batasan cookie/perangkat yang lazim pada analitik web.

---

# Kandaga v3.1.48

## v3.1.48 — Rekonsiliasi statistik pengunjung

- Memperbaiki penghitungan pageview harian, mingguan, bulanan, dan total agar tidak menggandakan data raw dengan ringkasan harian.
- Pengunjung unik lintas periode dihitung dari identitas visitor-day yang dideduplikasi, bukan penjumlahan `unique_visitors` per hari.
- Menambahkan heartbeat browser dan presence terverifikasi untuk indikator “Sedang online”.
- Reload berulang tidak lagi menaikkan jumlah pengunjung online; crawler tanpa JavaScript tidak masuk presence terverifikasi.
- Panel admin memakai rangkaian data harian gabungan raw/rollup dan batas waktu Asia/Jakarta.
- Migrasi `015_analytics_consistency` bersifat aditif dan idempoten. Rekonsiliasi data lama berjalan bertahap.

Tidak ada penghapusan katalog atau log mentah saat pemasangan migrasi.

---

## v3.1.47 — SEO metadata dan sitemap

## v3.1.47 — SEO metadata dan sitemap

- Metadata halaman detail naskah dibangun dari judul, deskripsi, kode inventaris, lokasi, bahasa, dan foto yang tersedia.
- Menambahkan `description`, `canonical`, Open Graph, Twitter Card, JSON-LD `CreativeWork`, dan `BreadcrumbList`.
- Beranda memperoleh JSON-LD `WebSite` dengan deklarasi pencarian.
- Hasil pencarian tetap `noindex,follow` agar tidak menghasilkan ribuan URL duplikat, tetapi tetap dapat diikuti crawler.
- Menambahkan `/sitemap.xml` yang memuat halaman publik dan seluruh detail naskah.
- Tidak ada migrasi SQL.

---

## v3.1.46 — Penyempurnaan tampilan publik dan analitik

- Menggunakan basis kode lengkap yang kompatibel dengan instalasi v3.1.45.
- Memperketat indikator pengunjung online agar hanya menghitung manusia dengan aktivitas lanjutan.
- Placeholder hanya disembunyikan pada foto utama metadata; naskah terkait tetap menampilkan fallback.
- Menghapus ikon view dari card/list pencarian dan memperbaiki tombol bookmark.
- Tidak ada migrasi SQL.

---

## v3.1.45 — Pembaruan dapat dilanjutkan otomatis

- Pekerjaan update aktif otomatis ditemukan saat admin membuka kembali pusat pembaruan.
- Proses melanjutkan dari tahap dan cursor terakhir yang tersimpan di server.
- Penutupan browser atau putus koneksi tidak lagi mengharuskan unggah ulang paket.
- Browser berfungsi sebagai pemantau; status maintenance dan backup tetap dikelola server.
- Tombol lanjut manual tetap tersedia sebagai fallback bila auto-resume tidak dapat dijalankan.

## Catatan deployment

Pembaruan ini memperbaiki pengalaman melanjutkan pekerjaan update berikutnya. Pekerjaan yang sudah berjalan tetap menggunakan runner yang membuatnya; jangan menghapus `job.json`, folder backup, atau `maintenance.lock` secara manual.

# Kandaga v3.1.44

## v3.1.44 — Retensi analitik otomatis

- Menambahkan ringkasan statistik harian dalam `website_visit_daily`.
- Menambahkan kebijakan retensi otomatis: data bot mentah tujuh hari dan data manusia mentah 90 hari secara default.
- Membersihkan data secara bertahap maksimal 5.000 baris per proses agar tidak mengunci tabel terlalu lama.
- Mengarsipkan ringkasan kunjungan manusia sebelum data mentah lama dibersihkan.
- Mempertahankan data katalog, akun, dan statistik ringkasan tanpa perubahan destruktif saat instalasi.
- Proses retensi berjalan aman di latar belakang dan tidak mengganggu request publik jika gagal.

## Catatan deployment

Migrasi `014_analytics_retention` hanya membuat tabel ringkasan dan konfigurasi retensi. Penghapusan data lama berlangsung bertahap setelah aplikasi berjalan, bukan saat paket dipasang.

# Kandaga v3.1.43

## v3.1.43 — Optimasi analitik dan crawler

- Menghentikan penyalinan request crawler baru ke tabel `website_visits`; crawler tetap tercatat pada access log server.
- Mempertahankan presence publik hanya untuk pengunjung manusia.
- Menghapus panel **Rincian kunjungan — hanya administrator** dari analitik aplikasi.
- Mempertahankan ringkasan trafik manusia, pengunjung unik, halaman populer, kata kunci, perangkat, dan lokasi.
- Menjelaskan bahwa Google Analytics 4 dan access log server menjadi sumber analitik eksternal dan audit teknis.
- Data historis `website_visits` tidak dihapus dan tidak diubah.

## Catatan deployment

Pembaruan ini tidak menjalankan migrasi database dan tidak menghapus data lama. Request crawler baru tidak lagi menambah baris pada `website_visits`, sehingga pertumbuhan tabel analitik menjadi jauh lebih lambat.

# Kandaga v3.1.42

## v3.1.42 — Form naskah dan struktur katalog

- Menghubungkan form tambah naskah dengan struktur naskah induk/unit.
- Menghubungkan form edit naskah dengan data struktur yang sudah tersimpan.
- Menambahkan input identifier alternatif dan sumbernya.
- Menambahkan input lokasi asal, lokasi ditemukan, dan lokasi sebelumnya.
- Menyimpan perubahan secara transaksional dan tetap opsional.
- Tidak mengubah importer; integrasi importer disiapkan pada rilis berikutnya.

## Catatan deployment

Paket ini memerlukan tabel tahap 1 dari v3.1.41. Jika tabel belum tersedia, form utama tetap berjalan dan bidang struktur dilewati secara aman.

## v3.1.41 — Struktur data katalog tahap 1

## v3.1.41 — Struktur data katalog tahap 1

- Menambahkan tabel `katalog_structure` untuk relasi naskah induk dan unit.
- Menambahkan tabel `katalog_identifiers` untuk kode atau identifier alternatif.
- Menambahkan tabel `katalog_location_history` untuk lokasi asal dan riwayat lokasi.
- Seluruh tabel bersifat aditif, idempoten, dan tidak mengubah data katalog lama.
- Importer lama belum diubah; dukungan importer akan disiapkan pada rilis berikutnya.

## Catatan deployment

Migrasi `013_catalog_structure_stage1` berjalan otomatis. Tidak ada tabel lama yang dihapus, tidak ada baris katalog yang ditulis ulang, dan tidak ada perubahan pada alur importer v3.1.40.

## v3.1.40 — Visitor Presence & Bibliography Search

## v3.1.40 — Visitor Presence & Bibliography Search

- Memperbaiki penghitung **Pengunjung online** agar reload berulang dari browser yang sama tidak dihitung sebagai pengunjung baru.
- Menambahkan cookie visitor anonim dengan path yang dinormalisasi untuk instalasi domain-root maupun subdirektori.
- Menambahkan tabel presence terpisah; page view tetap dicatat di `website_visits`, sedangkan online dihitung dari heartbeat token unik.
- Crawler tidak dihitung pada footer dan tidak membuat heartbeat presence.
- Menambahkan pencarian pada Daftar Pustaka/Bibliografi admin berdasarkan judul, subjudul, penulis, editor, penerbit, kota, tahun, ISBN/ISSN, dan DOI.
- Pencarian memakai query terparameterisasi, tombol reset, dan jumlah hasil.

## Catatan deployment

Migrasi `012_website_presence` berjalan otomatis dan idempoten. Migrasi tidak menghapus data katalog atau riwayat `website_visits`; hanya membersihkan presence yang lebih lama dari dua hari.

## v3.1.39 — Analytics Accuracy & Bot Reconciliation

- Mereklasifikasi log historis berdasarkan user-agent sehingga kombinasi `unknown_bot` + `Human` diperbaiki.
- Mengubah default nama bot menjadi `Unknown crawler`; label `Human` hanya diberikan pada trafik kelas human.
- Statistik manusia dan penghitung online memakai dua syarat: `is_bot=0` dan `traffic_class=human`.
- Menambahkan peringatan admin untuk log yang tidak konsisten antara kelas trafik dan flag bot.
- Mempertahankan crawler resmi, log audit, dan seluruh data katalog tanpa penghapusan.

## Catatan deployment

Migrasi `011_analytics_reconciliation` berjalan otomatis dan idempoten. Migrasi ini hanya memperbaiki metadata analitik historis berdasarkan user-agent; tidak mengubah URL, metadata naskah, atau isi katalog.

## v3.1.38 — Bot Taxonomy & Crawl Control

- Mengenali Meta External Agent/Fetcher sebagai `social_bot`, serta Google Inspection Tool sebagai `search_bot`.
- Menyimpan nama crawler seperti Googlebot, PetalBot, dan Meta External Agent pada rincian analitik admin.
- Menambahkan panel crawler terdeteksi tanpa memasukkannya ke statistik pengunjung manusia.
- Menambahkan `noindex,follow` pada hasil pencarian dan aturan `robots.txt` untuk mencegah indeksasi URL pencarian dinamis.
- Halaman detail naskah, artikel, katalog, dan bibliografi tetap dapat dirayapi.
- Migrasi 010 bersifat aditif dan idempoten; tidak menghapus log, data katalog, atau memblokir crawler resmi.

## Catatan deployment

Migrasi `010_bot_taxonomy` dijalankan otomatis oleh Pusat Pembaruan. Baris historis yang belum memiliki nama crawler diberi label aman `Human` atau `Unknown crawler`; klasifikasi rinci berlaku untuk permintaan setelah pembaruan.

## v3.1.37 — Analytics Reliability

- Footer publik tetap ringkas dengan Hari ini, 7 hari terakhir, Bulan ini, Total tampilan, dan Sedang online.
- Menambahkan token pengunjung anonim berbasis cookie 90 hari untuk estimasi pengunjung unik.
- Menambahkan klasifikasi human, social_bot, search_bot, dan unknown_bot.
- Memisahkan pageview manusia, sesi browser, pengunjung unik, bot/crawler, dan IP berintensitas tinggi pada panel admin.
- Menambahkan referrer dan fingerprint request untuk audit trafik tanpa menyimpan data tambahan yang tidak perlu.
- Statistik halaman, kata kunci, perangkat, negara, kota, dan grafik harian mengecualikan bot yang terdeteksi.
- Log bot tetap dipertahankan agar dapat diaudit.
- Tidak mengubah atau menghapus data katalog.

## Catatan deployment

Migrasi 009_analytics_reliability dijalankan otomatis oleh Pusat Pembaruan. Data historis sebelum token pengunjung diterapkan tetap dipertahankan; pengunjung unik historis merupakan estimasi berbasis browser_id.

## v3.1.36 — External Image & IIIF Reliability

- Menambahkan resolver gambar terpusat untuk modul naskah, katalog, bookmark, koleksi, dashboard, dan pencarian.
- Menormalkan path URL IIIF, termasuk URL VHMML yang memiliki slash ganda.
- Menambahkan fallback otomatis ke placeholder ketika host gambar eksternal gagal diakses.
- Menambahkan pemeriksaan URL gambar dari form edit naskah pada panel admin.
- Tidak ada migrasi database.

## Catatan deployment

URL eksternal tetap dimuat dari sumber aslinya dan tidak diunduh pada setiap kunjungan. Cache/mirror terjadwal dapat ditambahkan pada rilis berikutnya jika sumber eksternal sering membatasi hotlink.

## v3.1.35 — Content & Upload Hardening

- Menambahkan sanitasi HTML artikel di sisi server sebelum konten disimpan.
- Memperketat upload gambar dengan validasi dimensi dan jumlah piksel maksimum.
- Memvalidasi URL eksternal pada naskah dan bibliografi agar hanya memakai HTTP/HTTPS yang valid.
- Menambahkan perlindungan tambahan untuk alamat jaringan internal dan URL berkredensial.
- Pusat Pembaruan kini menampilkan ringkasan fitur, keamanan, bug fix, dan catatan database setelah update selesai.
- Metadata rilis disimpan dalam riwayat pekerjaan update agar tetap dapat ditinjau kembali.
- Tidak ada migrasi database.

## Catatan deployment

Berkas gambar, konfigurasi koneksi, dan upload lama tetap dilindungi oleh Pusat Pembaruan dan tidak ditimpa.

## Riwayat v3.1.34

## v3.1.34 — Security hardening

- Menghapus endpoint debug sesi dan test-download dari paket produksi.
- Mengharuskan API import memakai `KANDAGA_IMPORT_API_KEY` dari environment serta menambahkan pembatasan request.
- Menyeragamkan autentikasi langsung pada halaman admin dan endpoint pencarian bibliografi.
- Menambahkan baseline security headers, strict session mode, dan HSTS saat HTTPS aktif.
- Menambahkan throttling percobaan login.
- Menyaring HTML artikel sebelum disimpan untuk mengurangi risiko stored XSS.
- Memperketat proxy PDF: HTTPS saja, whitelist domain, timeout, batas 50 MB, dan pemeriksaan PDF.
- Menonaktifkan display error pada halaman produksi dan memperkuat aturan `.htaccess`.
- Tidak ada migrasi database.

## Catatan deployment

Sebelum menggunakan API import, set environment server `KANDAGA_IMPORT_API_KEY` dan rotasi key lama. Jika API tidak digunakan, biarkan variabel tidak diatur sehingga endpoint selalu menolak request.

## Riwayat v3.1.33

## v3.1.33 — Pemulihan cover bibliografi

- Memperluas resolusi cover untuk URL eksternal, path relatif, dan beberapa direktori legacy.
- Memastikan path cover tetap aman dari traversal dan hanya mengembalikan file di bawah direktori aplikasi.
- Memulihkan thumbnail cover pada indeks Monograf, modal relasi, indeks Katalog, dan detail Katalog jika file tersedia.
- Menghapus padding atas tambahan pada indeks Katalog agar container menempel ke navbar seperti Monograf.
- Tidak ada migrasi database.

## Riwayat v3.1.32

## v3.1.32 — Refinement detail katalog dan modal monograf

- Memindahkan metadata katalog ke sidebar kanan dengan perilaku sticky pada desktop.
- Menyamakan latar dan kontainer indeks katalog dengan halaman Monograf.
- Menghapus label berulang “Sumber data katalog” dan “Sumber penelitian”.
- Membatasi cover monograf pada modal relasi menjadi thumbnail berukuran konsisten.
- Layout sidebar berubah menjadi susunan normal pada mobile.
- Tidak ada migrasi database.

## Riwayat v3.1.31

## v3.1.31 — Penyelarasan modul bibliografi

- Menyeragamkan warna latar, header, card, radius, dan bayangan pada halaman Monograf dan Katalog.
- Menghapus tab Katalog yang redundan dari halaman Monograf; navigasi tetap tersedia melalui dropdown navbar.
- Menata detail katalog dalam card responsif dengan cover di kiri dan sitasi APSA di kanan.
- Menambahkan fallback visual ketika cover katalog tidak tersedia.
- Menampilkan cover monograf sebagai thumbnail pada modal manuskrip terkait.
- Tidak ada migrasi database.

## Riwayat v3.1.30

## v3.1.30 — Hotfix fatal error beranda

- Memastikan `includes/version.php` dimuat mandiri oleh `includes/navbar.php` sebelum helper versi dipanggil.
- Memperbaiki halaman beranda yang sebelumnya dapat menghasilkan HTTP 500 karena `kandagaVersion()` belum tersedia.
- Mengurangi risiko regresi serupa pada halaman yang memuat navbar tanpa `module-head.php` lebih dahulu.
- Tidak ada migrasi database.

## Riwayat v3.1.29

## v3.1.29 — Perbaikan thumbnail search mobile

- Memisahkan CSS layout thumbnail dari aturan list mobile lama yang menyebabkan card menyempit dan teks tersusun vertikal.
- Menata ulang card thumbnail mobile secara vertikal: gambar penuh di atas, metadata di bawah, dan tinggi card mengikuti isi.
- Menghapus efek tinggi card berlebih dari `h-100` pada layar kecil.
- Memperbaiki cache-busting CSS dan JavaScript menggunakan nomor versi aplikasi.
- Tidak ada migrasi database.

## Riwayat v3.1.28

## v3.1.28 — Search mobile lebih padat dan terbaca

- Memperbaiki kartu thumbnail mobile agar gambar, judul, kode, dan lokasi tersusun normal di bawah gambar.
- Bookmark dan Detail Naskah tampil sebagai ikon saja di pojok kanan atas dengan label aksesibilitas.
- Default hasil pencarian menjadi 16 item pada mobile (grid dua kolom), sementara desktop tetap 15 item (grid tiga kolom).
- Mengurangi ruang kosong pada modul front-end mobile dan menyeragamkan pembacaan konten naratif dengan perataan justified.
- Tidak ada migrasi database.

## Riwayat sebelumnya

## UX Search Mobile

- Mode Thumbnail pada mobile memakai grid dua kolom dengan gambar sebagai fokus utama.
- Mode List memakai satu kolom padat dengan thumbnail kecil dan metadata ringkas.
- Bookmark dan Detail Naskah dipindahkan ke pojok kanan atas sebagai ikon dengan tooltip/label aksesibilitas.
- Filter mobile dibuka sebagai modal dengan pilihan sementara, tombol Batal, Reset, dan Terapkan.
- Beberapa kategori dapat dikombinasikan sebelum hasil pencarian diterapkan.
- Radius card, modal, tombol, input, dan badge menggunakan token desain global.
- Tidak ada migrasi database.

## Optimasi detail naskah mobile

- Mengurangi padding, jarak antarbaris, dan ruang kosong pada metadata detail naskah.
- Nilai metadata naratif ditampilkan justified pada mobile, sementara URL, kode, tombol, dan elemen teknis tetap rata kiri.
- Margin tabel detail dibuat lebih efisien agar memakai lebar layar ponsel secara maksimal.
- Tombol Lapor/Beri Masukan diseragamkan ukuran dan gaya dengan tombol Bookmark.
- Naskah terkait dibatasi menjadi empat item; algoritma pemeringkatan tetap berjalan di belakang layar.
- Kartu naskah terkait menampilkan gambar, judul, kode inventaris, lokasi, dan tidak lagi menampilkan alasan kesamaan.
- Tidak ada migrasi database.

## Favicon konsisten

- Seluruh halaman Kandaga menggunakan `assets/images/favicon.png` sebagai favicon utama.
- Favicon publik, detail naskah, halaman modul, login, dan Goah tidak lagi memakai `katalog.ico`.

## Detail naskah responsif

- Pada layar mobile, label dan nilai metadata detail naskah ditampilkan vertikal dengan nilai menggunakan lebar penuh.
- Referensi katalog, primer, sekunder, deskripsi fisik, dan deskripsi isi tidak lagi terjepit dalam kolom sempit.
- Lokasi koleksi menjadi blok responsif dengan foto di atas dan metadata lokasi di bawah.
- Layout desktop dua kolom tetap dipertahankan.
- Tidak ada migrasi database.

## Kompatibilitas mobile

- Menambahkan deteksi viewport responsif berbasis `matchMedia`, dengan kelas `is-mobile` dan `is-desktop` untuk komponen yang membutuhkan perilaku berbeda.
- Mengurangi margin kiri-kanan container pada layar kecil agar katalog, bibliografi, form, dan card memanfaatkan lebar layar secara optimal.
- Menyesuaikan modal, tab, tabel, grid katalog, tombol, dan dukungan safe-area perangkat mobile.

## Hak akses bibliografi

- Pengguna berperan kontributor kini dapat membuka daftar pustaka serta form tambah/edit bibliografi.
- Upload cover dan penyimpanan bibliografi tetap dapat dilakukan oleh kontributor yang sudah login.
- Penghapusan entri dan import RIS tetap dibatasi untuk administrator.
- Tidak ada migrasi database.

## Bibliografi dan cover publikasi

- Form tambah/edit bibliografi dipindahkan ke modul halaman terpisah agar lebih mudah digunakan dan mendukung upload cover buku, artikel, atau katalog.
- Cover lokal disimpan pada `assets/images/bibliografi`; URL cover eksternal juga dapat digunakan.
- Daftar bibliografi, katalog, dan relasi pada detail naskah menampilkan thumbnail cover bila tersedia.
- Sitasi APSA dipusatkan pada satu formatter sehingga judul tidak berulang pada popup relasi dan kartu katalog hanya menampilkan sitasi bibliografis.
- Menambahkan migrasi idempoten `008_bibliography_covers` tanpa mengubah tabel inti `tb_bibliografi`.

## Kompatibilitas pembaruan

- Paket ditujukan untuk pembaruan bertahap dari v3.1.22 dan mempertahankan konfigurasi, relasi naskah, serta unggahan yang sudah ada.

## Perbaikan upload thumbnail artikel

- Memperbaiki validasi upload gambar agar tidak menolak ekstensi nama file yang tidak konsisten dengan MIME valid.
- Form artikel membaca batas `upload_max_filesize` server dan menampilkan pesan yang sesuai sebelum pengiriman.
- Respons error upload kini ditampilkan secara rinci melalui dialog, termasuk masalah ukuran, izin folder, dan upload parsial.
- Perbaikan berlaku untuk artikel baru dan edit artikel.
- Tidak ada migrasi SQL.

## Editorial post dan Pusat Pembaruan

- Memperbaiki markup field Kode Inventaris pada form edit naskah.
- Memperbaiki alur simpan artikel baru dan edit artikel agar selalu merespons JSON.
- Menambahkan slug unik otomatis, draf tanpa thumbnail, pratinjau thumbnail, dan tombol Simpan Draf.
- Menambahkan indikator ikon berputar pada proses Pusat Pembaruan.
- Proses update aktif dilanjutkan otomatis; tombol “Lanjutkan” manual dihilangkan.
- Tidak ada migrasi SQL.

## GA4 dan dashboard Goah

- Property ID GA4 `558112895` menjadi default lintas lingkungan.
- Panel visual GA4 tetap membutuhkan service account Viewer untuk membaca Data API.
- Card Aktivitas kontribusi katalog di dashboard dihilangkan.
- Header dashboard “Sampurasun” diperbarui dengan komposisi modern, ringkas, dan responsif.
- Tidak ada migrasi SQL.

## Panel Google Analytics 4

- Menyamakan format konfigurasi GA4 di localhost dan server online.
- Menambahkan panel opsional Google Analytics Data API pada Statistik Goah.
- Panel menampilkan metrik, grafik tren, sumber sesi, dan halaman terpopuler.
- Tidak ada migrasi SQL; `website_visits` tetap menjadi klaster internal terpisah.
- `config/analytics.local.php` tetap dipertahankan oleh Pusat Pembaruan.

## Rekonsiliasi lokal–online

- Paket ini menyamakan kode aplikasi dari v3.1.17 tanpa memaksa struktur atau isi database.
- Jangan mengimpor dump SQL lokal ke server online, dan jangan menjalankan SQL repair online di localhost.
- Jalankan pemeriksaan `cek-localhost.php` setelah update pada masing-masing lingkungan.
- Pertahankan konfigurasi koneksi, konfigurasi GA4 lokal, gambar/upload, dan tabel backup recovery.

## Pengembangan analitik

- Dashboard admin sekarang menampilkan status konfigurasi GA4 secara terpisah dari statistik internal Kandaga.
- Measurement ID tidak ditampilkan utuh.
- Tidak ada migrasi SQL dan tidak ada perubahan data kunjungan.
- Pertahankan konfigurasi database, konfigurasi analitik lokal, gambar/upload, dan tabel backup recovery.

## Baseline pascarecovery

- Versi ini adalah baseline stabil untuk server online setelah pemulihan database.
- Statistik internal Kandaga dan Google Analytics 4 tetap berada pada dua klaster berbeda.
- Tidak ada migrasi SQL dan tidak ada penggabungan ulang data kunjungan lama.
- Pertahankan `config/database.php`, `config/database.local.php`, `config/analytics.local.php`, seluruh gambar/upload, dan tabel backup recovery.

## Pemeriksaan setelah update

1. Pastikan login admin dan halaman publik terbuka.
2. Periksa jumlah naskah, post, bibliografi, dan statistik internal.
3. Pastikan tag GA4 tetap aktif melalui konfigurasi lokal.
4. Jangan menghapus tabel `kandaga_repair_backup_*` sebelum seluruh fungsi diverifikasi.

- Pusat Pembaruan: repositori lama dan upload ZIP bertahap dengan pratinjau versi/berkas/migrasi.
- Pekerjaan persisten, pencadangan kode dan database, migrasi tercatat, maintenance publik/admin.
- Pemulihan kode atau kode dan database dengan konfirmasi, unduhan backup, riwayat, dan pemeriksaan modifikasi lokal.
- Pengaturan database serta gambar/upload dipertahankan oleh updater.
- Statistik kunjungan harian, mingguan, bulanan, total, dan pengunjung online pada footer serta dashboard.
- Grafik analitik memakai overlay garis tren; kartu naskah dan halaman publik populer berdampingan.
- Sidebar Goah dapat digulir sampai menu terakhir; ikon Pusat Pembaruan diperjelas.
- Geolokasi menampilkan data yang tersedia tanpa baris kosong dan tetap dapat diproses ulang dari cache.
- Integrasi Google Analytics 4 bersifat opsional melalui `KANDAGA_GA_MEASUREMENT_ID`; analitik pihak pertama tetap sumber data utama.
- Statistik publik menampilkan perbandingan naskah yang memiliki dan belum memiliki referensi bibliografi.
- Statistik publik tidak lagi mengulang ringkasan kunjungan website; ringkasan kunjungan tetap tersedia di footer.
- Tanggal pembaruan katalog terakhir ditampilkan berdasarkan `tanggal_input`/`tanggal_update`.
- Tag GA4 dipusatkan pada satu include dan mencakup beranda serta seluruh modul publik.
- Navbar Bibliografi dipecah menjadi Monograf dan Katalog dengan URL bersih.
- Relasi naskah terkait memakai peringkat metadata dan Naskah Pilihan mendukung URL gambar eksternal.
- Status relasi penelitian memfilter primer/sekunder dan memakai pie chart.
- Ikon media sosial dipindahkan ke kolom Kontak Kami dan tipografi tabel kunjungan diselaraskan.
- Relasi bibliografi primer/sekunder kini menampilkan daftar manuskrip terkait dengan modal dan tautan detail.
- Catatan rilis versi lama dikonsolidasikan ke `CHANGELOG.md`; berkas per-versi lama dihapus melalui updater.
- Arsip Referensi kini hanya memuat sumber primer dan sekunder.
- Tab Katalog menyediakan indeks katalog sumber dan halaman daftar manuskrip yang dimuat dari setiap katalog.
- Modal relasi menampilkan sitasi APSA pada bagian atas serta foto, judul, kode, lokasi, dan tautan manuskrip di bagian bawah.
## v3.1.19
Panel GA4 opsional ditambahkan untuk statistik admin. Tidak ada perubahan struktur database.

# Kandaga v3.1.46

## v3.1.46 — Penyempurnaan tampilan dan social preview

- Menghapus ikon detail/view dari card hasil pencarian dan mempertahankan bookmark yang kontras di atas gambar.
- Menampilkan empat kontributor per siklus carousel dengan perilaku responsif desktop, tablet, dan mobile.
- Merapikan disclaimer, menghapus bagian arsitektur/kode sumber, dan memperbarui tanggal dokumen.
- Menambahkan metadata Open Graph dan Twitter Card dinamis pada detail naskah sehingga cover naskah digunakan saat tautan dibagikan.
- Menambahkan fallback favicon hanya jika naskah tidak memiliki gambar.

## Catatan deployment

Pembaruan ini tidak mengubah skema database dan tidak menghapus data katalog, pengguna, atau analitik.

