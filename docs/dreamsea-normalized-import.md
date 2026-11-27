# Impor DREAMSEA terstruktur

Engine lama (`Judul,Kode_Inventaris,…` → `INSERT katalog`) sudah tidak dipakai. Gunakan format berikut agar metadata DREAMSEA masuk ke struktur Kandaga yang baru.

## Alur

1. Ekstrak metadata dari `https://hmmlcloud.org/dreamsea/detail.php?msid=<id>`.
2. Terjemahkan teks ke bahasa Indonesia dengan gaya Kandaga (lihat bawah).
3. Susun CSV UTF-8 sesuai `import-examples/dreamsea-normalized-template.csv`.
4. Buka Goah → Metadata Importer → **Dreamsea Importer (terstruktur)**.
5. Unggah CSV → pratinjau → **Simpan ke antrean**. Tidak ada data yang berubah pada langkah ini.
6. Moderasi per entri: pilih lokasi penyimpanan, padanan bahasa/aksara/alas tulis, periksa kandidat duplikat, lalu **Buat entri baru**, **Merge**, **Tunda**, atau **Lewati**.
7. Setiap keputusan tercatat di `katalog_import_audit` dan `user_logs`.

## Kolom CSV

Wajib: `msid` atau `ds_id`, `Judul`, `Lokasi_Target`. Kolom lain opsional tetapi disarankan lengkap.

| Kolom | Tujuan |
|---|---|
| `msid`, `ds_id` | Identitas DREAMSEA; kunci dedup `dreamsea-msid` / `dreamsea-ds` di `katalog_identifiers` |
| `record_type`, `parent_msid`, `unit_label`, `unit_order`, `structure_notes` | `katalog_structure` |
| `Judul`, `Kode_Inventaris`, `Lokasi_Target`, `Kata_Kunci`, `Penyedia_Akses` | `katalog` |
| `Bahasa_List`, `Aksara_List`, `Alas_Tulis_List` | pivot `naskah_bahasa`, `naskah_aksara`, `naskah_alas_tulis` (master harus sudah ada) |
| `Deskripsi_Fisik`, `Deskripsi_Isi`, `Riwayat_Naskah` | `katalog` |
| `URL_Web`, `URL_Repositori`, `IIIF_Manifest`, `URL_PDF`, `url_gambar_eksternal` | `katalog`; harus http(s) absolut |
| `Identifier_Scheme`, `Identifier_Value`, `Identifier_Source` | identifier tambahan, mis. shelfmark |
| `Location_Kind`, `Location_Place`, `Location_District`, `Location_Province`, `Location_Country`, `Location_Period`, `Location_Certainty`, `Location_Source` | `katalog_location_history` |
| `Bibliografi_ID` | `naskah_bibliografi` (ID `tb_bibliografi`, dipisah `;`; opsional) |

Aturan penting:

- Tidak ada master yang dibuat otomatis. Jika istilah belum ada di `tb_bahasa`, `tb_aksara`, `tb_alas_tulis`, atau `tb_lokasi`, entri ditandai dan harus dipilih manual saat moderasi.
- `Lokasi_Target` adalah **lokasi penyimpanan sekarang**, bukan lokasi historis. Lokasi historis masuk `Location_*`.
- `Location_Certainty`: `pasti`, `kemungkinan`, `dugaan`, atau `belum_diketahui`.
- Kolom teks `katalog` masih `latin1`; engine melakukan preflight charset. Karakter yang tidak didukung ditolak sebelum penulisan, bukan diganti diam-diam.

## Gaya terjemahan Kandaga

- Judul naskah tetap memakai judul asli (Sunda/Jawa), bukan diterjemahkan.
- `Deskripsi_Isi` diawali pola **“Naskah ini berisi …”**; `Riwayat_Naskah` memakai pola **“Riwayat akuisisi: …”** bila sumbernya berisi riwayat akuisisi.
- Istilah seragam: `manuscript` → naskah, `copyist` → penyalin, `place of copying` → tempat penyalinan, `writing support` → alas tulis, `palm-leaf` → lontar, `paper` → kertas, `Javanese` → Jawa, `Sundanese` → Sunda, `script` → aksara.
- Angka dan ukuran memakai koma desimal Indonesia, mis. `23,5 x 2,5 cm`.
- Kalimat deskriptif ringkas, informatif, tanpa menambah fakta yang tidak ada di sumber.
- Teks asli DREAMSEA tetap tersimpan di `raw_json` antrean untuk perbandingan.
