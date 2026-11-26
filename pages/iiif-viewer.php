<?php
require_once __DIR__ . '/../config/bootstrap.php';
/**
 * iiif-viewer.php
 */
require_once __DIR__ . '/../config.php';

// 1. Ambil input
$manifestUrl = filter_input(INPUT_GET, 'manifest', FILTER_SANITIZE_URL);
$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

// 2. Ambil data dari database jika ID ada
$judul = "IIIF Viewer";
$kode = "";

if ($id) {
    // Sesuaikan dengan struktur tabel 'katalog' Anda
    $stmt = $conn->prepare("SELECT judul, kode_inventaris FROM katalog WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $judul = $row['judul'];
        $kode = $row['kode_inventaris'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($judul) ?> - Viewer</title>

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/css/bootstrap.min.css?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tify.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-index.css">

    <style>
        html, body { height: 100%; margin: 0; display: flex; flex-direction: column; background-color: #f8fafc; }
        .content-wrapper { flex: 1 0 auto; padding-top: 20px; padding-bottom: 40px; }

        /* Style Judul */
        .viewer-header { margin-bottom: 15px; }
        .viewer-title { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin-bottom: 0; }
        .viewer-code { color: #64748b; font-size: 0.9rem; font-family: monospace; }

        .viewer-box {
            background: #fff;
            border-radius: 15px;
            padding: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            height: 70vh;
            position: relative;
        }

        #tify-canvas { width: 100%; height: 100%; }
        .loading-state { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }
    </style>
<?php include __DIR__ . '/../includes/module-head.php'; ?>
</head>

<body class="kandaga-module">

    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <main class="content-wrapper container">
        <?php if (!empty($manifestUrl)) : ?>

            <div class="viewer-header">
                <h1 class="viewer-title"><?= htmlspecialchars($judul) ?></h1>
                <?php if (!empty($kode)): ?>
                    <div class="viewer-code"><i class="fas fa-barcode me-1"></i> Kode: <?= htmlspecialchars($kode) ?></div>
                <?php endif; ?>
            </div>

            <div class="viewer-box" id="viewer-container">
                <div id="tify-canvas">
                    <div class="loading-state text-muted"><i class="fas fa-spinner fa-spin me-2"></i> Memuat Naskah...</div>
                </div>
            </div>
        <?php else : ?>
            <div class="alert alert-danger text-center mt-5">
                <i class="fas fa-exclamation-triangle me-2"></i> URL Manifest tidak ditemukan.
            </div>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="<?= BASE_URL ?>/assets/js/jquery-3.6.0.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/bootstrap/js/bootstrap.bundle.min.js?v=<?= htmlspecialchars(kandagaVersion(), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script src="<?= BASE_URL ?>/assets/js/tify.js"></script>

    <?php if (!empty($manifestUrl)) : ?>
    <script>
        $(document).ready(function() {
            try {
                new Tify({
                    container: '#tify-canvas',
                    manifestUrl: <?= json_encode($manifestUrl); ?>,
                });
                $('.loading-state').hide();
            } catch (e) {
                console.error("Tify gagal dimuat:", e);
                $('#tify-canvas').html('<div class="alert alert-danger">Viewer gagal memuat manifest.</div>');
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>