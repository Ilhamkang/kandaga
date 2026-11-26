# Inventaris Dependensi

Daftar komponen pihak ketiga yang ikut terkemas dalam paket KANDAGA. Audit terakhir: **26 November 2026**.

Lisensi masing-masing tetap berlaku dan tidak diubah oleh lisensi AGPL-3.0 pada kode KANDAGA. Ringkasan lain ada di [`THIRD_PARTY_NOTICES.md`](THIRD_PARTY_NOTICES.md).

| Komponen | Versi | Lisensi | Lokasi | Catatan |
|---|---|---|---|---|
| Bootstrap | 4.6.2 | MIT | `assets/bootstrap/` | Stabil; tidak ada perbaikan keamanan besar yang tertunda. |
| jQuery | 3.6.0 | MIT | `assets/js/jquery-3.6.0.min.js` | Core tidak punya CVE langsung pada versi ini. Awasi plugin di sekitarnya. |
| Font Awesome Free | 7.2.0 | Ikon CC BY 4.0, font SIL OFL 1.1, kode MIT | `assets/fontawesome/` | Sesuai ketentuan Font Awesome Free. |
| TinyMCE | 7.7.2 | GPL-2.0-or-later | `assets/js/tinymce/`, `admin/assets/js/tinymce/` | Di atas patch XSS 7.2.0 (CVE-2024-38356/38357). Tidak perlu tindakan. |
| Chart.js | 4.5.1 | MIT | `assets/js/chart.js` | Versi terkini pada jalur 4.x. |
| Viewer.js | 1.11.3 | MIT | `assets/js/viewer.min.js` | Viewer gambar. |
| TIFY | 0.32.0 | AGPL-3.0 | `assets/js/tify.js` | Viewer dokumen; lisensi selaras dengan proyek. |
| Select2 | 4.1.0-rc.0 | MIT | `assets/js/select2.min.js`, `admin/assets/js/select2.min.js` | Masih release candidate; pertimbangkan pindah ke 4.1.0 final bila kompatibel. |
| SweetAlert2 | v11 (versi minor tidak tercantum di header) | MIT | `admin/assets/js/sweetalert2.min.js`, `assets/js/sweetalert2.min.js` | Perlu dicatat versi eksaknya pada pembaruan berikutnya. |
| dompdf | 3.1.0 | LGPL-2.1-or-later | `assets/js/dompdf/` | **Dihapus.** Tidak dipanggil dari kode PHP mana pun; rilis terbaru 3.1.6 dan sejumlah advisory 2026 menyasar versi `< 3.1.6`. |

Komponen yang ikut dihapus bersama `dompdf`:

| Paket | Versi | Lisensi |
|---|---|---|
| `dompdf/php-font-lib` | 1.0.1 | LGPL-2.1-or-later |
| `dompdf/php-svg-lib` | 1.0.0 | LGPL-2.1-or-later |
| `masterminds/html5` | 2.9.0 | MIT |
| `sabberworm/php-css-parser` | 8.7.0 | MIT |

## Catatan audit

Karena KANDAGA tidak memakai `composer.json`/`package.json`, Dependabot tidak dapat memindai dependency secara otomatis. Pemeriksaan dilakukan manual dengan:

1. mencocokkan versi pada header file atau `composer/installed.json`;
2. mengecek advisory di GitHub Advisory Database / NVD;
3. memastikan komponen yang tidak dipakai benar-benar dikeluarkan dari paket rilis.

Setiap perubahan versi harus dicatat di sini dan di `RELEASE.md` pada rilis bersangkutan.
