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

Rilis ini code-only. Tidak ada migrasi SQL, perubahan tabel, backup database, atau perubahan skema.

Untuk pembaruan dari v3.1.67, jalankan Update Center seperti biasa. Jika menu navbar masih tertutup setelah pembaruan, lakukan satu kali hard refresh agar aset Bootstrap dengan query versi baru dimuat.
