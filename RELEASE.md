# Kandaga v3.1.68

Perbaikan menu navbar pada layar mobile dan penyederhanaan laporan rilis. Riwayat versi lama tetap tersedia sebagai GitHub Release masing-masing; ringkasan historis disimpan di `CHANGELOG.md`.

- Menambahkan cache-buster `?v=<versi>` pada CSS dan JavaScript Bootstrap di seluruh template agar browser tidak lagi memakai bundle Bootstrap 4.6.2 yang tersimpan pada URL tanpa versi. Menu lipat navbar mobile kini membuka `#navbarNav` dengan normal setelah pembaruan.
- Menambahkan `aria-controls`, `aria-expanded`, dan `aria-label` pada tombol toggle navbar.
- Menyederhanakan `RELEASE.md` agar hanya memuat catatan rilis versi terbaru; arsip perubahan tetap berada di `CHANGELOG.md` dan GitHub Release.
- Menghapus bagian laporan hasil uji dan daftar pemeriksaan deployment dari catatan rilis serta changelog.
- Memperketat `tools/build-release.php`: `RELEASE.md` wajib memuat tepat satu judul versi, `version.json.version` harus sinkron dengan judul dan tag, serta format laporan rilis divalidasi.
- Melanjutkan penyesuaian pada modul katalog/detail, admin, dan modul pendukung terkait; rinciannya akan dimasukkan sebelum tag rilis dibuat.

Rilis ini code-only. Tidak ada migrasi SQL, perubahan tabel, backup database, atau perubahan skema.

Untuk pembaruan dari v3.1.67, jalankan Update Center seperti biasa. Jika menu navbar masih tertutup setelah pembaruan, lakukan satu kali hard refresh agar aset Bootstrap dengan query versi baru dimuat.
