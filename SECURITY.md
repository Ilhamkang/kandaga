# Kebijakan Keamanan KANDAGA

KANDAGA memandang laporan kerentanan sebagai kontribusi penting bagi pelestarian dan kepercayaan pengguna. Terima kasih atas bantuan Anda.

## Versi yang didukung

| Versi | Status |
|---|---|
| `3.1.x` | Didukung pada **patch terbaru** yang tersedia di kanal `gh-pages`. |
| `< 3.1` | Tidak lagi menerima perbaikan keamanan. |

Rilis terbaru dapat dicek di `version.json` pada kanal rilis GitHub.

## Cara melapor

**Utama — privat:** gunakan tombol **Report a vulnerability** pada tab *Security* repositori ini. Laporan tidak akan terlihat publik.

**Alternatif:** kirim email ke **info@kairaga.com** dengan subjek `[KANDAGA-SECURITY] <ringkasan singkat>`.

Mohon **jangan** membuka issue publik, diskusi, atau commit dengan detail exploit sebelum perbaikan tersedia.

Sertakan bila memungkinkan:

- versi KANDAGA yang terdampak;
- komponen/berkas yang terdampak;
- langkah reproduksi atau proof-of-concept;
- dampak yang mungkin terjadi;
- saran perbaikan bila Anda punya.

## Komitmen respons

- **3 hari kerja:** konfirmasi penerimaan laporan.
- **7 hari:** triage dan penentuan tingkat keparahan.
- **14 hari:** target patch untuk kerentanan *high/critical* bila memungkinkan; *low/medium* dijadwalkan pada rilis berikutnya.
- **Koordinasi disclosure:** detail dipublikasikan setelah patch rilis tersedia. Pelapor akan diberi kredit bila bersedia.

## Cakupan

Kebijakan ini mencakup **kode aplikasi** di repositori ini. Tidak termasuk:

- data naskah, metadata koleksi, gambar, dan materi yang dikelola Kairaga Project;
- kerentanan pada pustaka pihak ketiga — sebaiknya dilaporkan juga ke upstream, namun laporan ke KANDAGA tetap kami terima untuk koordinasi.

Daftar pustaka yang ikut terkemas ada di [`DEPENDENCIES.md`](DEPENDENCIES.md), dengan ringkasan lisensi di [`THIRD_PARTY_NOTICES.md`](THIRD_PARTY_NOTICES.md).

## Proses rilis keamanan

1. Terima laporan melalui kanal privat; jangan commit detail exploit ke `main`.
2. Siapkan perbaikan pada *private fork* atau branch privat.
3. Uji perbaikan, termasuk alur Update Center dan rollback.
4. Perbarui `RELEASE.md`, `version.json`, dan `CHANGELOG.md` dengan catatan tingkat tinggi tanpa PoC.
5. Commit, tag `vX.Y.Z`, lalu push sesuai prosedur rilis normal.
6. GitHub Actions membangun ZIP, SHA-256, dan kanal `gh-pages`; pengguna memperbarui lewat Update Center.
7. Setelah rilis tersedia, publikasikan advisory dan kredit pelapor.

Karena repositori ini public dan berlisensi AGPL-3.0, perubahan kode akhirnya tetap transparan — yang dijaga adalah **waktu**, bukan kerahasiaan permanen.

## Pengerasan deployment

Operator disarankan untuk:

- tidak pernah menyimpan kredensial di `config/database.php`; gunakan `config/database.local.php` atau variabel `KANDAGA_DB_*`;
- memastikan `/.git`, `/config/*`, dan folder upload tidak bisa diakses/dieksekusi dari web;
- menjaga permission file upload tetap non-eksekutabel;
- membatasi akses `/admin` hanya untuk akun yang berwenang dan memakai rate-limit bila tersedia;
- segera menerapkan paket rilis yang menyertakan perbaikan keamanan.

## Catatan pengaktifan di GitHub

Fitur **private vulnerability reporting** dan **branch protection** diaktifkan dari GitHub UI:

```text
Settings → Security → Security policy / Private vulnerability reporting
Settings → Branches → Branch protection rules untuk main
```

File ini berisi kebijakannya; pengaktifan fitur perlu dilakukan oleh maintainer repo.
