# Kandaga v3.1.72

Penyempurnaan pengelolaan antrean impor: batch kini dapat dibersihkan tanpa menghilangkan entri yang sudah disetujui. Riwayat versi lama tetap tersedia di `CHANGELOG.md` dan GitHub Release.

- Batch yang sudah diproses dapat dihapus setelah konfirmasi.
- Entri katalog yang sudah dibuat tidak ikut terhapus.
- Tautan identitas dan catatan keputusan moderasi tetap tersimpan walau berkas CSV asal sudah dihapus.
- Jejak yang menunjuk ke entri yang dihapus manual ikut dibersihkan saat batch dihapus.
- Teks konfirmasi dan panduan impor diselaraskan dengan perilaku baru.

Paket ini **menyertakan migrasi SQL additif** (`migrations/019_import_detachable_audit.sql`). Update Center mewajibkan backup database penuh sebelum pemasangan. Tanpa migrasi tersebut, penghapusan batch yang sudah diproses tetap ditolak seperti versi sebelumnya.

Untuk pembaruan dari v3.1.70 atau v3.1.71, buka Pusat Pembaruan, unduh paket v3.1.72, lalu ikuti pratinjau migrasi.
