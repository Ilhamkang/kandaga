# Kandaga v3.1.75

Penyempurnaan alur penulisan, Pustaka Media, dan tampilan publik dengan tema Teal yang konsisten di seluruh aplikasi.

- Seluruh popup bawaan `alert()`/`confirm()` dan SweetAlert2 aplikasi kini memakai satu dialog terpusat yang tampil di tengah layar, termasuk konfirmasi untuk aksi berisiko.
- Judul submodul admin memakai satu kaidah: ikon, judul, dan penjelasan singkat di bawahnya; breadcrumb dihapus dan tombol aksi memakai ikon plus tooltip.
- Preferensi Katalog diredesain menjadi empat kartu ringkas untuk Aksara, Alas Tulis, Bahasa, dan Lokasi dengan pencarian, badge jumlah, dan switch tampilan.
- Aturan kurator ditegakkan di sisi server: naskah/koleksi yang dipegang kurator tidak dapat disunting kontributor lain, sementara administrator tetap memiliki kendali penuh.
- TinyMCE lokal 7.7.2 mengaktifkan seluruh plugin open-source yang tersedia, menyembunyikan promosi/upgrade, dan menyisipkan gambar dari Pustaka Media langsung di posisi kursor.
- CAPTCHA kontak, registrasi, dan endpoint kontak kini memakai glyph miring dan acak sekali pakai; pesan masuk mendapat field topik tetap (Umum, Kerjasama, Testimoni, Saran & Kritik, Lainnya) yang tampil sebagai filter dan badge di inbox.
- Tab Log Global/Aktivitas Saya disamakan dengan tema admin; Pustaka Media menggantikan Pustaka Berkas dengan pratinjau metadata, salin tautan, dan hapus aman yang menolak berkas masih terpakai.
- Artikel dapat memilih gambar utama dan beberapa gambar galeri dari Pustaka Media; nomor urut pada Daftar Postingan dihapus dan galeri dirender pada halaman kabar.
- Header halaman publik dipraktikkan ulang menjadi kop ringkas tanpa breadcrumb: pencarian kabar menjadi toggle, abjad Indeks Judul pindah ke filter rail, dan seluruh warna mengikuti token Teal/slate dengan penyesuaian mobile.

Rilis ini **menyertakan migrasi SQL additif** (`migrations/021_inbox_topic.sql` dan `migrations/022_kabar_gambar.sql`). Paket tetap membawa seluruh migrasi bawaan manifest (`min_from 3.1.70`), sehingga Pusat Pembaruan mewajibkan backup database penuh sebelum pemasangan.

Untuk pembaruan dari v3.1.70 hingga v3.1.74, buka Pusat Pembaruan, unduh paket v3.1.75, lalu ikuti pratinjau migrasi seperti biasa.
