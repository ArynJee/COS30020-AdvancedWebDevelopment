<div class="admin-wrapper">
    <div class="body-overlay">
        <div id="sidebar" class="d-flex flex-column position-fixed top-0 start-0 h-100">
            <div class="sidebar-header p-3 border-bottom border-secondary">
                <h3 class="fs-4">
                    <img src="images/logo.jpg" alt="Root Flower website logo" width="30" height="30" class="d-inline-block align-text-top me-3">
                    Root Flower
                </h3>
            </div>
            <ul class="list-unstyled m-0 flex-grow-1 p-3">
                <li class="nav-item mb-2">
                <a href="manage_accounts.php" class="nav-link d-flex align-items-center py-2 px-3 rounded"><i class="bi bi-people me-3 fs-5"></i>
                    <span>Manage Account</span>
                </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="manage_studentwork.php" class="nav-link d-flex align-items-center py-2 px-3 rounded"><i class="bi bi-flower3 me-3 fs-5"></i>
                        <span>Manage Student Work</span>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="manage_workshop_reg.php" class="nav-link d-flex align-items-center py-2 px-3 rounded"><i class="bi bi-ui-checks me-3 fs-5"></i>
                        <span>Manage Workshop</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-bottom border-top border-secondary p-3">
                <ul class="list-unstyled m-0">
                    <li class="nav-item mb-2">

                        <?php
                            $admin_notifications = getAdminNotifications();
                            $admin_notification_count = getAdminNotificationCount();
                        ?>

                        <a href="#" class="nav-link d-flex py-2 px=3 rounded position-relative" data-bs-toggle="collapse" data-bs-target="#notificationCollapse"><i class="bi bi-bell me-3 fs-5"></i>
                            <span>Notification</span>
                            <?php if ($admin_notification_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $admin_notification_count ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <div class="collapse" id="notificationCollapse">
                            <div class="p-0 overflow-y-auto">
                                <?php if (empty($admin_notifications)): ?>
                                    <div class="px-3 pt-3">
                                        <span class="p-2">No notifications for now.</span>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($admin_notifications as $index => $notification): ?>
                                        <div class="dropdown-item px-3 py-2">
                                            <div class="d-flex w-100 justify-content-between align-items-start">
                                                <h6 class="mb-1 text-break"><?= htmlspecialchars($notification['title']) ?></h6>
                                                <small class="text-muted flex-shrink-0"><?= date('M j, g:i A', strtotime($notification['created_at'])) ?></small>
                                            </div>
                                            <p class="mb-1 small text-break"><?= htmlspecialchars($notification['message']) ?></p>
                                        </div>
                                        <?php if ($index < count($admin_notifications) - 1): ?>
                                            <hr class="dropdown-divider m-0">
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link d-flex py-2 px-3 rounded"><i class="bi bi-box-arrow-left me-3 fs-5"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>