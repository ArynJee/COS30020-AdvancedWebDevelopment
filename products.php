<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php");
    exit();
}

$filename = "products.txt";
if (!file_exists($filename)) {
    die("products.txt not found.");
}

$lines = file($filename, FILE_IGNORE_NEW_LINES);

// map folder keys to display name
$categories = [
    "valentines"   => "Valentines",
    "grad"         => "Graduation",
    "daily"        => "Daily Everyday flowers",
    "flowerstand"  => "Flower Stand",
    "cny"          => "Chinese New Year"
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8"/>
    <meta name="author" content="Aryn Mei Wei JEE"/>
    <meta name="description" content="Products Page"/>
    <meta name="keywords" content="Root Flower, floral class, Kuching-based"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/styles.css"/>

    <!--bootstrap framework CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">

    <title>Root Flower Product Page</title>
</head>

<body>
<!-- navigation bar -->
<?php include "include/header.php" ?>

<article>
    <div class="product-hero position-relative mb-0">
        <div class="product-hero-img position-relative object-fit-cover">
            <img src="images/product-hero-img.jpg" alt="Product Image" class="w-100">
            <div class="gradient-overlay"></div>
        </div>

        <div class="product-caption position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center text-center">
            <h1 class="display-3 fw-bolder text-capitalize mb-4">Products</h1>
            <p class="fs-5 mb-5">Shop our uniquely curated products made with lots of love for various occasions.</p>
        </div>
    </div>

    <div class="sticky-space"></div>

    <!-- category navbar -->
    <div class="category-nav-container sticky-top">
        <div class="container">
            <ul class="nav nav-pills justify-content-center pt-4 pb-2 mb-0" id="categoryTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-5 fs-6 fw-semibold mx-3 mb-3 px-3 py-2" id="all-tab" data-bs-toggle="pill" data-bs-target="#all-content" type="button" role="tab" aria-controls="all-content" aria-selected="true">All</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-5 fs-6 fw-semibold mx-3 px-3 py-2" id="valentines-tab" data-bs-toggle="pill" data-bs-target="#valentines-content" type="button" role="tab" aria-controls="valentines-content" aria-selected="false">Valentines</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-5 fs-6 fw-semibold mx-3 px-3 py-2" id="grad-tab" data-bs-toggle="pill" data-bs-target="#grad-content" type="button" role="tab" aria-controls="grad-content" aria-selected="false">Graduation</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-5 fs-6 fw-semibold mx-3 px-3 py-2" id="daily-tab" data-bs-toggle="pill" data-bs-target="#daily-content" type="button" role="tab" aria-controls="daily-content" aria-selected="false">Daily Everyday</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-5 fs-6 fw-semibold mx-3 px-3 py-2" id="flowerstand-tab" data-bs-toggle="pill" data-bs-target="#flowerstand-content" type="button" role="tab" aria-controls="flowerstand-content" aria-selected="false">Flower Stand</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-5 fs-6 fw-semibold mx-3 px-3 py-2" id="cny-tab" data-bs-toggle="pill" data-bs-target="#cny-content" type="button" role="tab" aria-controls="cny-content" aria-selected="false">Chinese New Year</button>
                </li>
            </ul>
        </div>
    </div>
    
    <div class="container my-0">
        <div class="tab-content" id="categoryTabsContent">
            <!-- all products -->
            <div class="tab-pane fade show active py-2" id="all-content" role="tabpanel" aria-labelledby="all-tab" tabindex="0">
                <div class="row g-4 mt-5 mb-5" id="productGrid">
                    <?php
                    $lines = file("products.txt", FILE_IGNORE_NEW_LINES);
                    foreach ($lines as $line) {
                        list($cat, $folderKey, $name, $price, $sold, $folder, $images) = explode("|", $line);
                        $imgs = explode(",", $images);

                        $displayName = $categories[$folderKey];
                    ?>
                    <div class="col-6 col-md-4 col-lg-4 product-card" data-category="<?php echo $displayName; ?>">
                        <div class="product-wrapper text-center mx-3 my-3 position-relative">
                            <div class="image-wrapper position-relative overflow-hidden">
                                <div class="slider position-relative">
                                    <?php foreach ($imgs as $i => $img): ?>
                                        <img src="images/products/<?php echo $folderKey; ?>/<?php echo $folder; ?>/<?php echo $img; ?>" 
                                            class="img-fluid slide w-100 <?php echo $i === 0 ? 'active' : ''; ?>" 
                                            alt="<?php echo $name; ?>">
                                    <?php endforeach; ?>
                                    <button class="slider-btn prev ms-2 position-absolute border-0 rounded-5"><i class="bi bi-chevron-left"></i></button>
                                    <button class="slider-btn next end-0 me-2 position-absolute border-0 rounded-5"><i class="bi bi-chevron-right"></i></button>
                                </div>
                            </div>

                            <div class="product-info mt-3">
                                <h6 class="product-name mb-2 fs-5 fw-semibold"><?php echo $name; ?></h6>
                                <p class="product-price mb-2">RM<?php echo $price; ?></p>
                                <p class="product-sold text-muted mb-3"><?php echo $sold; ?> sold</p>
                                <a href="#" class="btn px-3 py-2">Add to Cart</a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <div class="pagination-container d-flex justify-content-center mt-2 mb-5"></div>
            </div>

            <!-- category tabs -->
            <?php foreach ($categories as $key => $displayName): ?>
            <div class="tab-pane fade py-2" id="<?php echo $key; ?>-content" role="tabpanel" aria-labelledby="<?php echo $key; ?>-tab" tabindex="0">
                <div class="row g-4 mt-5 mb-5">
                    <?php
                    $lines = file("products.txt", FILE_IGNORE_NEW_LINES);
                    foreach ($lines as $line) {
                        list($cat, $folderKey, $name, $price, $sold, $folder, $images) = explode("|", $line);
                        if ($folderKey === $key) {
                            $imgs = explode(",", $images);
                    ?>
                    <div class="col-6 col-md-4 col-lg-4 product-card">
                        <div class="product-wrapper text-center mx-3 my-3 position-relative">
                            <div class="image-wrapper position-relative overflow-hidden">
                                <div class="slider position-relative">
                                    <?php foreach ($imgs as $i => $img): ?>
                                        <img src="images/products/<?php echo $folderKey; ?>/<?php echo $folder; ?>/<?php echo $img; ?>" 
                                            class="img-fluid slide <?php echo $i === 0 ? 'active' : ''; ?>" 
                                            alt="<?php echo $name; ?>">
                                    <?php endforeach; ?>
                                    <button class="slider-btn prev ms-2 position-absolute border-0 rounded-5"><i class="bi bi-chevron-left"></i></button>
                                    <button class="slider-btn next end-0 me-2 position-absolute border-0 rounded-5"><i class="bi bi-chevron-right"></i></button>
                                </div>
                            </div>

                            <div class="product-info mt-3">
                                <h6 class="product-name mb-2 fs-5 fw-semibold"><?php echo $name; ?></h6>
                                <p class="product-price mb-2">RM<?php echo $price; ?></p>
                                <p class="product-sold text-muted mb-3"><?php echo $sold; ?> sold</p>
                                <a href="#" class="btn px-3 py-2">Add to Cart</a>
                            </div>
                        </div>
                    </div>
                    <?php 
                        }
                    } 
                    ?>
                </div>
                <div class="pagination-container d-flex justify-content-center mt-2 mb-5"></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- back to top -->
    <a href="#" class="btn back-to-top position-fixed rounded-3 border-2 opacity-0 invisible px-3 py-2">
        <i class="bi bi-chevron-double-up fs-4"></i>
    </a>
</article>

<!-- footer -->
<?php include "include/footer.php" ?>

<!-- bootstrap framework Javascript-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script> 
<!-- javascript -->
<script src="java/main.js"></script>
</body>
</html>