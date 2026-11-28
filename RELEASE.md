# Kandaga v3.1.73

Penyempurnaan moderasi impor Web dan Katalog Cetak. Riwayat versi lama tetap tersedia di `CHANGELOG.md` dan GitHub Release.

- Pencarian bibliografi pada kedua importer kini dapat diketik; label menampilkan judul, penyusun, dan tahun tanpa menambah endpoint atau CDN baru.
- Saat membandingkan duplikat, moderator dapat memilih per detail: mempertahankan data lama, memakai draf yang diedit, atau menambahkan draf dengan atribusi sumber.
- Pilihan serupa tersedia untuk lokasi serta relasi bahasa, aksara, dan alas tulis; nilai lama tidak lagi tertimpa tanpa keputusan.
- Sumber yang sudah tertaut kini dapat dibandingkan dan digabung ke entri tujuan yang sama. Pembuatan entri baru tetap ditolak untuk identitas tersebut.
- Kode inventaris yang sama tidak memblokir entri baru apabila identitas sumber berbeda; konfirmasi duplikat tetap wajib.
- Validasi draf hanya dijalankan bila draf memang dipakai, sehingga memilih data lama tidak terhalang oleh draf yang bermasalah.
- Antrean menampilkan penanda “Tersimpan” untuk entri yang dibuat atau digabung, lengkap dengan tautan ke entri katalog.
- Ringkasan moderasi kini memuat badge “Dilewati” dan filter yang sesuai.

Rilis ini tidak menambah migrasi baru. Paket tetap membawa migrasi additif bawaan manifest (`min_from 3.1.70`), sehingga Pusat Pembaruan tetap mewajibkan backup database penuh sebelum pemasangan.

Untuk pembaruan dari v3.1.72, buka Pusat Pembaruan, unduh paket v3.1.73, lalu ikuti pratinjau migrasi seperti biasa.
