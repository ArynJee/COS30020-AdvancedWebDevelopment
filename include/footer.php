<footer class="footer">
    <div class="container">
        <div class="row d-flex justify-content-center">
            <div class="footer-introduction col-lg-4 my-2 ps-5">
                <h5 class="footer-heading fw-bold position-relative">Root Flower</h5>
                <p>Root Flower is a Kuching-based floral company founded in 2021. We specialize in bouquets and gifts box for seasonal festives. With express delivery service, we offer only a curated selection of exquisite floral arrangement and thoughtful gifts.</p>
            </div>

            <div class="col-lg-2 my-2 ps-5">
                <h5 class="footer-heading fw-bold position-relative">Discover</h5>
                <ul class="footer-links ps-0">
                    <li><a href="main_menu.php" class="text-decoration-none">Main Menu</a></li>
                    <li>
                        <a href="<?php echo isset($_SESSION['user']) ? 'products.php' : '#'; ?>" 
                           class="text-decoration-none <?php echo !isset($_SESSION['user']) ? 'disabled-link' : ''; ?>">
                            Products
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo isset($_SESSION['user']) ? 'workshops.php' : '#'; ?>" 
                           class="text-decoration-none <?php echo !isset($_SESSION['user']) ? 'disabled-link' : ''; ?>">
                            Workshops
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo isset($_SESSION['user']) ? 'studentworks.php' : '#'; ?>" 
                           class="text-decoration-none <?php echo !isset($_SESSION['user']) ? 'disabled-link' : ''; ?>">
                            Student Works
                        </a>
                    </li>
                </ul>
            </div>

            <div class="col-lg-2 my-2 ps-5">
                <h5 class="footer-heading-offset"></h5>
                <ul class="footer-links-offset ps-0">
                    <li><a href="profile.php" class="text-decoration-none">Profile</a></li>
                    <li><a href="about.php" class="text-decoration-none">About</a></li>
                </ul>
            </div>

            <div class="col-lg-4 my-2 ps-5">
                <h5 class="footer-heading fw-bold position-relative">Contact us</h5>
                <div class="social-icons mt-4">
                    <a href="https://www.facebook.com/share/15ywShieQr/" target="_blank" title="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="https://www.instagram.com/root.flowersss" target="_blank" title="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="https://api.whatsapp.com/send?phone=60143399709" target="_blank" title="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                </div>
            </div>
        </div>
    </div>
</footer>