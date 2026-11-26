# Kandaga v3.1.69

Merapikan kontrak layout agar modul beranda, halaman publik, dan panel admin memakai pembungkus yang lebih sedikit dan lebih konsisten di desktop maupun mobile. Riwayat versi lama tetap tersedia di `CHANGELOG.md` dan GitHub Release.

- Memindahkan stylesheet beranda, naskah terbaru, Naskah Pilihan, kontributor, dan `public-mobile.css` ke `<head>`. Modul kini berupa fragmen konten dan tidak lagi menyuntik `<link>`/`<style>` dari dalam `<body>`, sehingga urutan kaskade tidak lagi bergantung pada posisi include.
- Menghapus padding dan margin ganda pada modul beranda: `py-4` pada Naskah Pilihan dan `my-5` pada Kabar Terbaru dihapus; jarak kini sepenuhnya diatur oleh `.home-section`.
- Menghapus pembungkus `.row > .col-md-12` yang hanya membungkus entri naskah terbaru. Beranda kini `.home-section > .container > modul`, tanpa lapisan grid kosong.
- Menyeragamkan grid kartu ke pola `.row.row-cols-* > .col` tanpa `col-*` ganda di anak, pada naskah terbaru, katalog, dan naskah dalam detail koleksi. Pola mobile tetap dua kartu per baris.
- Memuat `public-mobile.css` sekali dari `module-head.php` dan menambahkan hook `kandaga-standalone-page` pada halaman yang sebelumnya hanya memakai `kandaga-module`, sehingga aturan gutter mobile menjangkau login, kontak, bookmark, referensi, viewer, dan halaman sejenis. Indeks lokasi kini juga memakai `kandaga-special-page`.
- Menghapus selector mati pada `mobile-layout.css`, override gutter mobile yang sudah ditangani global, duplikasi tinggi thumbnail kabar, dan membatasikan aturan `.card-title` agar tidak menimpa kartu halaman lain.
- Merapikan panel admin: modul yang di-include tidak lagi membuka dokumen HTML lengkap atau `.container-fluid` bertumpuk. Akses langsung ke file admin tetap menghasilkan halaman lengkap.
- Menurunkan rantai gutter beranda dari empat lapis menjadi tiga tanpa mengubah ukuran kartu kontributor maupun tinggi Naskah Pilihan.

Rilis ini code-only. Tidak ada migrasi SQL, perubahan tabel, backup database, atau perubahan skema.

Untuk pembaruan dari v3.1.68, jalankan Update Center seperti biasa.
