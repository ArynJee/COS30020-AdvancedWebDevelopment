<?php
session_start();

if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    header("Location: main_menu_admin.php");
    exit();
}

$isLoggedIn = isset($_SESSION['user']);
$userType = $_SESSION['user_type'] ?? 'user';

$alert = $_SESSION['alert'] ?? '';
$alertType = $_SESSION['alertType'] ?? '';
unset($_SESSION['alert'], $_SESSION['alertType']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="author" content="Aryn Mei Wei JEE"/>
    <meta name="description" content="Main Menu Page"/>
    <meta name="keywords" content="Root Flower, floral class, Kuching-based"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/styles.css"/>

    <!--bootstrap framework CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">

    <title>Root Flower Main Menu Page</title>
</head>

<body>
<!-- navigation bar -->
<?php include "include/header.php" ?>

<?php if (!empty($alert)): ?>
    <div class="alert alert-<?= htmlspecialchars($alertType) ?> fade show alert-dismissable translate-middle start-50 position-fixed mt-5 w-75 text-center" role="alert">
        <?= htmlspecialchars($alert) ?>
    </div>
<?php endif; ?>

<article class="pt-4">
    <h1 class="fs-1 fw-bolder text-center my-5 main-menu-title">
        Main Menu
    </h1>
    <div class="card-area pb-5">
      <div class="container px-4">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4 mb-5">
        
        <!-- product -->
        <div class="col">
            <div class="box position-relative overflow-hidden rounded-4">
                <img src="images/mainmenu-product.jpg" alt="Product card" class="w-100 rounded-4 d-block">
                <div class="content-overlay rounded-2 p-3 position-absolute w-100">
                    <h3 class="title fw-bold ms-3">Product</h3>
                    <div class="hidden-content">
                        <p class="px-3">Browse now to place order on our beautiful handmade flower bouquet filled with love and passion.</p>
                        <a href="<?= $isLoggedIn ? 'products.php' : 'login.php' ?>" class="btn border-0 rounded-2 text-decoration-none shadow px-2 py-2 ms-3">View More</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- workshop -->
        <div class="col">
            <div class="box position-relative overflow-hidden rounded-4">
                <img src="images/mainmenu-workshop.jpg" alt="Workshop card" class="w-100 rounded-4 d-block">
                <div class="content-overlay rounded-2 p-3 position-absolute w-100">
                    <h3 class="title fw-bold ms-3">Workshop</h3>
                    <div class="hidden-content">
                        <p class="px-3">Learn new skills making your own very special bouquets now by signing up for our offered workshops.</p>
                        <a href="<?= $isLoggedIn ? 'workshops.php' : 'login.php' ?>" class="btn border-0 rounded-2 text-decoration-none shadow px-2 py-2 ms-3">View More</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- student works -->
        <div class="col">
            <div class="box position-relative overflow-hidden rounded-4">
                <img src="images/mainmenu-studentworks.jpg" alt="Student Works card" class="w-100 rounded-4 d-block">
                <div class="content-overlay rounded-2 p-3 position-absolute w-100">
                    <h3 class="title fw-bold ms-3">Student Works</h3>
                    <div class="hidden-content">
                        <p class="px-3">Witness the wonderful creation by our students who attended the workshops here!</p>
                        <a href="<?= $isLoggedIn ? 'studentworks.php' : 'login.php' ?>" class="btn border-0 rounded-2 text-decoration-none shadow px-2 py-2 ms-3">View More</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- flower name -->
        <div class="col">
            <div class="box position-relative overflow-hidden rounded-4">
                <img src="images/mainmenu-flowername.jpg" alt="Flower Name card" class="w-100 rounded-4 d-block">
                <div class="content-overlay rounded-2 p-3 position-absolute w-100">
                    <h3 class="title fw-bold ms-3">Flower Contribution</h3>
                    <div class="hidden-content">
                        <p class="px-3">Contribute to our flower database by sharing with us your rare finds here.</p>
                        <a href="flower.php" class="btn border-0 rounded-2 text-decoration-none shadow px-2 py-2 ms-3">View More</a>
                    </div>
                </div>
            </div>
        </div>
        
        </div>
      </div>
    </div>
</article>

<!-- footer -->
<?php include "include/footer.php" ?>

<!-- bootstrap framework Javascript-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script> 
<!-- javascript -->
<script src="java/main.js"></script>
</body>
</html>