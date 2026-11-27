# Importer Web: format CSV terstruktur

Engine lama (`Judul,Kode_Inventaris,…` → `INSERT katalog`) sudah tidak dipakai. Gunakan format berikut agar metadata hasil scraping web—termasuk DREAMSEA—masuk ke struktur Kandaga yang baru. Antrean Importer Web terpisah dari Importer Katalog Cetak.

## Alur

1. Ekstrak metadata dari `https://hmmlcloud.org/dreamsea/detail.php?msid=<id>`.
2. Terjemahkan teks ke bahasa Indonesia dengan gaya Kandaga (lihat bawah).
3. Susun CSV UTF-8 sesuai `import-examples/dreamsea-normalized-template.csv`.
4. Buka Goah → Metadata Importer → **Importer Web**.
5. Unggah CSV → pratinjau → **Simpan ke antrean**. Tidak ada data yang berubah pada langkah ini.
6. Moderasi per entri: pilih lokasi penyimpanan, padanan bahasa/aksara/alas tulis, periksa kandidat duplikat, lalu **Buat entri baru**, **Merge**, **Tunda**, atau **Lewati**.
7. Setiap keputusan tercatat di `katalog_import_audit` dan `user_logs`.
8. Batch disimpan dengan penanda submodul `web`; daftar batch Importer Katalog Cetak (`catalog`) tidak menampilkan batch web, dan sebaliknya.
9. Batch web yang belum diproses dapat dihapus lewat tombol **Hapus batch belum diproses** setelah konfirmasi. Penghapusan ditolak bila masih ada baris yang sudah diputuskan, identitas tertaut, atau catatan audit. Penghapusan dicatat di `user_logs`.

## Kolom CSV

Wajib: `msid`, `ds_id`, atau pasangan `Identifier_Scheme`/`Identifier_Value`; serta `Judul` dan `Lokasi_Target`. Kolom lain opsional tetapi disarankan lengkap.

| Kolom | Tujuan |
|---|---|
| `msid`, `ds_id` | Identitas sumber. `msid` numerik dan `ds_id` berbentuk `DS 0000 00000` memakai skema `dreamsea-msid` / `dreamsea-ds`; `ds_id` lain memakai `web-source` |
| `record_type`, `parent_msid`, `unit_label`, `unit_order`, `structure_notes` | `katalog_structure` |
| `Judul`, `Kode_Inventaris`, `Lokasi_Target`, `Kata_Kunci`, `Penyedia_Akses` | `katalog` |
| `Bahasa_List`, `Aksara_List`, `Alas_Tulis_List` | pivot `naskah_bahasa`, `naskah_aksara`, `naskah_alas_tulis` (master harus sudah ada) |
| `Deskripsi_Fisik`, `Deskripsi_Isi`, `Riwayat_Naskah` | `katalog` |
| `URL_Web`, `URL_Repositori`, `IIIF_Manifest`, `URL_PDF`, `url_gambar_eksternal` | `katalog`; harus http(s) absolut |
| `Identifier_Scheme`, `Identifier_Value`, `Identifier_Source` | identifier tambahan, mis. shelfmark |
| `Location_Kind` | jenis lokasi historis (`asal`, `ditemukan`, `sebelumnya`) |
| `Location_Place` | tempat/desa/kecamatan/koleksi → `katalog_location_history.place_name` |
| `Location_District` | kabupaten/kota → `katalog_location_history.district` |
| `Location_Province` | provinsi → `katalog_location_history.province` |
| `Location_Country` | negara → `katalog_location_history.country` |
| `Location_Period`, `Location_Certainty`, `Location_Source` | periode, keyakinan, dan sumber pada `katalog_location_history` |
| `Bibliografi_ID` | `naskah_bibliografi` (ID `tb_bibliografi`, dipisah `;`; opsional) |

Aturan penting:

- Tidak ada master yang dibuat otomatis. Jika istilah belum ada di `tb_bahasa`, `tb_aksara`, `tb_alas_tulis`, atau `tb_lokasi`, entri ditandai dan harus dipilih manual saat moderasi.
- `Lokasi_Target` adalah **lokasi penyimpanan sekarang**, bukan lokasi historis. Lokasi historis masuk `Location_*`.
- Saat menyiapkan CSV dari data DREAMSEA, `City (Province)` (mis. `Indramayu (Jawa Barat)`) dipecah menjadi `Location_District=Indramayu` dan `Location_Province=Jawa Barat`. `Place of copying` menjadi `Location_Place`; kecamatan tetap disertakan, tetapi nama kabupaten di akhir dihapus agar tidak dobel. Engine juga menerima bentuk lama `Location_Province=Indramayu (Jawa Barat)` dan memecahnya otomatis saat membaca CSV.
- Riwayat lokasi dari CSV ditulis saat **Buat entri baru**. Saat **Merge**, riwayat hanya ditambahkan bila entri tujuan belum memiliki riwayat lokasi; data lama tidak ditimpa.
- `Location_Certainty`: `pasti`, `kemungkinan`, `dugaan`, atau `belum_diketahui`.
- Kolom teks `katalog` masih `latin1`; engine melakukan preflight charset. Karakter yang tidak didukung ditolak sebelum penulisan, bukan diganti diam-diam.

### URL gambar dari IIIF VHMML

`url_gambar_eksternal` diisi dengan URL gambar statis, bukan hasil unduhan saat impor. Pola yang dipakai:

```text
https://www.vhmml.org/image/READING_ROOM/DS/{DS%20xxxx%20xxxxx}/{DS_xxxx_xxxxx_001r.JPG}/full/,700/0/default.jpg
```

Bagian dalam kurung kurawal pertama adalah kode proyek DREAMSEA dengan spasi dikodekan `%20`. Nama berkas pada bagian kedua diambil dari **label kanvas pertama** manifest IIIF (`sequences[0].canvases[0].label`), sehingga akhirnya bisa berupa `_001r.JPG`, `_001.JPG`, atau `_fp.JPG` sesuai data sumber. Contoh nyata:

```text
https://www.vhmml.org/image/READING_ROOM/DS/DS%200018%2000002//DS_0018_00002_001r.JPG/full/,700/0/default.jpg
```

Kandaga hanya menyimpan dan menampilkan URL ini (`kandagaRealImageUrl()` / `kandagaImageUrl()`); tidak ada permintaan HTTP ke VHMML saat CSV diunggah atau disetujui. Dengan demikian masalah sertifikat SSL di host VHMML tidak memblokir impor. Jika gambar tidak tampil di browser, itu masalah sertifikat sisi klien dan tidak mengubah data katalog.

Untuk menyiapkan CSV, manifest dapat diambil sekali saat kurasi dengan verifikasi SSL dinonaktifkan (`curl -k`), lalu URL hasilnya ditulis ke sheet. Berkas manifest tidak perlu ikut diimpor.

## Gaya terjemahan Kandaga

- Judul naskah tetap memakai judul asli (Sunda/Jawa), bukan diterjemahkan.
- `Deskripsi_Isi` diawali pola **“Naskah ini berisi …”**; `Riwayat_Naskah` memakai pola **“Riwayat akuisisi: …”** bila sumbernya berisi riwayat akuisisi.
- Istilah seragam: `manuscript` → naskah, `copyist` → penyalin, `place of copying` → tempat penyalinan, `writing support` → alas tulis, `palm-leaf` → lontar, `paper` → kertas, `Javanese` → Jawa, `Sundanese` → Sunda, `script` → aksara.
- Angka dan ukuran memakai koma desimal Indonesia, mis. `23,5 x 2,5 cm`.
- Kalimat deskriptif ringkas, informatif, tanpa menambah fakta yang tidak ada di sumber.
- Teks asli DREAMSEA tetap tersimpan di `raw_json` antrean untuk perbandingan.
