<?php
// /asset/kontributor.php

// Perbaikan SQL: Tambahkan 'id' ke dalam SELECT
$q_kontributor = mysqli_query($conn, "
    SELECT id, username, nama, foto, deskripsi
    FROM users
    WHERE role IN ('kontributor', 'admin')
    ORDER BY id ASC
");

$kontributor_list = [];
while ($row = mysqli_fetch_assoc($q_kontributor)) {
    // Pastikan path foto benar
    $path_foto = "assets/images/user/" . (!empty($row['foto']) ? $row['foto'] : 'default-user.jpg');
    if (!file_exists($path_foto)) $path_foto = "assets/images/user/default-user.jpg";

    $row['foto_clean'] = $path_foto;
    $kontributor_list[] = $row;
}
$total_kontributor = count($kontributor_list);
?>

<link rel="stylesheet" href="assets/css/style-kontributor.css">

<div class="embedded-module embedded-kontributor" style="max-width: 960px;">
    <div class="kontributor-section position-relative">
        <div class="text-center mb-5">
            <h4 class="fw-bold" style="color: var(--kandaga-dark-blue);">Kontributor Data</h4>
        </div>

        <?php if ($total_kontributor > 0): ?>
            <div id="carouselKontributor" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php
                    $chunks = array_chunk($kontributor_list, 3);
                    foreach ($chunks as $index => $slide_items):
                    ?>
                        <div class="carousel-item <?= ($index === 0) ? 'active' : ''; ?>">
                            <div class="row row-cols-2 justify-content-center">
                                <?php foreach ($slide_items as $item): ?>
                                    <div class="col-md-4 col-sm-6 mb-4">
                                        <!-- ID sekarang sudah tersedia di $item['id'] -->
                                        <a href="detail-kontributor.php?id=<?= (int)$item['id']; ?>" class="text-decoration-none text-dark">
                                            <div class="kontributor-card p-4 text-center">
                                                <div class="avatar-kontributor-wrapper">
                                                    <img src="<?= $item['foto_clean']; ?>" class="avatar-kontributor-img">
                                                </div>
                                                <div class="nama-kontributor"><?= htmlspecialchars($item['nama']); ?></div>
                                                <small class="text-uppercase" style="color:var(--kandaga-gold); font-weight:700;">Kontributor</small>
                                                <p class="deskripsi-kontributor mt-3">
                                                    <?= !empty($item['deskripsi']) ? htmlspecialchars(substr($item['deskripsi'], 0, 80)) . '...' : 'Kontributor aktif Kandaga.'; ?>
                                                </p>
                                            </div>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_kontributor > 3): ?>
                    <a class="carousel-control-prev-kandaga" href="#carouselKontributor" role="button" data-slide="prev">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>
                    <a class="carousel-control-next-kandaga" href="#carouselKontributor" role="button" data-slide="next">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">Belum ada kontributor.</p>
        <?php endif; ?>
    </div>
</div>
