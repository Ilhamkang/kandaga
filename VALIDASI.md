# Validasi Kandaga v3.1.65 — KRIR Tahap 3

- [ ] Pastikan versi berubah dari 3.1.64 ke 3.1.65 dan paket ditandai code-only; tidak ada migrasi SQL atau backup database.
- [ ] Uji login dengan username/password normal, input array/tidak valid, percobaan gagal berulang, dan login berhasil; token CSRF harus berputar setelah autentikasi.
- [ ] Uji logout; cookie sesi terhapus dan halaman admin tidak dapat dibuka kembali tanpa login.
- [ ] Uji semua form POST publik/admin pada domain Kandaga; request same-origin tetap berhasil, request dengan Origin domain lain ditolak.
- [ ] Uji form naskah, edit naskah, kabar, lokasi, bibliografi, profil, feedback, inbox, dan pengaturan SMTP.
- [ ] Uji upload gambar naskah, profil, lokasi, bibliografi, dan thumbnail kabar; pastikan gambar tampil, permission target 0644, serta file skrip tidak dapat dieksekusi dari folder aset.
- [ ] Pada Update Center, pastikan preview menampilkan backup kode saja dan tidak memulai backup database.
- [ ] Jalankan update code-only, tutup/reload browser, lalu buka kembali `/admin/update.php`; pekerjaan harus dapat dilanjutkan dari state terakhir.
- [ ] Jika tidak ada tick selama lima menit, pastikan peringatan heartbeat muncul tanpa menghapus job, maintenance.lock, atau incoming upload.
- [ ] Pastikan jeda polling tidak membanjiri endpoint update-center dan request memakai sesi same-origin.
- [ ] Periksa CSP Report-Only, proteksi config/includes/tools/migrations/metadata, dan seluruh smoke test v3.1.64.
- [ ] Pastikan katalog, bibliografi, importer, bookmark, SMTP/korespondensi, statistik internal, Zotero, SEO, dan layout desktop/mobile tidak berubah.

# Validasi Kandaga v3.1.63 — KRIR Tahap 1

- [ ] Pastikan versi berubah dari 3.1.62 ke 3.1.63 dan paket ditandai code-only; tidak ada tahap backup database atau migrasi SQL.
- [ ] Pastikan API key Zotero lama tidak muncul pada source tree, ZIP, HTML, URL, atau error response.
- [ ] Set `KANDAGA_ZOTERO_API_KEY` di environment server setelah key lama dicabut/dirotasi; uji halaman Referensi dengan key valid dan tanpa key.
- [ ] Dengan key valid, Referensi menampilkan judul/penulis yang aman jika berisi karakter HTML; tidak ada output mentah atau script yang dieksekusi.
- [ ] Simulasikan Zotero lambat/tidak tersedia; halaman harus kembali dengan pesan umum dalam batas waktu, tanpa stack trace atau detail curl.
- [ ] Uji akses langsung ke `/config/`, `/includes/`, `/admin/includes/`, `/admin/engines/`, root `config.php` (jika ada), `.env`, `.git/`, dan artefak `.sql/.zip/.bak/.log`; semuanya harus ditolak/tidak disajikan.
- [ ] Uji form naskah, edit naskah, form kabar, dan pendaftaran pengguna melalui Goah serta URL langsung; akses tanpa sesi ditolak, role non-admin tidak dapat membuka form admin-only.
- [ ] Uji API import tanpa key, key salah, method GET, payload terlalu besar, dan error database; respons tidak membocorkan SQL/internal detail.
- [ ] Jalankan smoke test homepage, search, detail naskah, gambar, PDF/IIIF, bookmark, bibliografi, importer, SMTP/Inbox, statistik katalog, SEO, serta mode mobile.
- [ ] Pastikan tidak ada perubahan pada tabel, naskah, bibliografi, statistik katalog, SMTP, bookmark, atau Naskah Pilihan.

# Validasi Kandaga v3.1.62

- [ ] Pastikan versi terpasang berubah dari 3.1.61 ke 3.1.62.
- [ ] Pada hasil pencarian thumbnail dan list, indikator PDF/IIIF tampil sebagai ikon kecil di samping ikon alas tulis tanpa label, kotak, atau link aktif.
- [ ] Pastikan kode inventaris panjang dapat membungkus dan tidak terpotong.
- [ ] Buka detail naskah; link PDF/IIIF tetap aktif dan tidak berubah.
- [ ] Uji Statistik, Tentang, Kontributor, Detail Kontributor, dan Disclaimer pada lebar 320, 375, 390, dan 430 piksel.
- [ ] Pastikan tidak ada nested container atau padding bertumpuk pada halaman khusus.
- [ ] Buka Bibliografi Monograf pada desktop dan mobile; sitasi, cover, DOI, URL, dan tombol tidak terpotong.
- [ ] Buka modal “Lihat naskah terkait”; modal tidak melebihi layar, header memakai warna tema, cover tampil jika tersedia, dan sitasi tampil otomatis dalam format APSA tanpa tulisan “APSA”.
- [ ] Uji modal tanpa cover serta cover yang gagal dimuat; tidak boleh muncul gambar rusak.
- [ ] Pastikan item naskah terkait berubah menjadi layout satu kolom pada layar sempit.
- [ ] Pada Indeks Judul, pastikan maksimal 40 judul unik per halaman, informasi rentang tampil, serta tombol Sebelumnya/Berikutnya berfungsi.
- [ ] Uji nomor halaman di luar batas; halaman harus dinormalisasi dan tidak menampilkan daftar kosong.
- [ ] Pastikan pagination tetap ringkas pada mobile.
- [ ] Pastikan tidak ada 404 stylesheet baru, migrasi SQL, perubahan tabel, atau backup database.
- [ ] Pastikan API key Zotero tidak disentuh oleh rilis ini.

# Validasi Kandaga v3.1.61

- [ ] Pastikan versi terpasang berubah dari 3.1.60 ke 3.1.61.
- [ ] Uji homepage pada 320, 375, 390, dan 430 piksel; pastikan tidak ada frame atau gutter bertumpuk.
- [ ] Pastikan Naskah Pilihan tetap memakai logika, sumber gambar, dan perilaku data yang sama.
- [ ] Pastikan Kabar Terbaru dan Kontributor tidak menambahkan container internal kedua.
- [ ] Buka Goah pada mobile; pastikan `main-wrapper`, `kontenUtama`, dan modul tidak menumpuk padding horizontal.
- [ ] Uji dashboard, form, tabel, Inbox, Feedback Naskah, Statistik, dan Pengaturan pada mobile.
- [ ] Pastikan detail naskah: Riwayat Naskah, Deskripsi Fisik, dan Deskripsi Isi tampil justified untuk teks panjang.
- [ ] Pastikan stylesheet detail memuat versi dinamis dan tidak menggunakan cache CSS lama.
- [ ] Uji search mode thumbnail dan list dengan naskah tanpa akses, PDF, IIIF, serta PDF+IIIF.
- [ ] Pastikan ikon PDF/IIIF hanya muncul untuk URL HTTP(S) valid, dapat diklik, dan memiliki label aksesibel.
- [ ] Pastikan klik bookmark, detail naskah, filter, pagination, dan viewer tidak berubah.
- [ ] Pastikan tidak ada 404 asset baru, migrasi SQL, perubahan tabel, atau backup database.

# Validasi Kandaga v3.1.60

- [ ] Pastikan versi terpasang berubah dari 3.1.59 ke 3.1.60.
- [ ] Uji detail naskah dengan gambar lokal dan URL; gambar tetap terpusat, rasio terjaga, lebar mengikuti kontainer, dan tinggi tidak melampaui batas desktop/mobile.
- [ ] Uji detail naskah pada lebar 320, 375, 390, dan 430 piksel; gambar tidak meluber dan metadata tetap satu kolom.
- [ ] Pastikan isi Deskripsi Fisik dan Deskripsi Isi rata kiri-kanan (justified), termasuk paragraf/list HTML.
- [ ] Buka form edit naskah dengan data HTML legacy; editor menampilkan formatnya, bukan literal tag atau entity HTML.
- [ ] Uji simpan ulang rich-text dan pastikan tidak ada perubahan massal pada data naskah lain.
- [ ] Buka detail artikel dan daftar Kabar pada mobile; frame tidak bertumpuk tebal, konten media tidak meluber, dan kartu tetap terbaca.
- [ ] Pastikan homepage Kabar Terbaru tidak memiliki padding container berlapis; Naskah Pilihan tidak berubah.
- [ ] Uji desktop; layout dan fungsi yang sudah ada tetap dipertahankan.
- [ ] Pastikan tidak ada migrasi SQL, perubahan tabel, atau backup database.

# Validasi Kandaga v3.1.59

- [ ] Pastikan versi terpasang berubah dari 3.1.58 ke 3.1.59.
- [ ] Uji detail naskah dengan gambar lokal; gambar tampil di tengah, tidak terpotong, dan rasio asli dipertahankan.
- [ ] Uji detail naskah dengan URL gambar eksternal; gambar tampil di tengah dengan batas ukuran yang sama.
- [ ] Uji detail naskah tanpa gambar atau dengan URL gagal; gambar utama tidak diganti placeholder.
- [ ] Uji detail naskah pada desktop dan lebar mobile 320, 375, 390, serta 430 piksel.
- [ ] Pastikan kartu Naskah Terkait tetap berjumlah dan berisi seperti sebelumnya, serta hover/fokus kembali terlihat.
- [ ] Pastikan Naskah Pilihan halaman depan tidak berubah.
- [ ] Pastikan tidak ada migrasi SQL, perubahan tabel, atau backup database.

# Validasi Kandaga v3.1.58

- [ ] Pastikan versi terpasang berubah dari 3.1.57 ke 3.1.58.
- [ ] Pada Statistik Katalog, bandingkan total status relasi penelitian dengan jumlah naskah pada katalog; naskah yang memiliki primer dan sekunder sekaligus tetap dihitung satu kali pada total terkait.
- [ ] Pastikan kartu Naskah Terpopuler dan 10 kata kunci pencarian teratas memiliki tinggi yang sama pada desktop dan tetap nyaman pada mobile.
- [ ] Uji detail naskah dengan 1, 2, 3, dan 4 item aksara/bahasa/alas tulis; format tiga item atau lebih harus `A, B, & C`.
- [ ] Uji detail manuskrip pada lebar 320, 375, 390, dan 430 piksel; label metadata harus berada di atas nilai, bukan dua kolom.
- [ ] Lipat sidebar Goah; ikon harus berukuran seragam dan tooltip nama menu muncul saat hover/fokus.
- [ ] Buat dan edit naskah tanpa kode inventaris; penyimpanan harus berhasil.
- [ ] Pada dropdown Lembaga Penyimpan, ketik sebagian nama lembaga; hasil harus dapat ditemukan dan nilai lama tetap tampil saat edit.
- [ ] Pastikan tidak ada migrasi SQL, perubahan tabel, atau backup database.

# Validasi Kandaga v3.1.57

- [ ] Pastikan versi terpasang berubah dari 3.1.56 ke 3.1.57.
- [ ] Pastikan `assets/css/module-fonts.css`, `assets/css/mobile-compat.css`, dan `admin/assets/css/style-admin-consistency.css` dapat dimuat tanpa 404.
- [ ] Buka Dashboard, Daftar Naskah, Tambah/Edit Naskah, Daftar Pustaka, Importer, Pengaturan, Pengguna, dan Statistik; pastikan font serta heading konsisten.
- [ ] Pastikan sidebar, navbar, breadcrumb, label, input, tabel, tombol, badge, dan modal tidak berubah fungsi.
- [ ] Buka Inbox Feedback dan Feedback Naskah; pastikan topbar, daftar pesan, thread, metadata, tombol, dan formulir balasan memiliki gaya yang selaras.
- [ ] Uji pencarian, filter, pagination, tandai baca, buka/tutup thread, kirim balasan, dan hapus pesan.
- [ ] Uji kedua modul korespondensi pada lebar desktop dan mobile 375–430 piksel.
- [ ] Pastikan font monospace hanya dipakai untuk kode/metadata teknis dan tidak memengaruhi teks antarmuka.
- [ ] Pastikan kelas `fw-bold` dan `ms-*` pada template lama tetap tampil benar.
- [ ] Pastikan tidak ada migrasi SQL, perubahan tabel, atau backup database.

# Validasi Kandaga v3.1.56

- [ ] Pastikan versi terpasang berubah dari 3.1.55 ke 3.1.56.
- [ ] Buka **Inbox Feedback** dan gunakan pencarian serta pagination.
- [ ] Pilih pesan kontak dan pastikan isi lengkap tampil.
- [ ] Kirim balasan ke alamat email aktif dan pastikan status terkirim.
- [ ] Muat ulang thread dan pastikan riwayat balasan tetap tampil.
- [ ] Klik balas dua kali pada pesan yang sama; pastikan tidak terjadi pengiriman ganda.
- [ ] Uji tutup dan buka kembali thread.
- [ ] Uji penghapusan pesan dan pastikan riwayat balasan ikut dibersihkan.
- [ ] Uji form kontak dengan CSRF, email tidak valid, dan isi terlalu panjang.
- [ ] Pastikan password SMTP tidak muncul di SQL, HTML, JavaScript, atau ZIP.
- [ ] Pastikan paket dipasang sebagai code-only tanpa migrasi SQL dan backup database.

# Validasi Kandaga v3.1.55

- [ ] Pastikan versi terpasang berubah dari 3.1.54 ke 3.1.55.
- [ ] Buka Pengaturan → Email & Notifikasi sebagai admin.
- [ ] Simpan konfigurasi SMTP; status harus tetap nonaktif sebelum pengujian.
- [ ] Kirim email uji; setelah berhasil status berubah aktif.
- [ ] Buka Feedback Naskah, kirim balasan, dan pastikan thread menampilkan status terkirim.
- [ ] Coba menutup dan membuka kembali thread.
- [ ] Pastikan kontributor tidak dapat membuka Pengaturan atau endpoint balasan.
- [ ] Pastikan password SMTP tidak muncul di SQL, HTML, JavaScript, atau ZIP.
- [ ] Pastikan paket dipasang sebagai code-only tanpa migrasi SQL dan backup database.

# Validasi Kandaga v3.1.54

- Footer menampilkan empat statistik akses agregat: Hari ini, 7 hari terakhir, Bulan ini, dan Total.
- Tidak ada heartbeat presence, daftar pengunjung online, IP, geo, perangkat, atau log request mentah pada jalur aplikasi aktif.
- Tabel `kandaga_page_daily` dan `kandaga_search_daily` dibuat lazy secara idempoten tanpa migrasi SQL.
- `naskah_views`, statistik metadata katalog, relasi penelitian, halaman populer, dan pencarian teratas tetap tersedia.
- Kartu thumbnail pencarian memakai tombol bookmark berbentuk ikon dengan tanda tambah; tidak ada aset gambar baru.
- Tombol bookmark tetap memiliki teks aksesibel yang disembunyikan secara visual pada kartu thumbnail.
- Tidak ada migrasi SQL dan manifest tetap melarang backup database.

# Validasi Kandaga v3.1.52

- Jalur modern `website_visits` hanya mengisi kolom inti: identitas browser/token, halaman, metadata pencarian, dan klasifikasi trafik.
- Kolom IP, user-agent, referrer, hash permintaan, dan metadata diagnostik lain tetap tersedia untuk data lama tetapi dibiarkan nullable pada event baru.
- Crawler tidak ditulis ke `website_visits`; pemeriksaan crawler dilakukan dari access log server.
- Footer, presence, rollup harian, dan retensi otomatis tetap berjalan seperti v3.1.51.
- Manifest menyatakan tidak ada migrasi SQL dan updater menolak mode backup database; hanya backup kode yang diizinkan.
- Arsip ZIP diperiksa agar memiliki file pada root paket, manifest sinkron, dan checksum seluruh berkas cocok.

# Validasi Kandaga v3.1.50

- Panel statistik admin tidak lagi memanggil panel/API Google Analytics atau menampilkan rincian geolokasi/IP.
- KPI dan grafik memakai penghitung internal dengan cache singkat; rincian katalog dan trafik dimuat secara lazy melalui modal.
- Endpoint `admin/actions/statistics-detail.php` hanya menerima tipe rincian yang diizinkan, memakai query terparameterisasi, dan membatasi trafik pada periode 90 hari.
- Tag GA4 tetap hanya untuk telemetri halaman publik dan tidak menulis baris ke `website_visits`.
- Jalur log modern tidak mengantrekan geolokasi dan tidak menyimpan IP/user-agent baru; fallback legacy tetap tersedia untuk skema lama.
- Migrasi 017 menambahkan indeks `(is_bot, traffic_class, visited_at, page_key)` secara idempoten.
- Arsip ZIP diperiksa agar memiliki file pada root paket, manifest sinkron, dan checksum seluruh berkas cocok.

# Validasi Kandaga v3.1.49

- Heartbeat presence dimuat dari footer bersama sehingga beranda ikut mengirim verifikasi browser.
- Footer memperbarui angka aktif secara dinamis; jendela aktif lima menit dan crawler tidak dihitung.
- Endpoint heartbeat mengembalikan jumlah aktif terverifikasi tanpa membuat baris presence baru untuk setiap reload.
- Detail naskah dan `website_visits` memakai classifier crawler yang sama.
- Sidik pencarian memasukkan kata kunci dan filter aktif; pagination tidak membuat hitungan kata kunci baru.
- Pembersihan `website_presence` lama berjalan bertahap maksimal 5.000 baris.
- Rincian admin menjelaskan bahwa log mentah mengikuti masa retensi, sedangkan total historis memakai rollup.
- Migrasi 016 menambahkan indeks heartbeat secara idempoten.

- Cookie `kandaga_visitor` memakai path ter-normalisasi (`/` untuk domain-root dan `/kandaga/` untuk subdirektori).
- Tabel `website_presence` menyimpan satu heartbeat per token anonim; reload tidak menambah baris presence.
- Footer hanya menghitung presence manusia aktif dalam lima menit terakhir; crawler tidak dihitung.
- Page view tetap dicatat pada `website_visits` untuk mempertahankan statistik historis.
- Daftar Pustaka admin memiliki pencarian judul, penulis, penerbit, tahun, ISBN/ISSN, DOI, dan metadata terkait.
- Query pencarian bibliografi menggunakan prepared statement.
- Migrasi 012 bersifat idempoten dan membersihkan hanya presence lebih lama dari dua hari.

- Relasi bibliografi primer/sekunder dihitung distinct melalui `naskah_bibliografi`.
- Modal relasi menampilkan judul dan kode inventaris secara escaped serta tautan detail naskah.
- Relasi sumber berkategori `katalog` diberi perlakuan khusus dan tidak disamakan dengan sumber primer/sekunder.
- Dokumentasi versi dikonsolidasikan ke `CHANGELOG.md`; berkas catatan versi lama dihapus melalui manifest updater.
- Migrasi 010 menambahkan nama crawler dan indeks analitik secara idempoten.
- Migrasi 011 mereklasifikasi user-agent historis dan memperbaiki default `bot_name` tanpa menghapus log.
- Arsip Referensi memfilter kategori primer/sekunder; sumber katalog dipindahkan ke `pages/katalog.php`.
- Halaman katalog menghitung manuskrip secara distinct dan menyediakan pencarian/pagination.
- Modal bibliografi meng-escape data JSON dan menampilkan foto lokal/eksternal dengan placeholder aman.
- Manifest dan checksum berkas diperbarui sebelum distribusi.
- `.htaccess` mengarahkan `/monograf` dan `/katalog`, serta mengkanoniskan URL PHP lama.
- Ranking naskah terkait memprioritaskan judul, lokasi, aksara, bahasa, dan alas tulis.
- Status relasi penelitian hanya menghitung kategori `referensi primer` dan `referensi sekunder`.
- Baseline database pascarecovery tidak menimpa data katalog atau konfigurasi koneksi.
- `website_visits` tetap menjadi sumber statistik internal first-party; GA4 tidak dicampur sebagai baris kunjungan internal.
- Konfigurasi GA4 tetap opsional melalui `config/analytics.local.php` atau `KANDAGA_GA_MEASUREMENT_ID`.
- Tidak ada migrasi SQL baru pada rilis baseline ini.
- Paket ini tidak membawa SQL repair recovery dan tidak menimpa data database.
- Basis lokal `katalogus` dan basis online `kairag23_kamus` tetap dipertahankan masing-masing.
- Perbedaan jumlah data kunjungan tidak disamakan secara artifisial.
- Panel admin menampilkan status konfigurasi GA4 tanpa mengekspos Measurement ID penuh.
- Panel GA4 tidak mengubah atau menggabungkan baris `website_visits`.
