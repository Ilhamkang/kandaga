# Importer Web: format CSV

Panduan ini mulai dari **unggah CSV** di Goah → Metadata Importer → **Importer Web**. Formatnya dipakai untuk CSV dari sumber web mana pun, bukan hanya satu proyek; contoh di bawah mencakup data bertema DREAMSEA/VHMML dan Lektur Kemenag. Antrean Importer Web terpisah dari Importer Katalog Cetak.

## Alur di aplikasi

1. Pilih CSV UTF-8 (koma, maksimal 10 MB / 5.000 entri) pada langkah **Unggah CSV**, lalu isi nama sumber/batch.
2. Klik **Baca CSV** untuk pratinjau validasi. Perbaiki error sebelum melanjutkan.
3. Klik **Simpan ke antrean**. Pada tahap ini belum ada data katalog yang berubah.
4. Buka langkah **Moderasi** dan periksa setiap entri: lokasi penyimpanan sekarang, padanan bahasa/aksara/alas tulis, kandidat duplikat, struktur, identitas, serta **riwayat lokasi historis**.
5. Pilih **Buat entri baru**, **Merge**, **Tunda**, atau **Lewati**. Centang konfirmasi wajib yang muncul; keputusan tanpa konfirmasi ditolak.
6. Setiap keputusan tercatat di `katalog_import_audit` dan `user_logs`. Batch `web` tidak muncul pada daftar batch `catalog`, dan sebaliknya.
7. Batch yang belum diproses dapat dihapus setelah konfirmasi. Penghapusan ditolak bila ada baris sudah diputuskan, identitas tertaut, atau catatan audit.

## Kolom CSV

Wajib: identitas sumber (`msid`, `ds_id`, atau pasangan `Identifier_Scheme`/`Identifier_Value`), `Judul`, dan `Lokasi_Target`. Kolom lain opsional, tetapi sebaiknya lengkap. Nama kolom berikut juga dipakai oleh Katalog Cetak; Importer Web menambah kolom identitas web dan struktur.

| Kolom | Tujuan |
|---|---|
| `msid`, `ds_id` | Identitas DREAMSEA. `msid` numerik dan `ds_id` berbentuk `DS 0000 00000` memakai skema `dreamsea-msid` / `dreamsea-ds`; `ds_id` lain memakai `web-source`. |
| `Identifier_Scheme`, `Identifier_Value`, `Identifier_Source` | Identitas utama untuk sumber non-DREAMSEA, mis. `lektur-kode` + `LKK_CRB2017_BNP001`. Skema + nilai dipakai untuk deduplikasi; jangan memakai skema `dreamsea-*` untuk sumber lain. |
| `record_type`, `parent_msid`, `unit_label`, `unit_order`, `structure_notes` | `katalog_structure`. Untuk sumber non-DREAMSEA, `parent_msid` boleh kosong; induk Kandaga dapat dipilih saat moderasi. |
| `Judul`, `Kode_Inventaris`, `Lokasi_Target`, `Kata_Kunci`, `Penyedia_Akses` | `katalog`. Judul asli tidak diterjemahkan. |
| `Bahasa_List`, `Aksara_List`, `Alas_Tulis_List` | pivot `naskah_bahasa`, `naskah_aksara`, `naskah_alas_tulis`. Master harus sudah ada. |
| `Deskripsi_Fisik`, `Deskripsi_Isi`, `Riwayat_Naskah` | `katalog`. Kodikologi/kondisi masuk deskripsi fisik; makna teks masuk deskripsi isi; provenansi masuk riwayat naskah. |
| `URL_Web`, `URL_Repositori`, `IIIF_Manifest`, `URL_PDF`, `url_gambar_eksternal` | `katalog`. Harus URL absolut `http(s)`, tanpa username/password di URL. |
| `Location_Kind` | `asal`, `ditemukan`, atau `sebelumnya` pada `katalog_location_history`. |
| `Location_Place`, `Location_District`, `Location_Province`, `Location_Country` | tempat/koleksi, kabupaten/kota, provinsi, negara pada riwayat lokasi. |
| `Location_Period`, `Location_Certainty`, `Location_Source` | periode, keyakinan, dan sumber riwayat lokasi. |
| `Bibliografi_ID` | `naskah_bibliografi` (ID `tb_bibliografi`, dipisah `;`; opsional). |

Aturan penting:

- Tidak ada master yang dibuat otomatis. Istilah yang belum ada di `tb_bahasa`, `tb_aksara`, `tb_alas_tulis`, atau `tb_lokasi` ditandai dan dipilih manual saat moderasi.
- `Lokasi_Target` adalah **lokasi penyimpanan sekarang** dan harus memakai master yang ada. Lokasi historis tidak otomatis menjadi `Lokasi_Target`.
- Untuk data DREAMSEA, `City (Province)` seperti `Indramayu (Jawa Barat)` ditulis sebagai `Location_District=Indramayu` dan `Location_Province=Jawa Barat`. `Place of copying` masuk `Location_Place`; nama kabupaten di akhir Place of copying dihapus agar tidak dobel. Engine juga menerima bentuk lama `Location_Province=Indramayu (Jawa Barat)` dan memecahnya saat membaca CSV.
- Untuk data Lektur Kemenag, keterangan lokasi biasanya tertanam dalam narasi deskripsi. Pindahkan ke `Location_Place` / `Location_District` / `Location_Province` / `Location_Country` dan tetapkan `Lokasi_Target` secara manual saat moderasi.
- `Location_Certainty`: `pasti`, `kemungkinan`, `dugaan`, atau `belum_diketahui`.
- Kolom teks `katalog` masih `latin1`. Engine melakukan preflight charset dan menolak karakter yang tidak didukung, bukan menggantinya diam-diam.
- Riwayat lokasi kini **selalu tersedia sebagai field** di panel moderasi Importer Web maupun Katalog Cetak. Perubahan pada keputusan **Tunda/Lewati** disimpan ke draf antrean dan audit; hanya **Buat entri baru/Merge** yang menulisnya ke `katalog_location_history`. Saat **Merge**, riwayat lama tidak ditimpa.

## URL gambar eksternal

`url_gambar_eksternal` berisi URL gambar statis yang sudah valid, bukan hasil unduhan saat impor. Kandaga hanya menyimpan dan menampilkan URL ini; tidak ada permintaan HTTP ke host gambar ketika CSV dibaca atau disetujui. Panel moderasi menampilkan pratinjau dengan `referrerpolicy="no-referrer"` dan menyediakan tautan langsung bila host memblokir hotlink.

Pola yang umum ditemukan:

```text
# VHMML / DREAMSEA (IIIF image service)
https://www.vhmml.org/image/READING_ROOM/DS/DS%200018%2000002//DS_0018_00002_001r.JPG/full/,700/0/default.jpg

# Lektur Kemenag (galeri berkas statis)
https://lektur.kemenag.go.id/manuskrip/swi-upload/700px/LKK_CRB2017_BNP01_001.jpg
https://lektur.kemenag.go.id/manuskrip/swi-upload/myThumbs/LKK_CMS2013_ALF_18_001.jpg
```

Perhatikan bahwa nama berkas tidak selalu sama dengan kode entri, dan polanya berubah antar tahun/koleksi:

```text
LKK_CIAMIS2013_MLM01 → LKK_CMS2013_ALF_18_001.jpg
LKK_CRB2017_BNP001   → LKK_CRB2017_BNP01_001.jpg
LKK_BANTEN2016_KHD001 → LKK_BANTEN2016_01_001.jpg
LKK_CIREBON2015_AMB01 → LKK_Cirebon2015_AMB01_001.jpg
LKK_CRB2014_BMB001   → LKK_CRB2014_BMB001_002.jpg
```

Salin URL yang memang tercantum pada galeri halaman tersebut; jangan merangkai URL dari kode inventaris atau mengira halaman pertama selalu bernomor `001`. Beberapa halaman tidak memuat galeri gambar sama sekali; pada kasus itu biarkan `url_gambar_eksternal` kosong dan catat keterangannya di `Riwayat_Naskah`/catatan moderasi. Folder `700px` dipakai untuk ukuran penuh, `myThumbs` untuk thumbnail; keduanya diterima selama absolut `http(s)`.

## Contoh CSV ringkas

Berikut contoh dengan kolom inti. Kolom lain boleh ditambahkan sesuai tabel di atas; berkas lengkap ada di `import-examples/dreamsea-normalized-template.csv` (nama berkas warisan, formatnya berlaku untuk semua sumber web).

```csv
msid,ds_id,Identifier_Scheme,Identifier_Value,Judul,Kode_Inventaris,Lokasi_Target,Bahasa_List,Aksara_List,Alas_Tulis_List,Deskripsi_Fisik,Deskripsi_Isi,Riwayat_Naskah,Penyedia_Akses,URL_Web,url_gambar_eksternal,Location_Kind,Location_Place,Location_District,Location_Province,Location_Country,Location_Certainty
623,"DS 0027 00001",,,Satus Jawokan Dermayu,"DS 0027 00001",Perpustakaan Nasional Republik Indonesia,Jawa,Jawa,Lontar,74 halaman; kertas Eropa,Naskah ini berisi 53 jawokan/mantra.,Naskah disalin oleh Ki Tarka Sutarahardja pada 2018.,DREAMSEA,https://www.hmmlcloud.org/dreamsea/detail.php?msid=623,"https://www.vhmml.org/image/READING_ROOM/DS/DS%200027%2000001//DS_0027_00001_001r.JPG/full/,700/0/default.jpg",asal,Sanggar Aksara Jawa Kidang Pananjung,Cikedung,Indramayu,Indonesia,pasti
,,lektur-kode,LKK_CRB2017_BNP001,Kitab Tauhid,LKK_CRB2017_BNP001,Perorangan,Arab,Arab,Kertas,147 halaman; kertas Eropa,Naskah ini menjelaskan ilmu kalam.,Disimpan di Buntet Pesantren Cirebon.,Lektur Kemenag,https://lektur.kemenag.go.id/manuskrip/web/koleksi-detail/lkk-crb2017-bnp001.html,https://lektur.kemenag.go.id/manuskrip/swi-upload/700px/LKK_CRB2017_BNP01_001.jpg,asal,Buntet Pesantren,Astanajapura,Cirebon,Indonesia,pasti
```

Baris pertama memakai `msid`/`ds_id`; baris kedua memakai `Identifier_Scheme`/`Identifier_Value`. Keduanya menghasilkan `source_key` unik untuk deduplikasi.

## Gaya terjemahan Kandaga

- Judul naskah tetap memakai judul asli (Sunda/Jawa/Arab), bukan diterjemahkan.
- `Deskripsi_Isi` diawali pola **“Naskah ini berisi …”**; `Riwayat_Naskah` memakai pola **“Riwayat akuisisi: …”** bila sumbernya berisi riwayat akuisisi.
- Istilah seragam: `manuscript` → naskah, `copyist` → penyalin, `place of copying` → tempat penyalinan, `writing support` → alas tulis, `palm-leaf` → lontar, `paper` → kertas, `Javanese` → Jawa, `Sundanese` → Sunda, `script` → aksara.
- Angka dan ukuran memakai koma desimal Indonesia, mis. `23,5 x 2,5 cm`.
- Kalimat deskriptif ringkas, informatif, tanpa menambah fakta yang tidak ada di sumber.
- Teks asli sumber tetap tersimpan di `raw_json` antrean untuk perbandingan saat moderasi.
