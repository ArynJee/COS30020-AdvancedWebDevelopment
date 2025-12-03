<?php
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: main_menu.php");
    exit();
}

$alert = $_SESSION['alert'] ?? '';
$alertType = $_SESSION['alertType'] ?? '';
unset($_SESSION['alert'], $_SESSION['alertType']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="author" content="Aryn Mei Wei JEE"/>
    <meta name="description" content="RootFlower Admin Main Menu Page"/>
    <meta name="keywords" content="Root Flower, Admin, Main Menu"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style/styles.css"/>

    <!--bootstrap framework CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">

    <title>RootFlower Admin Main Menu Page</title>
</head>

<body class="admin-page">
<!-- navigation bar -->
<?php include "include/header.php" ?>

<?php if (!empty($alert)): ?>
    <div class="alert alert-<?= htmlspecialchars($alertType) ?> fade show alert-dismissable translate-middle start-50 position-fixed mt-5 w-75 text-center" role="alert">
        <?= htmlspecialchars($alert) ?>
    </div>
<?php endif; ?>

<article class="pt-4">
    <h1 class="fs-1 fw-bolder text-center my-5 main-menu-title">
        Admin Main Menu
    </h1>
    <div class="card-area">
      <div class="container px-4">
        <div class="d-flex flex-wrap justify-content-center gap-4">
        
            <!-- manage user account -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="box position-relative overflow-hidden rounded-4">
                    <img src="images/mainmenu-product.jpg" alt="Manage Users Account" class="w-100 rounded-4 d-block">
                    <div class="content-overlay rounded-2 p-3 position-absolute w-100">
                        <h3 class="title fw-bold ms-3">Manage Users' Account</h3>
                        <div class="hidden-content">
                            <p class="px-3">Manage user accounts, permissions, and access levels.</p>
                            <a href="manage_accounts.php" class="btn border-0 rounded-2 text-decoration-none shadow px-2 py-2 ms-3">Manage</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- manage student work -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="box position-relative overflow-hidden rounded-4">
                    <img src="images/mainmenu-studentworks.jpg" alt="Manage Student Work" class="w-100 rounded-4 d-block">
                    <div class="content-overlay rounded-2 p-3 position-absolute w-100">
                        <h3 class="title fw-bold ms-3">Manage Student Work</h3>
                        <div class="hidden-content">
                            <p class="px-3">Review, approve, and manage student submissions.</p>
                            <a href="manage_studentwork.php" class="btn border-0 rounded-2 text-decoration-none shadow px-2 py-2 ms-3">Manage</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- manage workshop registrations -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="box position-relative overflow-hidden rounded-4">
                    <img src="images/mainmenu-workshop.jpg" alt="Manage Workshop Registration" class="w-100 rounded-4 d-block">
                    <div class="content-overlay rounded-2 p-3 position-absolute w-100">
                        <h3 class="title fw-bold ms-3">Manage Workshop Registration</h3>
                        <div class="hidden-content">
                            <p class="px-3">Manage workshop registrations, schedules, and participants.</p>
                            <a href="manage_workshop_reg.php" class="btn border-0 rounded-2 text-decoration-none shadow px-2 py-2 ms-3">Manage</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div>
</article>

<!-- admin footer -->
<div class="admin-footer">
    <p class="fst-italic text-center pt-3">Acknowledgement @ COS30020 Advanced Web Technology Assignment 2</p>
</div>

<!-- bootstrap framework Javascript-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script> 
<!-- javascript -->
<script src="java/main.js"></script>
</body>
</html>