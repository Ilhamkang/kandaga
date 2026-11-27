# Importer Katalog Cetak: format CSV dan moderasi

Gunakan `import-examples/template-katalog-cetak.csv` sebagai Acuan kolom. Format baru mempertahankan 22 kolom lama agar batch yang sudah ada tetap dapat dibaca, lalu menambahkan kolom untuk metadata digital, struktur, identitas, riwayat lokasi, dan bibliografi ganda.

## Alur

1. Siapkan CSV UTF-8 dengan pemisah koma. Gunakan template; nama kolom boleh dipetakan ulang di layar **Pemetaan & pratinjau**.
2. Buka Goah → Metadata Importer → **Importer Katalog Cetak**.
3. Pilih referensi bibliografi, unggah CSV, baca pratinjau, lalu periksa pemetaan. **Simpan ke antrean** hanya menulis `katalog_import_batches` dan `katalog_import_rows`.
4. Buka tiap entri untuk membandingkan draf dengan entri Kandaga. Pilih **Buat entri baru**, **Merge**, **Tunda**, atau **Lewati**.
5. Setiap keputusan dicatat di `katalog_import_audit`; identitas sumber yang disetujui ditautkan di `katalog_import_sources` agar tidak diimpor ulang.
6. Batch yang belum diproses dapat dihapus lewat tombol **Hapus batch belum diproses** setelah konfirmasi. Penghapusan ditolak bila ada baris yang sudah diputuskan, identitas tertaut, atau catatan audit.

## Kolom wajib

Identitas sumber wajib diisi: `Sumber_ID`, `Bagian_Sumber`, `Nomor_Entri`, `Halaman_Cetak`, `Halaman_PDF`, dan `Status_Sumber`. `Status_Sumber` harus salah satu dari `deskriptif`, `tabel`, atau `berita`. Entri berstatus `berita` hanya boleh ditunda atau dilewati sampai identitas bendanya terverifikasi.

`Judul` dan `Kode_Inventaris` wajib ada pada draf sebelum entri baru dibuat. Keduanya juga dipakai untuk mencari kandidat duplikat, bersama identitas pada `katalog_identifiers`.

## Kolom lama yang tetap dipakai

`Judul`, `Kode_Inventaris`, `Lokasi_Sumber`, `Bahasa_List`, `Aksara_List`, `Alas_Tulis_List`, `Ukuran`, `Tebal_Naskah`, `Jumlah_Baris`, `Deskripsi_Fisik`, `Deskripsi_Isi`, `Riwayat_Naskah`, `Bentuk_Karangan`, `Asal_Naskah`, `Sumber_ID`, `Bagian_Sumber`, `Nomor_Entri`, `Halaman_Cetak`, `Halaman_PDF`, `Status_Sumber`, `Catatan_Koreksi`, dan `Teks_Sumber`.

Catatan:

- `Ukuran`, `Tebal_Naskah`, dan `Jumlah_Baris` digabung ke `Deskripsi_Fisik` bila `Deskripsi_Fisik` tidak diisi.
- `Asal_Naskah` dan `Lokasi_Sumber` menjadi riwayat lokasi/koleksi, bukan lokasi penyimpanan sekarang.
- `Bentuk_Karangan` ditambahkan sebagai awalan `Deskripsi_Isi`; kodikologi dan kondisi tetap di `Deskripsi_Fisik`, makna teks tetap di `Deskripsi_Isi`.
- `Bahasa_List`, `Aksara_List`, dan `Alas_Tulis_List` dipisah dengan `;`, `,`, atau `|`. Istilah yang belum ada di master Kandaga ditandai dan harus dipilih manual saat moderasi; master tidak dibuat otomatis.

## Kolom baru

| Kolom | Tujuan |
|---|---|
| `Kata_Kunci` | `katalog.kata_kunci` |
| `Penyedia_Akses` | `katalog.penyedia_akses`; jika kosong, memakai nama sumber/batch |
| `Lokasi_Target` | saran **lokasi penyimpanan sekarang**; tetap wajib dikonfirmasi moderator |
| `URL_Web`, `URL_Repositori`, `IIIF_Manifest`, `URL_PDF`, `URL_Gambar_Eksternal` | akses digital pada `katalog`; harus http(s) absolut tanpa kredensial URL, maksimal 2.048 karakter |
| `Identifier_Scheme`, `Identifier_Value`, `Identifier_Source` | identitas tambahan di `katalog_identifiers`; kode inventaris dan identitas sumber tetap dicatat otomatis |
| `Location_Kind` | `asal`, `ditemukan`, atau `sebelumnya` |
| `Location_Place`, `Location_District`, `Location_Province`, `Location_Country` | tempat/koleksi, kabupaten/kota, provinsi, negara pada `katalog_location_history` |
| `Location_Period`, `Location_Certainty`, `Location_Source` | periode, keyakinan (`pasti`, `kemungkinan`, `dugaan`, `belum_diketahui`), dan sumber riwayat lokasi |
| `Record_Type` | `induk` atau `unit`; unit wajib memiliki `Parent_ID` |
| `Parent_ID` | ID `katalog` induk atau kode inventaris yang sudah ada |
| `Unit_Label`, `Unit_Order`, `Structure_Notes` | label unit, urutan, dan catatan pada `katalog_structure` |
| `Bibliografi_ID` | satu atau beberapa ID `tb_bibliografi`, dipisah `;`, `,`, atau `|`; bibliografi batch tetap ditambahkan otomatis |

## Lokasi historis versus lokasi sekarang

`Lokasi_Sumber`, `Asal_Naskah`, dan kolom `Location_*` menggambarkan lokasi pada masa katalog dibuat atau lokasi penyalinan. Data ini masuk `katalog_location_history` dan/atau `Riwayat_Naskah`. Kolom-kolom itu **tidak** otomatis menjadi `katalog.id_lokasi`.

`Lokasi_Target` hanya saran. Moderator tetap memilih lokasi penyimpanan sekarang dari master `tb_lokasi` dan mencentang konfirmasi. Lokasi baru tidak dibuat otomatis.

Riwayat lokasi ditulis saat **Buat entri baru**. Saat **Merge**, riwayat lokasi hanya ditambahkan bila entri tujuan belum memiliki riwayat; data lama tidak ditimpa.

## Duplikat dan charset

Sebelum menyimpan keputusan **Buat entri baru**, engine menampilkan kandidat berdasarkan identitas yang sama, kode inventaris/varian historis, dan judul. Jika ada kandidat, moderator harus secara eksplisit menyatakan entri baru tersebut adalah salinan berbeda. Duplikat yang sama sebaiknya diarahkan ke **Merge**.

Kolom `judul`, `kode_inventaris`, dan `kata_kunci` pada sebagian instalasi masih memakai `latin1_swedish_ci`. Engine melakukan preflight charset dan menolak karakter yang tidak dapat disimpan; teks mentah tetap berada di antrean sehingga tidak hilang.

## Prasyarat skema

Importer memerlukan `migrations/005_catalog_import.sql`, `migrations/013_catalog_structure_stage1.sql`, dan `migrations/018_web_import_batch_separation.sql`. Mulai paket 3.1.71, ketiganya terdaftar di manifest Update Center dan dijalankan otomatis saat pembaruan; tombol **Periksa & Lengkapi** juga dapat menjalankannya pada instalasi yang sudah memakai manifest tersebut.
