<?php
session_start();
include 'include/function.php';
if (!isset($_SESSION['user']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8"/>
    <meta name="author" content="Aryn Mei Wei JEE"/>
    <meta name="description" content="Workshops Page"/>
    <meta name="keywords" content="Root Flower, floral class, Kuching-based"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/styles.css"/>

    <!--bootstrap framework CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">

    <title>Root Flower Workshop</title>
</head>

<body>
<?php include "include/header.php" ?>

<article>
    <div class="workshops">
        <div class="container-fluid mb-5">
            <div class="row">
                <div class="col-12 px-0">
                    <div class="workshop-hero position-relative overflow-hidden">
                        <div class="workshop-img position-relative">
                            <img src="images/workshop-img.jpg" alt="Workshop Image" class="w-100 workshop-hero-img">
                            <div class="gradient-overlay"></div>
                        </div>

                        <div class="workshop-caption position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center text-center">
                            <h1 class="display-3 fw-bolder text-capitalize mb-4">Flower Workshops</h1>
                            <p class="fs-5 mb-5">Join our hands-on floral workshops and learn the art of flower arrangement from our expert florists.</p>
                        </div>
                    </div>

                    <?php
                        $workshopOrder = ['handtied-bouquet', 'florist-to-be1', 'florist-to-be2', 'hobby-class'];
                        $position = 0;
                        
                        foreach ($workshopOrder as $workshopKey){
                            echo renderWorkshop($workshopKey, $position);
                            if ($position < count($workshopOrder) - 1){
                                echo '<hr class="my-5">';
                            }
                            $position++;
                        }
                    ?>
                </div>
            </div>
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