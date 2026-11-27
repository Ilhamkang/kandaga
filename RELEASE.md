# Kandaga v3.1.71

Penyempurnaan struktur metadata katalog dan kesiapan pembaruan skema otomatis. Riwayat versi lama tetap tersedia di `CHANGELOG.md` dan GitHub Release.

- Menyelaraskan field metadata katalog dengan struktur form input dan database saat ini.
- Menambahkan dukungan metadata akses digital, struktur rekaman, identitas alternatif, riwayat lokasi, dan tautan bibliografi ganda.
- Memisahkan lokasi historis dari lokasi penyimpanan sekarang yang tetap diverifikasi manual.
- Merapikan alur tinjauan dan pencatatan perubahan agar jejak keputusan tetap lengkap.
- Memperbarui contoh data dan panduan kolom pendukung.
- Mendaftarkan migrasi SQL skema katalog ke manifest Update Center agar dijalankan otomatis saat pembaruan dari v3.1.70.

Paket ini **menyertakan migrasi SQL**. Update Center mewajibkan backup database penuh sebelum pemasangan dan menjalankan migrasi idempoten setelah berkas aplikasi terpasang. Tidak ada data katalog yang dihapus atau ditimpa oleh migrasi tersebut.

Untuk pembaruan dari v3.1.70, buka Pusat Pembaruan, unduh paket v3.1.71, lalu ikuti pratinjau migrasi. Instalasi pada-versi lebih lama harus diperbarui ke v3.1.70 terlebih dahulu.
