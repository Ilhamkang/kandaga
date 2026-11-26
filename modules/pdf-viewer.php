<?php
require_once __DIR__ . '/../config/bootstrap.php';
// Menerima input dari URL
$rawFile = isset($_GET['file']) ? trim($_GET['file']) : '';

// Membersihkan prefix "lokasi/" agar URL menjadi murni https://...
$cleanUrl = str_replace('lokasi/', '', $rawFile);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PDF Viewer - KANDAGA</title>
    
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/style-index.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>

    <style>
        /* VAKSINASI CSS: Agar footer tidak rusak */
        footer, .main-footer { all: revert; display: block; width: 100%; background: #1e293b; color: #cbd5e1; padding: 2rem 0; margin-top: auto; }
        
        /* Arsitektur Layout */
        html, body { height: 100%; margin: 0; display: flex; flex-direction: column; background-color: #f8fafc; font-family: 'Inter', sans-serif; }
        .content-wrapper { flex: 1 0 auto; padding-top: 40px; padding-bottom: 40px; }
        
        /* Viewer PDF */
        .pdf-box { background: #fff; border-radius: 15px; padding: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border-top: 5px solid #c5a880; margin-bottom: 20px; }
        #pdf-container { width: 100%; display: flex; flex-direction: column; align-items: center; }
        canvas { border: 1px solid #ccc; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 800px; }
        .loader { font-weight: bold; color: #64748b; margin-bottom: 10px; }
    </style>
<?php include __DIR__ . '/../includes/module-head.php'; ?>
</head>

<body class="kandaga-module">

    <?php include 'navbar.php'; ?>

    <main class="content-wrapper container">
        <?php if (!empty($cleanUrl)) : ?>
            <div class="pdf-box" id="pdf-container">
                <div class="loader" id="status-text">Memuat dokumen dari server...</div>
                <canvas id="pdf-canvas"></canvas>
            </div>
            <div class="text-center">
                <a href="<?= htmlspecialchars($cleanUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary" target="_blank">
                    <i class="fa-solid fa-download"></i> Unduh Asli
                </a>
            </div>
        <?php else : ?>
            <div class="alert alert-warning text-center mt-5">
                <h5>File PDF tidak ditemukan</h5>
                <p>Silakan periksa kembali tautan dokumen Anda.</p>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        <?php if (!empty($cleanUrl)) : ?>
        // 1. Definisikan URL yang sudah melalui proxy
        // Kita menggunakan encodeURIComponent agar URL PDF tidak merusak query string proxy
        const rawUrl = '<?= htmlspecialchars($cleanUrl, ENT_QUOTES, 'UTF-8'); ?>';
        const proxyUrl = 'proxy.php?url=' + encodeURIComponent(rawUrl);

        // 2. Setup PDF.js
        const pdfjsLib = window['pdfjs-dist/build/pdf'];
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';

        // 3. Proses Loading
        pdfjsLib.getDocument(proxyUrl).promise.then(pdf => {
            document.getElementById('status-text').innerText = "Berhasil dimuat";
            
            // Render halaman pertama
            pdf.getPage(1).then(page => {
                const scale = 1.5;
                const viewport = page.getViewport({ scale: scale });
                const canvas = document.getElementById('pdf-canvas');
                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                page.render({ canvasContext: context, viewport: viewport });
            });
        }).catch(err => {
            console.error(err);
            document.getElementById('status-text').innerHTML = 
                '<span class="text-danger">Gagal memuat dokumen.</span>';
        });
        <?php endif; ?>
    </script>
</body>
</html>