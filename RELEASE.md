# Kandaga v3.1.74

Penyatuan tampilan administrasi dan penambahan informasi akses naskah. Riwayat versi lama tetap tersedia di `CHANGELOG.md` dan GitHub Release.

- Seluruh modul admin kini memakai satu permukaan kerja putih yang seragam, termasuk dashboard, kotak masuk, feedback naskah, statistik, dan halaman yang dibuka langsung.
- Tema admin memakai token warna dan tipografi di dalam repositori dengan aksen teal gelap; Bootstrap tetap satu bundle lokal 5.3.9 tanpa CDN.
- CSS inline diekstraksi ke stylesheet khusus, duplikasi override dirapikan, dan cache-buster disamakan memakai versi aplikasi.
- Nesting markup dirapikan: label Bootstrap 5, ikon Font Awesome 6, heading halaman, modal, dan daftar kosong tidak lagi memakai pola lama.
- Permintaan fragmen jQuery `.load()` kini dikenali sebagai fragmen shell, sehingga pemuatan modul tidak membangun ulang dokumen penuh.
- Detail naskah menampilkan narasi akses fisik dan status arsip digital berdasarkan field baru; keterangan tambahan tampil bila diisi, tanpa klaim akses publik.
- Form input/edit naskah menerima `mode_akses_fisik`, `pihak_kontak`, `status_arsip_digital`, dan `keterangan_arsip_digital` dengan validasi nilainya.
- Halaman Tentang menambahkan ajakan kolaborasi metadata naskah Sunda dengan jalur kontak resmi.

Rilis ini **menyertakan migrasi SQL additif** (`migrations/020_catalog_access_metadata.sql`). Paket tetap membawa kelima migrasi bawaan manifest (`min_from 3.1.70`), sehingga Pusat Pembaruan mewajibkan backup database penuh sebelum pemasangan.

Untuk pembaruan dari v3.1.70 hingga v3.1.73, buka Pusat Pembaruan, unduh paket v3.1.74, lalu ikuti pratinjau migrasi seperti biasa.
