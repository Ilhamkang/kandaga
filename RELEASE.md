# Kandaga v3.1.70

Menggabungkan perapian kontrak layout dari v3.1.69 dengan engine DREAMSEA terstruktur. Riwayat versi lama tetap tersedia di `CHANGELOG.md` dan GitHub Release.

- Memindahkan stylesheet beranda, naskah terbaru, Naskah Pilihan, kontributor, dan `public-mobile.css` ke `<head>`; modul kini berupa fragmen dan tidak lagi menyuntik `<link>`/`<style>` dari dalam `<body>`.
- Menghapus padding, margin, dan pembungkus grid ganda pada beranda; rantai gutter mobile turun dari empat lapis menjadi tiga. Grid kartu diseragamkan ke `.row.row-cols-* > .col` dengan maksimal dua kartu per baris di mobile, termasuk kontributor 2×2 dan kabar terbaru empat item.
- Merapikan modul admin agar tidak menggandakan dokumen HTML atau `.container-fluid` ketika di-include dari shell Goah; akses langsung tetap menghasilkan halaman lengkap.
- Mengganti engine DREAMSEA lama yang melakukan `INSERT` langsung dan membuat master otomatis. Engine baru memakai alur unggah → validasi → antrean → moderasi → audit seperti Importer Katalog Cetak.
- Menjadikan `msid`/kode `DS` sebagai identitas utama dengan `source_key` turunan; duplikat mengarah ke pemeriksaan/merge, bukan entri baru. `Buat entri baru` menolak identitas yang sudah tersimpan kecuali moderator secara eksplisit menyatakan entri berbeda.
- Menulis field struktur baru saat persetujuan: `katalog_structure`, `katalog_identifiers`, `katalog_location_history`, pivot bahasa/aksara/alas tulis, dan `naskah_bibliografi`. Lokasi historis tidak mengubah lokasi penyimpanan saat ini.
- Melarang pembuatan otomatis `tb_bahasa`, `tb_aksara`, `tb_alas_tulis`, dan `tb_lokasi`; istilah yang belum cocok ditandai dan dipilih manual saat moderasi.
- Menambahkan pratinjau data mentah, kandidat duplikat, perbandingan entri dengan pemeriksaan revisi, charset preflight, validasi URL, dan pencatatan audit transaksional.
- Menambahkan template CSV terstruktur dan panduan `docs/dreamsea-normalized-import.md`, termasuk gaya terjemahan Kandaga: judul asli dipertahankan, deskripsi diawali “Naskah ini berisi …”, dan riwayat akuisisi memakai “Riwayat akuisisi: …”.
- Endpoint engine lama di `admin/engines/` tidak lagi tersedia; seluruh aksi kini lewat `admin/actions/dreamsea-import.php` dengan otorisasi admin dan CSRF.

Rilis ini code-only. Tidak ada migrasi SQL, perubahan tabel, backup database, atau perubahan skema pada paket ini. Tabel `005` dan `013` sudah tersedia sejak rilis sebelumnya; engine akan menolak berjalan dengan pesan yang jelas jika keduanya belum ada.

Untuk pembaruan dari v3.1.68 atau v3.1.69, jalankan Update Center seperti biasa. Tidak ada data katalog yang diubah oleh pemasangan rilis ini; impor DREAMSEA dijalankan terpisah setelah berkas CSV disiapkan dan setiap entri dimoderasi.
