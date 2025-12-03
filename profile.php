<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8"/>
    <meta name="author" content="Aryn Mei Wei JEE"/>
    <meta name="description" content="Profile Page"/>
    <meta name="keywords" content="Root Flower, floral class, Kuching-based"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/styles.css"/>

    <!--bootstrap framework CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">

    <title>Root Flower Profile Page</title>
</head>

<body>
<!-- navigation bar -->
<?php include "include/header.php" ?>

<article>

<div class="profile-bg position-relative">
    <div class="container d-flex flex-column align-items-center justify-content-center profile-container">
        <h1 class="fw-bold text-center mb-5">Profile Page</h1>
        <div class="text-center mb-4">
            <img src="profile_images/profilepic.jpg" class="profile-pic" alt="Profile Picture">
        </div>

        <div class="card profile-card p-4 mb-5">
            <table class="table table-borderless profile-table">
                <tr>
                    <th scope="row">Name:</th>
                    <td>Aryn Mei Wei JEE</td>
                </tr>
                <tr>
                    <th scope="row">Student ID:</th>
                    <td>102789770</td>
                </tr>
                <tr>
                    <th scope="row">Email:</th>
                    <td>102789770@students.swinburne.edu.my</td>
                </tr>
            </table>

            <p class="mt-3">I declare that this assignment is my individual work. I have not work collaboratively nor have I copied from any other student's work or from any other source. I have not engaged another party to complete this assignment. I am aware of the University's policy with regards to plagiarism. I have not allowed, and will not allow, anyone to copy my work with the intention of passing it off as his or her own work.</p>
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