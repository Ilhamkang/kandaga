<?php
require_once __DIR__ . '/../config/bootstrap.php'; 
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once __DIR__ . '/../config/database.php'; 
require_once __DIR__ . '/../includes/version.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Ketentuan Penggunaan dan Penafian - KANDAGA</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-tentang.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">

    <style>
        body { background-color: #f8fafc; color: #334155; font-family: 'Inter', sans-serif; line-height: 1.6; }
        .page-header { background: white; padding: 50px 0; border-bottom: 1px solid #e2e8f0; margin-bottom: 40px; }
        .wrapper-disclaimer { background: white; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 45px; }
        
        h4 { color: #0f172a; font-weight: 700; margin-top: 35px; margin-bottom: 15px; display: flex; align-items: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px; }
        h4 i { color: #0284c7; margin-right: 12px; font-size: 1.3rem; }
        
        h5 { color: #1e293b; font-weight: 600; margin-top: 20px; margin-bottom: 10px; }
        p, li { color: #475569; font-size: 0.95rem; text-align: justify; }
        ul, ol { padding-left: 22px; }
        li { margin-bottom: 10px; }
        .wrapper-disclaimer hr { border-color: #e2e8f0; }
        
        .alert-notice { background-color: #f0f9ff; border-left: 4px solid #0284c7; border-radius: 8px; padding: 20px; margin-bottom: 30px; }
        .table-matrix { font-size: 0.9rem; margin-top: 15px; margin-bottom: 20px; }
        .table-matrix th { background-color: #f8fafc; color: #1e293b; font-weight: 600; }
        .license-badge { font-size: 0.8rem; }
    </style>
<?php include __DIR__ . '/../includes/module-head.php'; ?>
</head>

<body class="kandaga-module kandaga-special-page kandaga-disclaimer-page">
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="page-header text-center">
    <div class="container">
        <h2 class="font-weight-bold">Ketentuan Penggunaan &amp; Penafian</h2>
    </div>
</div>

<div class="container pb-5">
    <div class="wrapper-disclaimer kandaga-prose">
    
    <div class="alert-notice">
        <h5 class="font-weight-bold text-dark mb-2"><i class="fas fa-gavel text-primary me-2"></i> Ketentuan Umum Penggunaan Platform</h5>
        <p class="mb-2"><strong>KANDAGA</strong> adalah katalog bersama digital lintas institusi dan perorangan yang berfokus pada pendokumentasian naskah Sunda. Dokumen ini menjelaskan ketentuan penggunaan, batas tanggung jawab, serta pengelolaan hak atas data dan kode aplikasi.</p>
        <p class="mb-0 small text-muted"><i class="fas fa-exclamation-circle text-warning me-1"></i> <strong>Cara membaca ketentuan ini:</strong> Dengan mengakses atau menggunakan layanan KANDAGA, Anda dianggap telah membaca ketentuan di bawah ini. Jika tidak menyetujuinya, mohon tidak menggunakan data atau layanan yang disediakan.</p>
    </div>

    <h4><i class="fas fa-exclamation-triangle"></i> 1. Batas Tanggung Jawab atas Penyalahgunaan Data</h4>
    <p>Sebagai platform terbuka untuk kebutuhan akademik dan publik, KANDAGA menjelaskan batas tanggung jawabnya terhadap penyalahgunaan data oleh pihak ketiga melalui ketentuan berikut:</p>
    <ul>
        <li><strong>Larangan Klaim Hak Cipta Sepihak:</strong> Data katalog tidak boleh didaftarkan sebagai hak cipta, paten, atau Hak Kekayaan Intelektual (HKI) komersial atas nama pihak lain tanpa dasar hukum dan izin dari pemilik sahnya. Klaim sepihak seperti itu tidak kami akui dan dapat kami sanggah sesuai hukum yang berlaku.</li>
        <li><strong>Penyalahgunaan Data untuk Keuntungan Finansial:</strong> Seluruh data di KANDAGA disediakan demi pertumbuhan ilmu pengetahuan. Pihak yang menggunakan metadata, hasil transkripsi, atau pratinjau gambar untuk diperjualbelikan, dijadikan konten berbayar, atau diintegrasikan ke dalam sistem komersial tanpa izin pemilik sah naskah fisik, bertanggung jawab sendiri sesuai hukum pidana maupun perdata yang berlaku.</li>
        <li><strong>Perlindungan dari Distorsi Teks:</strong> KANDAGA tidak bertanggung jawab atas interpretasi keliru, pemotongan kutipan, atau pengubahan narasi katalog oleh pihak lain di media lain yang dapat memicu disinformasi sejarah, pelanggaran etika, atau sengketa sosial-budaya.</li>
    </ul>

    <h4><i class="fas fa-code"></i> 2. Hak Kekayaan Intelektual dan Keterbukaan Kode Sumber</h4>
    <p>KANDAGA membedakan secara tegas antara kode aplikasi dan data yang dikelolanya:</p>
    <ul>
        <li><strong>Kepemilikan Kode:</strong> Arsitektur perangkat lunak, rancangan basis data, logika pemrograman (PHP, SQL, JavaScript), dan desain antarmuka dikembangkan secara mandiri oleh Kairaga Project dan dipublikasikan sebagai perangkat lunak sumber terbuka.</li>
        <li><strong>Lisensi Kode:</strong> Kode sumber tersedia di <a href="https://github.com/Ilhamkang/kandaga" target="_blank" rel="noopener noreferrer">github.com/Ilhamkang/kandaga</a> di bawah lisensi <a href="https://github.com/Ilhamkang/kandaga/blob/main/LICENSE" target="_blank" rel="noopener noreferrer">GNU AGPL-3.0</a>. Publik boleh melihat, mempelajari, memodifikasi, dan mendistribusikan kode selama mematuhi ketentuan AGPL-3.0, termasuk kewajiban menyediakan kode sumber bagi pengguna yang berinteraksi dengan versi yang dimodifikasi melalui jaringan.</li>
        <li><strong>Batasan Cakupan:</strong> Lisensi kode tidak meliputi data naskah, metadata koleksi, gambar, atau materi pihak ketiga. Metadata dan deskripsi yang disusun KANDAGA mengikuti CC BY-NC-SA 4.0; gambar tetap berada di bawah hak pemilik atau institusi asal; dan pustaka pihak ketiga mengikuti lisensinya masing-masing sebagaimana tercantum dalam <a href="https://github.com/Ilhamkang/kandaga/blob/main/THIRD_PARTY_NOTICES.md" target="_blank" rel="noopener noreferrer">pemberitahuan pihak ketiga</a>.</li>
        <li><strong>Pelaporan Kerentanan:</strong> Temuan keamanan pada kode dapat disampaikan secara privat melalui kanal yang dijelaskan dalam <a href="https://github.com/Ilhamkang/kandaga/blob/main/SECURITY.md" target="_blank" rel="noopener noreferrer">kebijakan keamanan</a> KANDAGA.</li>
    </ul>

    <h4><i class="fas fa-user-shield"></i> 3. Batasan Wewenang dan Akuntabilitas Pengguna</h4>
    <p>Untuk menjaga akuntabilitas data, tata kelola akses KANDAGA diatur berdasarkan peran berikut:</p>
    
    <h5>A. Admin / Webmaster</h5>
    <p>Admin memiliki wewenang atas pemeliharaan server, keamanan sistem, optimasi performa, validasi akun kontributor, serta penanganan keluhan terkait data. Tanggung jawab Admin mencakup keamanan siber dan kepatuhan regulasi digital platform, tanpa bertindak sebagai penjamin keaslian fisik manuskrip atau keabsahan kepemilikan naskah di lapangan.</p>

    <h5>B. Kontributor / Kurator Data</h5>
    <p>Kontributor yang telah diverifikasi memiliki wewenang mengunggah deskripsi teks, memasukkan kode katalogisasi, dan menyelaraskan metadata naskah. Kontributor bertanggung jawab secara akademik atas keaslian data yang dimasukkannya. KANDAGA menyimpan log audit atas aktivitas setiap akun kontributor. Jika terjadi pelanggaran hak cipta atau pemalsuan data oleh kontributor, tanggung jawab melekat pada pemilik akun tersebut.</p>

    <h5>C. Pengunjung Publik</h5>
    <p>Pengunjung Publik memperoleh hak akses non-eksklusif untuk memanfaatkan data sebagai referensi pendidikan atau penelitian non-komersial. Pengunjung dilarang melakukan penarikan data massal melalui <i>automated scraping, crawling,</i> atau manipulasi URL yang dapat membebani server. Setiap karya ilmiah yang dihasilkan diharapkan menyertakan sitasi KANDAGA dan instansi asal naskah.</p>

    <h4><i class="fas fa-images"></i> 4. Hak Cipta Gambar Digital dan Objek Fisik Manuskrip</h4>
    <p>Aspek kepemilikan visual dan material atas benda cagar budaya digital diatur berdasarkan hak cipta asal:</p>
    <ul>
        <li><strong>Hak Cipta Melekat pada Pemegang Asli:</strong> KANDAGA tidak memiliki, membeli, atau mengambil alih hak cipta atas lembaran fisik maupun foto digital naskah yang terdaftar. Hak tersebut tetap berada di tangan lembaga penyimpan resmi, proyek preservasi eksternal, atau kolektor pribadi/ahli waris yang sah.</li>
        <li><strong>Atribusi Proyek Digitalisasi:</strong> Untuk gambar yang bersumber dari proyek pihak ketiga seperti <i>DREAMSEA</i> atau perpustakaan nasional, KANDAGA bertindak sebagai media penyelarasan katalog bersama dan tunduk pada aturan lisensi proyek asal dengan menampilkan nama institusi atau proyek tersebut secara transparan.</li>
        <li><strong>Perlindungan Koleksi Komunitas dan Perorangan:</strong> Gambar naskah dari koleksi pribadi atau masyarakat adat ditampilkan atas dasar izin untuk pelestarian pengetahuan. Pengguna luar tidak boleh mereproduksi visual tersebut tanpa persetujuan tertulis terpisah dari pemilik atau keluarga ahli waris.</li>
    </ul>

    <h4><i class="fas fa-book-open"></i> 5. Atribusi Sumber Rujukan, Katalog Cetak, dan Bibliografi Sekunder</h4>
    <p>KANDAGA menjunjung tinggi orisinalitas riset dan hak moral peneliti terdahulu yang telah memetakan sejarah manuskrip:</p>
    <ul>
        <li><strong>Atribusi Karya Bibliografi:</strong> Data penomoran, deskripsi fisik, kolofon, atau catatan sejarah naskah yang disadur dari katalog cetak klasik, buku inventaris lama, disertasi, maupun jurnal ilmiah wajib mencantumkan sumber referensi primernya secara presisi di dalam sistem KANDAGA.</li>
        <li><strong>Sitasi Ganda:</strong> Pengguna yang mengutip data dari platform ini untuk karya ilmiah dianjurkan menerapkan sitasi ganda: mengutip KANDAGA sebagai penyedia katalog bersama digital, sekaligus sumber bibliografi primer yang tertera pada kartu deskripsi naskah.</li>
    </ul>

    <h4><i class="fas fa-hand-holding-heart"></i> 6. Kebijakan Non-Komersial dan Transparansi Pemeliharaan</h4>
    <p>KANDAGA dikelola tanpa orientasi keuntungan finansial:</p>
    <ul>
        <li><strong>Donasi dan Hibah:</strong> KANDAGA tidak menarik bayaran atau royalti dari data kebudayaan. Dukungan finansial, donasi sukarela, maupun dana hibah dicatat secara akuntabel dan digunakan untuk pemeliharaan infrastruktur digital, seperti hosting, domain, SSL, dan keamanan siber.</li>
        <li><strong>Tanpa Paywall:</strong> Seluruh metadata pencarian disediakan secara gratis bagi publik tanpa pembatasan akses berbayar.</li>
    </ul>

    <h4><i class="fas fa-envelope-open-text"></i> 7. Protokol Penurunan Konten</h4>
    <p>KANDAGA menyediakan mekanisme penanganan sengketa hak cipta data melalui verifikasi permohonan:</p>
    <p>Apabila Anda adalah pemilik sah secara hukum—lembaga resmi, kolektor, atau ahli waris—atas suatu manuskrip yang datanya diduga melanggar hak privasi atau hak cipta, Anda dapat mengajukan permohonan penurunan konten <i>(take-down request)</i> melalui email resmi kepada Webmaster. Permohonan wajib menyertakan bukti kepemilikan atau identitas digitalisasi yang sah. Admin akan memeriksa berkas dan memproses penurunan aset gambar dalam waktu maksimal dua hari kerja, dengan tetap mempertahankan ringkasan metadata teks demi pencatatan sejarah katalog bersama.</p>

    <h4><i class="fas fa-chart-line"></i> Analitik Kunjungan</h4>
    <p>Untuk pengembangan layanan, KANDAGA mencatat halaman yang dibuka, waktu kunjungan, kata kunci pencarian, IP, perangkat, dan browser. Rincian hanya tersedia bagi administrator. Pemetaan negara dan kota menggunakan ipwho.is apabila geolokasi diaktifkan; hasilnya bersifat perkiraan. IP lokal tidak dikirim ke layanan tersebut.</p>
    <hr class="my-4">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center bg-light p-3 rounded border">
        <div class="text-left mb-2 mb-md-0">
            <p class="mb-0 small text-muted"><strong>Lisensi data KANDAGA:</strong></p>
            <p class="mb-0 small text-muted">Deskripsi dan metadata yang disusun KANDAGA dilisensikan di bawah <a href="https://creativecommons.org/licenses/by-nc-sa/4.0/deed.id" target="_blank" rel="noopener noreferrer" class="fw-bold">Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International (CC BY-NC-SA 4.0)</a>. Hak atas gambar naskah tetap berada pada pemilik atau institusi asal.</p>
        </div>
        <div class="text-md-right text-center flex-shrink-0">
            <span class="badge bg-secondary p-2 license-badge"><i class="fab fa-creative-commons me-1"></i> BY-NC-SA 4.0</span>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center bg-light p-3 rounded border mt-2">
        <div class="text-left mb-2 mb-md-0">
            <p class="mb-0 small text-muted"><strong>Lisensi kode aplikasi:</strong></p>
            <p class="mb-0 small text-muted">Kode KANDAGA dirilis di bawah <a href="https://github.com/Ilhamkang/kandaga/blob/main/LICENSE" target="_blank" rel="noopener noreferrer" class="fw-bold">GNU AGPL-3.0</a>. Rincian komponen pihak ketiga tersedia di repositori proyek.</p>
        </div>
        <div class="text-md-right text-center flex-shrink-0">
            <span class="badge bg-primary p-2 license-badge"><i class="fas fa-code me-1"></i> AGPL-3.0</span>
        </div>
    </div>

    <p class="text-center text-muted small mt-4 mb-0">
        <em>Ketentuan Penggunaan dan Penafian — Katalog Bersama KANDAGA<br>
        Terakhir diperbarui: November 2026<br>
        Versi aplikasi: <?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>
        </em>
    </p>

</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="<?= BASE_URL ?>/assets/js/jquery-3.5.1.slim.min.js"></script>
<script src="<?= BASE_URL ?>/assets/bootstrap/js/bootstrap.bundle.min.js"></script>

</body>
</html>
