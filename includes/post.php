<?php
// Modul: Menampilkan 3 artikel terbaru
// Query 3 artikel terakhir
$q_terbaru = mysqli_query($conn, "SELECT * FROM tb_kabar WHERE status = 'published' ORDER BY id_kabar DESC LIMIT 3");
?>

<div class="kandaga-latest-posts my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="font-weight-bold" style="color: #0f2042;">Kabar Terbaru</h4>
        <a href="kabar/" class="text-decoration-none" style="color: #c5a880; font-weight: 600;">Lihat Semua &rarr;</a>
    </div>

    <div class="row">
        <?php while ($row = mysqli_fetch_assoc($q_terbaru)): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100" style="border: 1px solid #e2e8f0; border-radius: 4px; transition: 0.2s;">
                    <a href="kabar/<?= $row['slug']; ?>">
                        <img src="assets/images/post/<?= $row['thumbnail']; ?>" class="img-fluid" style="width: 100%; height: 200px; object-fit: cover; border-radius: 3px 3px 0 0;" alt="<?= htmlspecialchars($row['judul']); ?>">
                    </a>
                    <div class="card-body">
                        <h5 style="font-size: 1rem; font-weight: 700;">
                            <a href="kabar/<?= $row['slug']; ?>" class="text-dark text-decoration-none">
                                <?= htmlspecialchars($row['judul']); ?>
                            </a>
                        </h5>
                        <p class="text-muted small mt-2">
                            <?= substr(strip_tags($row['konten']), 0, 200); ?>...
                        </p>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>
