<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-footer.css">

<footer class="main-footer">
    <div class="container">
        <div class="row">
            <div class="col-md-5 mb-4">
                <h5 class="footer-title">Tentang KANDAGA</h5>
                <p class="text-justify-custom">
                    Portal Katalog Naskah Sunda Kairaga (KANDAGA) merupakan inisiatif digital untuk mendokumentasikan dan mempermudah akses terhadap warisan tulis Sunda dari berbagai koleksi dunia.
                </p>
                <?php require_once __DIR__.'/catalog-analytics.php'; $footerVisits=isset($conn)?kandagaCompactCounters($conn):null; if($footerVisits!==null): ?>
                <div class="footer-visits" aria-label="Statistik akses katalog"><div class="footer-visits-title">Statistik akses katalog</div><table class="footer-visits-table"><tbody>
                <?php foreach(['daily'=>'Hari ini','weekly'=>'7 hari terakhir','monthly'=>'Bulan ini','total'=>'Total tampilan'] as $key=>$label): ?><tr><th><?= $label ?></th><td><strong><?= number_format((int)($footerVisits[$key]??0)) ?></strong></td></tr><?php endforeach; ?></tbody></table></div>
                <?php endif; ?>
            </div>

            <div class="col-md-3 mb-4 ms-auto">
                <h5 class="footer-title">Pintasan</h5>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>/pages/indeks-judul.php"><i class="fa-solid fa-chevron-right me-2" style="font-size: 0.7rem;"></i> Indeks Judul</a></li>
                    <li><a href="<?= BASE_URL ?>/monograf"><i class="fa-solid fa-chevron-right me-2" style="font-size: 0.7rem;"></i> Monograf</a></li>
                    <li><a href="<?= BASE_URL ?>/katalog"><i class="fa-solid fa-chevron-right me-2" style="font-size: 0.7rem;"></i> Katalog</a></li>
                    <li><a href="<?= BASE_URL ?>/pages/statistik.php"><i class="fa-solid fa-chevron-right me-2" style="font-size: 0.7rem;"></i> Statistik Data</a></li>
                    <li><a href="<?= BASE_URL ?>/pages/tentang.php"><i class="fa-solid fa-chevron-right me-2" style="font-size: 0.7rem;"></i> Tentang Proyek</a></li>
                    <li><a href="<?= BASE_URL ?>/pages/disclaimer.php"><i class="fa-solid fa-chevron-right me-2" style="font-size: 0.7rem;"></i> Disclaimer</a></li>
                    <li><a href="<?= BASE_URL ?>/pages/kontak.php"><i class="fa-solid fa-chevron-right me-2" style="font-size: 0.7rem;"></i> Kontak</a></li>
                    <!--<li><a href="<?= BASE_URL ?>/pages/login.php"><i class="fa-solid fa-chevron-right me-2" style="font-size: 0.7rem;"></i> Login</a></li>-->
                </ul>
            </div>

            <div class="col-md-3 mb-4 footer-contact">
                <h5 class="footer-title">Kontak Kami</h5>
                <p class="d-flex align-items-start">
                    <i class="fa-solid fa-envelope mt-1"></i>
                    <span>info@kairaga.com</span>
                </p>
                <p class="d-flex align-items-start">
                    <i class="fa-solid fa-location-dot mt-1"></i>
                    <span>Bandung, Jawa Barat,<br>Indonesia</span>
                </p>
                <div class="social-icons mt-3" aria-label="Media sosial Kairaga">
                    <a href="#" title="Facebook" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#" title="Twitter/X" aria-label="Twitter/X"><i class="fa-brands fa-x-twitter"></i></a>
                    <a href="#" title="Instagram" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" title="YouTube" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container text-center">
            <p class="mb-0 small" style="opacity: 0.8;">
                &copy; <?= date('Y'); ?> <span class="brand-footer">KAIRAGA.COM</span>.
                Seluruh data dikelola secara mandiri oleh <b>Kairaga Project</b>.
            </p>
        </div>
    </div>
</footer>

