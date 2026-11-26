<?php
require_once __DIR__ . '/image-resolver.php';

// Ambil metadata kandidat saja. File lokal yang sudah tidak ada dan placeholder
// akan dilewati di PHP; deskripsi baru diambil setelah kandidat valid ditemukan.
$sqlPilihan = "SELECT id, judul, gambar, url_gambar_eksternal
               FROM katalog
               WHERE (COALESCE(gambar, '') != '' OR COALESCE(url_gambar_eksternal, '') != '')
               ORDER BY RAND()";
$resultPilihan = mysqli_query($conn, $sqlPilihan);
$naskah = null;
$displayImg = null;

if ($resultPilihan) {
    while ($candidate = mysqli_fetch_assoc($resultPilihan)) {
        $candidateImage = kandagaRealImageUrl($candidate);
        if ($candidateImage !== null) {
            $naskah = $candidate;
            $displayImg = $candidateImage;

            // mysqlnd tidak selalu tersedia pada hosting; gunakan bind_result()
            // agar tetap kompatibel saat mengambil deskripsi terpilih.
            $description = '';
            $descriptionStmt = mysqli_prepare($conn, 'SELECT deskripsi_isi FROM katalog WHERE id = ? LIMIT 1');
            if ($descriptionStmt) {
                $candidateId = (int)$candidate['id'];
                mysqli_stmt_bind_param($descriptionStmt, 'i', $candidateId);
                mysqli_stmt_execute($descriptionStmt);
                mysqli_stmt_bind_result($descriptionStmt, $descriptionValue);
                if (mysqli_stmt_fetch($descriptionStmt)) {
                    $description = (string)$descriptionValue;
                }
                mysqli_stmt_close($descriptionStmt);
            }
            $naskah['deskripsi_isi'] = $description;
            break;
        }
    }
}
?>

<div class="embedded-module embedded-naskah-pilihan py-4">
    <div class="row mb-4 align-items-end">
        <div class="col-12 border-left border-primary" style="border-width: 4px !important;">
            <h3 class="ms-3 fw-bold" style="color: #1e293b; margin-bottom: 2px;">Naskah Pilihan</h3>
            <p class="ms-3 text-muted mb-0">Rekomendasi kurasi naskah untuk Anda jelajahi hari ini.</p>
        </div>
    </div>

    <?php
    if ($naskah !== null && $displayImg !== null): ?>
        <a href="detail.php?id=<?= $naskah['id'] ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm overflow-hidden card-pilihan" style="border-radius: 16px; transition: 0.3s;">
                <div class="row no-gutters align-items-stretch">
                    <div class="col-md-4">
                        <div class="featured-image-box w-100">
                            <img src="<?= htmlspecialchars($displayImg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="<?= htmlspecialchars($naskah['judul']) ?>"
                                 class="w-100 h-100" style="object-fit: cover;"
                                 onerror="this.onerror=null;var card=this.closest('a');if(card){card.remove();}">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card-body p-4 p-md-5">
                            <h2 class="fw-bold mb-3" style="color: #1e293b; font-size: 1.75rem; letter-spacing: -0.025em; line-height: 1.2; font-family: 'Inter', sans-serif;">
    <?= htmlspecialchars($naskah['judul']) ?>
</h2>
                            <p class="card-text text-muted mb-4" style="line-height: 1.7; text-align: justify; font-size: 0.95rem;">
                                <?php
                                    $clean_text = strip_tags(html_entity_decode($naskah['deskripsi_isi']));
                                    // Disesuaikan agar deskripsi tidak terlalu memanjang jika sangat panjang
                                    echo (mb_strlen($clean_text) > 450) ? mb_substr($clean_text, 0, 450) . "..." : $clean_text;
                                ?>
                            </p>
                            <span class="fw-bold" style="color: #2563eb;">
                                Baca Selengkapnya <i class="fas fa-arrow-right ms-1"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    <?php else: ?>
        <div class="text-center py-5">
            <p class="text-muted">Belum ada naskah pilihan.</p>
        </div>
    <?php endif; ?>
</div>

<style>
    .card-pilihan {
        display: flex;
        flex-direction: column;
    }
    .card-pilihan:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
    }

    /* Tinggi gambar dan panjang teks dikunci agar card tidak mengikuti
       rasio asli naskah yang berbeda-beda. */
    .card-pilihan .featured-image-box {
        background: #f1f5f9;
        height: 300px;
        overflow: hidden;
    }
    .card-pilihan .featured-image-box img {
        display: block;
        object-fit: cover;
    }
    .card-pilihan h2,
    .card-pilihan .card-text {
        display: -webkit-box;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .card-pilihan h2 { -webkit-line-clamp: 2; }
    .card-pilihan .card-text { -webkit-line-clamp: 4; }

    @media (min-width: 992px) {
        .card-pilihan .featured-image-box { height: 320px; }
    }

    @media (max-width: 767.98px) {
        .card-pilihan .row { flex-direction: column; }
        .card-pilihan .featured-image-box { height: 220px; }
        .card-pilihan .card-body { padding: 1.25rem !important; }
        .card-pilihan h2 {
            font-size: 1.35rem !important;
            line-height: 1.25 !important;
        }
        .card-pilihan .card-text {
            font-size: .9rem !important;
            line-height: 1.6 !important;
            -webkit-line-clamp: 3;
        }
    }
</style>
