# KANDAGA — Katalog Naskah Sunda Kairaga

> Upaya kolaboratif dalam mendigitalisasi dan memetakan kekayaan intelektual naskah Sunda.

## Tentang KANDAGA

Katalog Naskah Sunda Kairaga, disingkat **KANDAGA**, adalah sebuah katalog terpadu (*union catalog*) untuk naskah-naskah Sunda yang dikembangkan secara mandiri oleh **Kairaga Project**. Platform ini dirancang untuk membantu para peneliti, pelajar, dan masyarakat umum memahami serta melestarikan warisan budaya Sunda secara lebih mudah dan interaktif.

**Kandaga** dalam bahasa Sunda berarti “wadah”. Secara filosofis, nama ini menjadi semangat pengembangan katalog: menjadi wadah informasi koleksi naskah-naskah Sunda.

## Asal-usul

- Inisiasi perancangan dimulai pada **2023** atas dukungan program **Fellowship Lingling Wiyadharma** dari **Scaliger Institut di Universitas Leiden, Belanda**, serta **Masyarakat Pernaskahan Nusantara (Manassa)**.
- Tahap awal pengumpulan informasi melibatkan koleksi naskah Sunda dari **Perpustakaan Universitas Leiden**, kemudian dikembangkan dengan data dari berbagai katalog institusi lain secara bertahap.
- Pada tahap awal pengembangan, kode disusun secara menyeluruh (*fullstack*) oleh **Ilham Nurwansah**.
- Entri katalog dan informasi metadata didukung oleh para **kontributor** yang menyediakan informasi pernaskahan untuk kepentingan publik.

## Dukungan

### Dukungan Inisiasi

<img src="assets/images/supporter_01.png" alt="Dukungan inisiasi 1" height="60">
<img src="assets/images/supporter_02.png" alt="Dukungan inisiasi 2" height="60">
<img src="assets/images/supporter_03.png" alt="Dukungan inisiasi 3" height="60">
<img src="assets/images/supporter_07.png" alt="Dukungan inisiasi 4" height="60">

### Dukungan Akses Koleksi

<img src="assets/images/supporter_04.png" alt="Dukungan akses koleksi" height="60">

## Roadmap Pengembangan

| Tahun | Fokus |
|---|---|
| 2023 | Penjajakan informasi dan perancangan sistem arsitektur katalog naskah. |
| 2024 | Pengembangan database, pembuatan aplikasi web (back-end & front-end), dan input data dasar koleksi Leiden. |
| 2025 | Penambahan entri data institusi lain dan peluncuran fitur Indeks Abjad. |
| 2026 | Fitur statistik mendalam, integrasi blog, dan optimalisasi mesin pencarian naskah. |

## Kontak & Pengembang

<img src="assets/images/ilham-leiden.png" alt="Ilham Nurwansah" height="120">

**Ilham Nurwansah**  
📧 [info@kairaga.com](mailto:info@kairaga.com)  
Bandung, Jawa Barat, Indonesia

Iterasi berkelanjutan sejak **Mei 2023** untuk aksesibilitas data budaya yang lebih baik. Versi terpasang saat penulisan README ini: **3.1.66**.

## Dukung Proyek

Proyek ini dirawat dengan semangat pelestarian dan ribuan cangkir kafein. Jika KANDAGA bermanfaat, Anda dapat mendukung pengembangan fitur selanjutnya melalui [Trakteer Kairaga](https://trakteer.id/kairaga/tip).

## Repositori & Rilis

Repositori ini menyimpan source KANDAGA sekaligus menjadi kanal rilis untuk **Update Center**.

- Source: branch `main`
- Kanal rilis: branch `gh-pages`
- Metadata rilis: `https://raw.githubusercontent.com/Ilhamkang/kandaga/gh-pages/version.json`
- Rilis dibuat otomatis oleh GitHub Actions saat tag `v*` di-push.
- Kebijakan dan pelaporan kerentanan: [`SECURITY.md`](SECURITY.md)
- Inventaris serta status dependensi: [`DEPENDENCIES.md`](DEPENDENCIES.md)

Untuk membuat rilis baru:

```bash
# Perbarui judul RELEASE.md ke versi baru dan description di version.json
php tools/sync-version.php

git add RELEASE.md version.json
git commit -m "Rilis vX.Y.Z"
git tag vX.Y.Z
git push origin main --tags
```

Workflow akan membangun ZIP, menghitung checksum, mempublikasikan `version.json` dan paket ke `gh-pages`, lalu membuat GitHub Release.

> Ringkasan proyek pada README ini diambil dari halaman **“Tentang”** di aplikasi KANDAGA.

## Lisensi

Kode sumber KANDAGA dilisensikan di bawah **GNU Affero General Public License v3.0 (AGPL-3.0)**. Lihat file [`LICENSE`](LICENSE) untuk teks lengkapnya.

Lisensi ini berlaku untuk kode aplikasi, bukan untuk data naskah, metadata koleksi, gambar, atau materi pihak ketiga. Pustaka pihak ketiga tetap mengikuti lisensi masing-masing; ringkasannya tersedia di [`THIRD_PARTY_NOTICES.md`](THIRD_PARTY_NOTICES.md).

© 2026 **KAIRAGA.COM**. Seluruh data dikelola secara mandiri oleh **Kairaga Project**.
