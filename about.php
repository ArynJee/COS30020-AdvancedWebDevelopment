<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8"/>
    <meta name="author" content="Aryn Mei Wei JEE"/>
    <meta name="description" content="About Page"/>
    <meta name="keywords" content="Root Flower, floral class, Kuching-based"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/styles.css"/>

    <!--bootstrap framework CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">

    <title>Root Flower About Page</title>
</head>
<body>
<!-- navigation bar -->
<?php include "include/header.php" ?>

<article>
    <div class="container my-5">
        <h1 class="text-center mb-5 fw-bold about-title">About Page</h1>
        
        <div class="about-content mx-auto">
            <div class="about-section mb-5">
                <h3 class="text-primary mb-3"><i class="bi bi-check-circle me-2"></i> Tasks Not Attempted/Not Completed</h3>
                <div class="card rounded-2 shadow">
                    <div class="card-body p-3">
                        <p class="mb-0">None - All required tasks have been completed and implemented.</p>
                    </div>
                </div>
            </div>

            <div class="about-section mb-5">
                <h3 class="text-primary mb-3"><i class="bi bi-x-circle me-2"></i> Parts I Struggled With</h3>
                <div class="card rounded-2 shadow">
                    <div class="card-body px-5 py-4">
                        <ul class="list-group list-group-flush">
                            <li class="border-0 px-3 py-3">Bootstrap Modal Controls - I tried to implement bootstrap multi modal for the add workshop workflow on manage_workshop_reg.php, but I failed to make it work and instead opted for handling modal with URL parameters, and used PHP to write the structure of modal body based on workshop type.</li>
                            <li class="border-0 px-3 py-3">PDF Text Extraction - I initially tried to use only TCPDF to handle the process of extracting text from PDF and storing extracted description from PDF to flower_table, but it always extracts text into raw binary characters due to PDF version issues. As such, I integrated PDFParser with TCPDF as PDFParser is primarily used to extract text from PDF, and TCPDF is primarily used to generate PDF.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="about-section mb-5">
                <h3 class="text-primary mb-3"><i class="bi bi-hand-thumbs-up"></i> Parts I Want To Do Better</h3>
                <div class="card rounded-2 shadow">
                    <div class="card-body px-5 py-4">
                        <ul class="list-group list-group-flush">
                            <li class="border-0 px-3 py-3">Additional Features - I would like to add additional features like add to cart functionality, website-wide search function, and sort and filter records to enhance admin's user experience.
                            </li>
                            <li class="border-0 px-3 py-3">Minimizing use of CSS - I would like to minimize the use of extensive CSS with efficient bootstrap classes as much as possible, as throughout this assignment, I seldom use Bootstrap's ready made template.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="about-section mb-5">
                <h3 class="text-primary mb-3"><i class="bi bi-plus-square"></i> Extended Task/Additional Features</h3>
                <div class="card rounded-2 shadow">
                    <div class="card-body px-5 py-4">
                        <ul class="list-group list-group-flush">
                            <li class="border-0 px-3 py-3">Notifications - User and Admin side notification.</li>
                            <li class="border-0 px-3 py-3">AI Flower Identification - Used Google Flash 2.0 API to identify flower from database / AI.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="about-section mb-5">
                <h3 class="text-primary mb-3"><i class="bi bi-link-45deg"></i> Links</h3>
                <div class="card rounded-2 shadow">
                    <div class="card-body px-5 py-4">
                        <ul class="list-group list-group-flush">
                            <li class="border-0 px-3 py-3"><a href="">Video Presentation</a>
                            </li>
                            <li class="border-0 px-3 py-3"><a href="index.php">Home Page</a>
                            </li>
                        </ul>
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
</body>
</html>