<header>
    <nav class="navbar navbar-expand-lg fixed-top navbar-custom <?= !isset($_SESSION['user']) ? 'navbar-guest' : '' ?>">
        <div class="container-fluid">
            <!-- logo -->
            <?php if (isset($_SESSION['user']) && ($_SESSION['user_type'] ?? 'user') === 'admin'): ?>
            <span class="navbar-brand me-auto">
                <img src="images/logo.jpg" alt="Root Flower website logo" width="30" height="24" class="d-inline-block align-text-top me-2">
                Root Flower
            </span>
            <?php else: ?>
            <a class="navbar-brand me-auto" href="index.php">
                <img src="images/logo.jpg" alt="Root Flower website logo" width="30" height="24" class="d-inline-block align-text-top me-2">
                Root Flower
            </a>
            <?php endif; ?>

            <!-- offcanvas sidebar menu -->
            <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title position-relative" id="offcanvasNavbarLabel">
                        <img src="images/logo.jpg" alt="Root Flower website logo" width="30" height="24" class="d-inline-block align-text-top me-2">
                        Root Flower
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <ul class="navbar-nav justify-content-center flex-grow-1">
                        <?php if (isset($_SESSION['user'])): ?>
                            <?php if (($_SESSION['user_type'] ?? 'user') === 'admin'): ?>
                            <!-- user's links -->
                            <?php else: ?>
                                <li class="nav-item">
                                    <a class="nav-link mx-lg-2 position-relative" href="main_menu.php">Main Menu</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link mx-lg-2 position-relative" href="studentworks.php">Student Works</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link mx-lg-2 position-relative" href="products.php">Products</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link mx-lg-2 position-relative" href="workshops.php">Workshop</a>
                                </li>
                            <?php endif; ?>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link mx-lg-2 position-relative" href="main_menu.php">Main Menu</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link mx-lg-2 position-relative dimmed" href="#">Student Works</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link mx-lg-2 position-relative dimmed" href="#">Products</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link mx-lg-2 position-relative dimmed" href="#">Workshop</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <div class="d-flex align-items-center gap-2 navbar-actions">
                <!-- identify -->
                <?php if (isset($_SESSION['user']) && ($_SESSION['user_type'] ?? 'user') !== 'admin'): ?>
                    <a href="identify.php" class="navbar-icon px-2 text-decoration-none d-inline-flex align-items-center align-middle fs-5">
                        <i class="bi bi-camera fs-4"></i>
                    </a>
                <?php endif; ?>
                <!-- contribution -->
                <?php if (isset($_SESSION['user']) && ($_SESSION['user_type'] ?? 'user') !== 'admin'): ?>
                    <a href="flower.php" class="navbar-icon px-2 text-decoration-none d-inline-flex align-items-center align-middle fs-5">
                        <i class="bi bi-flower1"></i>
                    </a>
                <?php endif; ?>
                
                <!-- user's notification -->
                <?php if (isset($_SESSION['user']) && ($_SESSION['user_type'] ?? 'user') !== 'admin'): ?> 
                    <?php include_once 'include/function.php'; ?>
                    <?php
                        $user_email = $_SESSION['user']['email'];
                        $notifications = getUserNotifications($user_email);
                        $notification_count = getNotificationCount($user_email);
                    ?>
                    <div class="dropdown">
                        <a href="#" class="navbar-icon px-2 text-decoration-none d-inline-flex align-items-center align-middle fs-5 dropdown-toggle" 
                        data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell"></i>
                            <?php if ($notification_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $notification_count ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu notification-dropdown dropdown-menu-end p-2 overflow-y-auto shadow overflow-x-hidden overflow-y-scroll">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <?php if (empty($notifications)): ?>
                                <li><span class="dropdown-item-text text-muted">No notifications</span></li>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <li>
                                        <div class="dropdown-item text-break">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1 text-break"><?= htmlspecialchars($notification['title']) ?></h6>
                                                <small><?= date('M j, g:i A', strtotime($notification['created_at'])) ?></small>
                                            </div>
                                            <p class="mb-1 small text-break"><?= htmlspecialchars($notification['message']) ?></p>
                                        </div>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>

               <!-- user's profile -->
                <?php if (!isset($_SESSION['user']) || (isset($_SESSION['user']) && ($_SESSION['user_type'] ?? 'user') !== 'admin')): ?>
                    <a href="<?= isset($_SESSION['user']) ? 'update_profile.php' : 'login.php' ?>" class="navbar-icon px-2 text-decoration-none d-inline-flex align-items-center align-middle">
                        <i class="bi bi-person fs-5"></i>
                    </a>
                <?php endif; ?>

                <!-- admin notification -->
                <?php if (isset($_SESSION['user']) && ($_SESSION['user_type'] ?? 'user') === 'admin'): ?> 
                    <?php
                        include_once 'include/function.php';
                        $admin_notifications = getAdminNotifications();
                        $admin_notification_count = getAdminNotificationCount();
                    ?>
                    <div class="dropdown">
                        <a class="navbar-icon px-2 text-decoration-none d-inline-flex align-items-center align-middle dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell fs-5"></i>
                            <?php if ($admin_notification_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $admin_notification_count ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end notification-dropdown dropdown-menu-end p-2 overflow-y-auto shadow overflow-x-hidden overflow-y-scroll">
                            <li><h6 class="dropdown-header">Admin Notifications</h6></li>
                                <?php if (empty($admin_notifications)): ?>
                                    <li><span class="dropdown-item-text text-muted">No notifications</span></li>
                                <?php else: ?>
                                    <?php foreach ($admin_notifications as $notification): ?>
                                        <li>
                                            <div class="dropdown-item">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?= htmlspecialchars($notification['title']) ?></h6>
                                                    <small><?= date('M j, g:i A', strtotime($notification['created_at'])) ?></small>
                                                </div>
                                                <p class="mb-1 small"><?= htmlspecialchars($notification['message']) ?></p>
                                            </div>
                                        </li>
                            <li><hr class="dropdown-divider"></li>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- logout -->
                <?php if (isset($_SESSION['user'])): ?>
                    <a href="logout.php" class="navbar-icon px-2 text-decoration-none d-inline-flex align-items-center align-middle">
                        <i class="bi bi-box-arrow-right fs-5"></i>
                    </a>
                <?php endif; ?>
            </div>

            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>
</header>