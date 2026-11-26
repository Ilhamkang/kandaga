# Kandaga v3.1.68

Perbaikan menu navbar pada layar mobile dan penyederhanaan laporan rilis. Riwayat versi lama tetap tersedia sebagai GitHub Release masing-masing; ringkasan historis disimpan di `CHANGELOG.md`.

- Menambahkan cache-buster `?v=<versi>` pada CSS dan JavaScript Bootstrap di seluruh template agar browser tidak lagi memakai bundle Bootstrap 4.6.2 yang tersimpan pada URL tanpa versi. Menu lipat navbar mobile kini membuka `#navbarNav` dengan normal setelah pembaruan.
- Menambahkan `aria-controls`, `aria-expanded`, dan `aria-label` pada tombol toggle navbar.
- Menyederhanakan `RELEASE.md` agar hanya memuat catatan rilis versi terbaru; arsip perubahan tetap berada di `CHANGELOG.md` dan GitHub Release.
- Menghapus bagian laporan hasil uji dan daftar pemeriksaan deployment dari catatan rilis serta changelog.
- Memperketat `tools/build-release.php`: `RELEASE.md` wajib memuat tepat satu judul versi, `version.json.version` harus sinkron dengan judul dan tag, serta format laporan rilis divalidasi.
- Memperbaiki kontras judul pada header pencarian mobile menjadi putih sehingga “Eksplorasi Katalog Naskah” terbaca jelas di atas latar gelap.
- Menata ulang tipografi hero beranda pada layar kecil: judul `1.85rem`, paragraf dan tombol `0.95rem`, serta angka dan label statistik yang proporsional.
- Memperbaiki kartu monograf publik di mobile: sitasi tidak lagi terjepit menjadi kolom 56px ketika sampul tidak tersedia, tinggi kartu turun drastis, dan pagination tidak lagi meluap.
- Menambahkan cache-buster versi pada stylesheet beranda agar perbaikan tipografi langsung diterima browser.
- Memperbaiki modal “Lihat naskah terkait” pada monograf: warna sitasi header diubah menjadi terang (kontras ~15:1), judul ganda dihapus karena sudah tercantum dalam format APSA, dan tinggi popup kini mengikuti isi (377px untuk 1 naskah, bukan 828px penuh layar).
- Menambahkan modal “Format kutipan” yang sebelumnya tidak ada: tab APA/APSA/Chicago dan tombol salin dengan fallback untuk browser tanpa akses clipboard.
- Menyeragamkan tinggi modal agar tidak dipaksa `min-height` penuh layar oleh Bootstrap 5 pada layar kecil.
- Menambahkan pengecekan duplikat pada form bibliografi admin: indikator langsung saat mengisi serta penolakan simpan apabila judul, penyusun, tahun, dan kategori identik setelah normalisasi spasi dan huruf besar/kecil.
- Memperbaiki form edit naskah: tombol hapus kini tersedia pada semua baris bibliografi, termasuk satu-satunya entri yang tersedia, sehingga relasi katalog/primer/sekunder benar-benar bisa dilepas.
- Menyesuaikan tombol hapus baris di form edit menjadi kotak 34×34px yang sejajar dengan tinggi `form-select`, tidak lagi tampak kebesar.
- Memperbaiki ekspor XML HSP-KOD dan HSP-DESC: atribut `type` tidak lagi memuat backslash literal dan teks relasi seperti “Arab & Pegon” kini dieskap sebagai `&amp;`. Semua format XML (MODS, MARC21, TEI, HSP) ikut diamankan.
- Menyederhanakan halaman detail naskah pada layar kecil: judul dan aksi tampil sebelum sampul, sampul dibatasi `34vh`, kartu memakai satu gutter, field metadata pendek dirapatkan dua kolom, serta tabel Akses Digital dan Lokasi Koleksi di-stack tanpa scroll horizontal. Lebar area baca naik dari 318px menjadi 342px dan tinggi halaman turun sekitar 800px.
- Menyeragamkan grid kartu publik pada layar kecil: naskah terbaru, kontributor, kabar terbaru, koleksi, inventaris koleksi, indeks lokasi, katalog, dan manuskrip dalam katalog kini tampil maksimal dua kartu per baris. Gambar dan padding kartu dipangkas agar tidak memenuhi satu layar; tata letak desktop tidak berubah. Kartu grafik statistik tetap penuh lebar karena memuat grafik dan tabel sekaligus.
- Mengganti carousel kontributor beranda dengan grid empat kartu per halaman dan pager kecil di header: 2×2 di mobile, 3 kolom di tablet, 4 kolom di desktop lebar, dengan avatar dan tipografi yang proporsional. Kontrol lama masih memakai `data-slide` Bootstrap 4 sehingga tombol panah tidak berfungsi setelah upgrade Bootstrap 5; overlay yang meluap sekitar 8px di tepi kanan juga hilang.
- Menambahkan pergantian halaman kontributor otomatis di desktop dengan crossfade lembut setiap 6,5 detik. Gerakan berhenti saat kursor atau fokus berada di seksi, saat tab tersembunyi, dan untuk `prefers-reduced-motion`.
- Menyeimbangkan tinggi kartu kontributor: nama dan deskripsi masing-masing mendapat alokasi dua baris tetap, sehingga satu nama panjang tidak lagi meregangkan seluruh baris dan menyisakan ruang kosong di kartu lain.
- Mengunci tinggi gambar Naskah Pilihan menjadi 320px di desktop lebar, 300px di tablet, dan 220px di mobile; deskripsi dibatasi empat baris di desktop dan tiga baris di mobile sehingga card tidak lagi mengikuti rasio asli naskah yang berbeda-beda.
- Menambah kabar terbaru beranda menjadi empat artikel agar tersusun 2×2 di mobile dan empat kolom di desktop, sejajar dengan grid kontributor.

Rilis ini code-only. Tidak ada migrasi SQL, perubahan tabel, backup database, atau perubahan skema.

Untuk pembaruan dari v3.1.67, jalankan Update Center seperti biasa. Jika menu navbar masih tertutup setelah pembaruan, lakukan satu kali hard refresh agar aset Bootstrap dengan query versi baru dimuat.
