<?php
// Pastikan config/database.php sudah terhubung
if (!isset($conn)) { include 'config/database.php'; }

$sql = "SELECT id, judul, gambar, url_gambar_eksternal, kode_inventaris 
        FROM katalog 
        ORDER BY tanggal_input DESC 
        LIMIT 4";
$result = mysqli_query($conn, $sql);
?>

<div class="row mb-4">
    
    <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <?php while ($naskah = mysqli_fetch_assoc($result)): ?>
            <?php
            $img = htmlspecialchars(kandagaImageUrl($naskah), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            ?>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card naskah-baru-card shadow-sm h-100" onclick="location.href='detail.php?id=<?= $naskah['id'] ?>'" style="cursor: pointer; border: none; border-radius: 12px; overflow: hidden;">
                    <div style="height: 180px; overflow: hidden;">
                        <img src="<?= $img ?>" class="card-img-top" alt="<?= htmlspecialchars($naskah['judul']) ?>" 
                             style="width: 100%; height: 100%; object-fit: cover;"
                             <?= kandagaImageFallbackAttribute() ?> >
                    </div>
                    <div class="card-body p-3">
                        <div class="small text-muted mb-1">
                            <i class="fa-solid fa-barcode mr-1"></i><?= htmlspecialchars($naskah['kode_inventaris'] ?? 'NO-CODE') ?>
                        </div>
                        <h6 class="naskah-baru-title" title="<?= htmlspecialchars($naskah['judul']) ?>">
                            <?= htmlspecialchars($naskah['judul']) ?>
                        </h6>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12 text-center p-4">
            <p class="text-muted">Belum ada naskah terbaru yang diunggah.</p>
        </div>
    <?php endif; ?>
</div>
