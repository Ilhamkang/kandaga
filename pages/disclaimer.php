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
    <title>Penafian Komprehensif (Disclaimer) - KANDAGA</title>
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
        
        .alert-notice { background-color: #f0f9ff; border-left: 4px solid #0284c7; border-radius: 8px; padding: 20px; margin-bottom: 30px; }
        .table-matrix { font-size: 0.9rem; margin-top: 15px; margin-bottom: 20px; }
        .table-matrix th { background-color: #f8fafc; color: #1e293b; font-weight: 600; }
    </style>
<?php include __DIR__ . '/../includes/module-head.php'; ?>
</head>

<body class="kandaga-module kandaga-special-page kandaga-disclaimer-page">
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="page-header text-center">
    <div class="container">
        <h2 class="font-weight-bold">Penafian (<i>Disclaimer</i>)</h2>
    </div>
</div>

<div class="container pb-5">
    <div class="wrapper-disclaimer kandaga-prose">
    
    <div class="alert-notice">
        <h5 class="font-weight-bold text-dark mb-2"><i class="fas fa-gavel text-primary me-2"></i> Ketentuan Umum & Konsensus Penggunaan Platform</h5>
        <p class="mb-2"><strong>KANDAGA</strong> dikembangkan sebagai <em>Union Catalog</em> (Katalog Bersama) digital lintas institusi dan perorangan yang berfokus pada penyelamatan data budaya. Dokumen penafian ini merupakan sebuah kesepakatan hukum yang mengikat antara pengelola sistem, kontributor data, dan pengguna publik.</p>
        <p class="mb-0 small text-muted"><i class="fas fa-exclamation-circle text-warning me-1"></i> <strong>Pernyataan Persetujuan Aktif (Express Consent):</strong> Dengan mengakses, menelusuri, atau mendaftarkan diri sebagai kontributor di platform KANDAGA, Anda menyatakan secara sadar bahwa Anda telah membaca, memahami, dan menyetujui seluruh klausul penafian, batas tanggung jawab, serta regulasi hak cipta yang tercantum di bawah ini.</p>
    </div>

    <h4><i class="fas fa-exclamation-triangle"></i> 1. Perlindungan Sistem dari Risiko Penyalahgunaan Pihak Luar</h4>
    <p>Sebagai platform dengan akses terbuka untuk kepentingan akademik, KANDAGA membatasi tanggung jawab hukumnya secara tegas dari tindakan manipulatif pihak ketiga melalui klausul berikut:</p>
    <ul>
        <li><strong>Larangan Klaim Hak Cipta Sepihak (Re-Klaim):</strong> KANDAGA melarang keras pihak luar, baik perorangan maupun korporasi, untuk mengunduh, mengompilasi, atau mengekstrak data dari platform ini untuk kemudian didaftarkan sebagai hak cipta pribadi, paten, atau Hak Kekayaan Intelektual (HKI) komersial di lembaga hukum manapun. Segala bentuk klaim sepihak atas data dari platform ini dinyatakan tidak sah dan batal demi hukum.</li>
        <li><strong>Penyalahgunaan Data untuk Keuntungan Finansial (Komersialisasi):</strong> Seluruh data di KANDAGA disediakan demi pertumbuhan ilmu pengetahuan. Pihak luar yang menyalahgunakan data metadata, hasil transkripsi, atau pratinjau gambar di platform ini untuk diperjualbelikan, dijadikan konten berbayar, atau diintegrasikan ke dalam sistem komersial tanpa izin tertulis dari pemilik sah naskah fisik, akan bertanggung jawab secara mandiri penuh di hadapan hukum pidana maupun perdata tanpa melibatkan pengelola KANDAGA.</li>
        <li><strong>Perlindungan dari Distorsi Teks (Decontextualization):</strong> KANDAGA tidak bertanggung jawab atas interpretasi keliru, pemotongan kutipan, atau pengubahan narasi katalog oleh pihak luar di media lain yang berpotensi memicu disinformasi sejarah, pelanggaran etika, atau sengketa sosial-budaya di masyarakat.</li>
    </ul>

    <h4><i class="fas fa-code"></i> 2. Hak Kekayaan Intelektual Arsitektur dan Kode Sumber Sistem</h4>
    <p>Ketentuan mengenai kepemilikan digital, hak cipta pengembangan, dan cetak biru masa depan infrastruktur KANDAGA diatur sebagai berikut:</p>
    <ul>
        <li><strong>Kepemilikan Mandiri:</strong> Seluruh arsitektur perangkat lunak, rancangan basis data, logika pemrograman (PHP, SQL, JavaScript), desain antarmuka (UI/UX), dan sistem manajemen katalog yang berjalan pada platform KANDAGA merupakan hasil pengembangan mandiri dan menjadi hak cipta penuh dari pengembang platform.</li>
        <li><strong>Komitmen Sumber Terbuka (Open Source Vision):</strong> Sebagai wujud kontribusi terhadap ekosistem <i>digital humanities</i>, kode sumber KANDAGA ke depannya dirancang untuk dirilis secara terbuka kepada publik <i>(open-source system)</i>. Setelah dirilis resmi, publik diperkenankan untuk mempelajari, memodifikasi, dan menerapkan sistem ini demi pelestarian naskah di wilayah lain berdasarkan lisensi publik yang akan ditentukan kemudian.</li>
        <li><strong>Batasan Duplikasi Saat Ini:</strong> Sebelum kode sumber resmi dirilis secara terbuka di repositori publik, pihak luar dilarang keras mengkloning, menggandakan, mendistribusikan ulang, atau memperjualbelikan kode arsitektur sistem KANDAGA untuk kepentingan apa pun tanpa izin tertulis dari pengembang asli.</li>
    </ul>

    <h4><i class="fas fa-user-shield"></i> 3. Batasan Wewenang dan Akuntabilitas Hukum Pengguna</h4>
    <p>Untuk memitigasi risiko hukum dan menjaga akuntabilitas validitas data, tata kelola akses KANDAGA diatur berdasarkan batasan peran berikut:</p>
    
    <h5>A. Admin / Webmaster</h5>
    <p>Admin memiliki wewenang penuh atas pemeliharaan server, enkripsi, optimasi performa sistem, validasi akun kontributor, serta penanganan keluhan hukum terkait data. Tanggung jawab hukum Admin terbatas pada keamanan siber dan kepatuhan regulasi digital platform, serta tidak bertanggung jawab atas pembuktian keaslian fisik manuskrip atau keabsahan kepemilikan naskah di lapangan.</p>

    <h5>B. Kontributor / Kurator Data (Audit Trail System)</h5>
    <p>Kontributor yang telah melewati verifikasi memiliki wewenang untuk mengunggah deskripsi teks, memasukkan kode katalogisasi, dan menyelaraskan metadata naskah. Kontributor bertanggung jawab secara moral dan akademik atas keaslian data deskriptif yang dimasukkannya. KANDAGA menerapkan sistem <i>log audit</i> yang mencatat secara ketat aktivitas setiap akun kontributor. Jika terjadi pelanggaran hak cipta atau pemalsuan data oleh kontributor, tanggung jawab hukum melekat langsung pada pemilik akun tersebut.</p>

    <h5>C. Pengunjung Publik</h5>
    <p>Pengunjung Publik diberikan hak akses non-eksklusif untuk memanfaatkan data sebagai referensi pendidikan atau penelitian non-komersial. Pengunjung dilarang keras melakukan penarikan data massal secara ilegal melalui metode <i>automated scraping, crawling,</i> atau manipulasi URL eksternal yang dapat membebani kinerja server. Pengunjung wajib menyertakan sitasi yang valid ke platform KANDAGA serta instansi asal naskah pada setiap karya ilmiah yang dihasilkan.</p>

    <h4><i class="fas fa-images"></i> 4. Hak Cipta Gambar Digital dan Objek Fisik Manuskrip</h4>
    <p>Aspek kepemilikan visual dan material atas benda cagar budaya digital diatur berdasarkan perlindungan hak cipta asal:</p>
    <ul>
        <li><strong>Hak Cipta Melekat pada Pemegang Asli:</strong> KANDAGA tidak memiliki, tidak membeli, dan tidak mengambil alih hak cipta atas lembaran fisik ataupun foto digital dari manuskrip yang terdaftar. Hak kekayaan intelektual tersebut sepenuhnya tetap berada di tangan lembaga penyimpan resmi, proyek preservasi eksternal, atau kolektor pribadi/keluarga ahli waris yang sah secara hukum.</li>
        <li><strong>Atribusi Proyek Digitalisasi Internasional/Nasional:</strong> Untuk gambar yang bersumber dari proyek digitalisasi pihak ketiga (seperti proyek filantropi <i>DREAMSEA</i>, perpustakaan nasional, dsb.), KANDAGA bertindak sebagai media penyelarasan katalog bersama dan tunduk penuh pada aturan lisensi non-komersial dari proyek asal dengan menampilkan nama institusi/proyek tersebut secara transparan.</li>
        <li><strong>Perlindungan Koleksi Komunitas dan Perorangan:</strong> Gambar naskah yang berasal dari koleksi pribadi atau masyarakat adat ditampilkan semata-mata atas dasar izin darurat untuk pelestarian pengetahuan. Pengguna luar dilarang mereproduksi visual tersebut tanpa adanya persetujuan tertulis terpisah dari keluarga pemilik naskah.</li>
    </ul>

    <h4><i class="fas fa-book-open"></i> 5. Atribusi Sumber Rujukan, Katalog Cetak, dan Bibliografi Sekunder</h4>
    <p>KANDAGA menjunjung tinggi orisinalitas riset dan hak moral para peneliti terdahulu yang telah memetakan sejarah manuskrip:</p>
    <ul>
        <li><strong>Atribusi Karya Bibliografi:</strong> Data penomoran, deskripsi fisik, kolofon, atau catatan sejarah naskah yang disadur dari katalog cetak klasik, buku inventaris lama, disertasi, maupun jurnal ilmiah wajib menyantumkan sumber referensi primernya secara presisi di dalam sistem KANDAGA.</li>
        <li><strong>Ketentuan Sitasi Ganda (Dual Citation Requirement):</strong> Bagi pengguna publik yang mengutip data atau merujuk informasi dari platform ini untuk penulisan karya ilmiah, diwajibkan untuk menerapkan metode sitasi ganda: yaitu wajib mengutip KANDAGA sebagai penyedia platform katalog bersama digital saat ini, sekaligus wajib mengutip katalog cetak/sumber bibliografi primer asli yang tertera pada kartu deskripsi naskah tersebut.</li>
    </ul>

    <h4><i class="fas fa-hand-holding-heart"></i> 6. Asas Kebijakan Non-Komersial dan Transparansi Biaya Pemeliharaan</h4>
    <p>KANDAGA dikelola secara transparan tanpa berorientasi pada keuntungan finansial (Nonprofit Digital Humanities Initiative):</p>
    <ul>
        <li><strong>Pengelolaan Donasi dan Hibah:</strong> KANDAGA tidak menarik bayaran atau royalti dari data kebudayaan. Segala bentuk dukungan finansial, donasi sukarela, maupun dana hibah penelitian dari pihak ketiga akan dicatat secara akuntabel dan dialokasikan 100% hanya untuk pemeliharaan infrastruktur digital (seperti perpanjangan sewa <i>hosting</i> server, pembelian domain, SSL, dan peningkatan sistem keamanan siber).</li>
        <li><strong>Penolakan Paywall:</strong> Seluruh data metadata pencarian di platform ini akan selalu disediakan secara gratis bagi masyarakat luas tanpa ada pembatasan akses berbayar <i>(paywall)</i>.</li>
    </ul>

    <h4><i class="fas fa-envelope-open-text"></i> 7. Protokol Penurunan Konten (Take-Down Request Protocol)</h4>
    <p>KANDAGA menyediakan mekanisme penyelesaian sengketa hak cipta data secara responsif melalu penapisan ketat:</p>
    <p>Apabila Anda adalah pemilik sah secara hukum (lembaga resmi, kolektor, atau ahli waris sah) dari suatu manuskrip yang datanya terindikasi melanggar hak privasi atau hak cipta tertentu di platform ini, Anda dapat mengajukan permohonan penurunan konten <i>(take-down request)</i> dengan mengirimkan email resmi kepada Webmaster. Permohonan wajib menyertakan bukti kepemilikan atau identitas digitalisasi yang sah. Admin KANDAGA akan melakukan verifikasi berkas dan memproses penurunan aset gambar dalam waktu maksimal 2x24 jam kerja, dengan tetap mempertahankan ringkasan metadata teks demi kepentingan pencatatan sejarah nasional <i>(Union Catalog)</i>.</p>

    <h4><i class="fas fa-chart-line"></i> Analitik kunjungan</h4>
    <p>Untuk pengembangan layanan, KANDAGA mencatat halaman yang dibuka, waktu kunjungan, kata kunci pencarian, IP, perangkat dan browser. Rincian tersedia hanya bagi administrator. Pemetaan negara dan kota menggunakan IP melalui ipwho.is apabila geolokasi diaktifkan; hasilnya merupakan perkiraan. IP lokal tidak dikirim ke layanan tersebut.</p>
    <hr class="my-4" style="border-color: #e2e8f0;">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center bg-light p-3 rounded border">
        <div class="text-start mb-2 mb-md-0">
            <p class="mb-0 small text-muted"><strong>Lisensi Konten Katalog KANDAGA:</strong></p>
            <p class="mb-0 small text-muted">Seluruh data teks deskripsi dan metadata di platform ini dilisensikan di bawah standar internasional <a href="https://creativecommons.org/licenses/by-nc-sa/4.0/deed.id" target="_blank" class="fw-bold">Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International (CC BY-NC-SA 4.0)</a>.</p>
        </div>
        <div class="text-md-end text-center flex-shrink-0">
            <span class="badge bg-secondary p-2" style="font-size: 0.8rem;"><i class="fab fa-creative-commons me-1"></i> BY-NC-SA 4.0</span>
        </div>
    </div>

    <p class="text-center text-muted small mt-4 mb-0">
        <em>Dokumen Regulasi Hukum Internal - Platform Katalog Bersama KANDAGA<br>
        Terakhir diperbarui: Juni 2026<br>
        </em>
    </p>

</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="<?= BASE_URL ?>/assets/js/jquery-3.5.1.slim.min.js"></script>
<script src="<?= BASE_URL ?>/assets/bootstrap/js/bootstrap.bundle.min.js"></script>

</body>
</html>
