<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "RootFlower";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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
    <meta name="description" content="Student Works Page"/>
    <meta name="keywords" content="Root Flower, floral class, Kuching-based"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/styles.css"/>

    <!--bootstrap framework CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">

    <title>Root Flower Student Works</title>
</head>

<body>
<?php include "include/header.php" ?>

<article>
    <div class="studentworks-container mx-auto my-auto">
        <h1 class="my-5 fw-bold text-center">Student Works</h1>
        <div class="studentworks-gallery mx-5 mb-5">
        <?php
            // Fetch approved works from database
            $sql = "SELECT * FROM studentwork_table WHERE status = 'approved' ORDER BY upload_date DESC";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $media_files = json_decode($row['media_files'], true);
                    $first_media = $media_files[0] ?? '';

                    $isVideo = pathinfo($first_media, PATHINFO_EXTENSION) === 'mp4';
                    $studentName = $row['first_name'] . ' ' . $row['last_name'];
                    $uploadDate = date('d.m.Y', strtotime($row['upload_date']));
                    $workshop_title = $row['workshop_title'];
                    
                    echo '<a href="studentwork_detail.php?id=' . $row['id'] . '" class="text-decoration-none">';
                    echo '<div class="photo position-relative overflow-hidden rounded-2 d-inline-block mb-3 w-100 shadow">';
                    
                    if ($isVideo){
                        echo '<video class="media-item d-block shadow" muted loop autoplay>';
                        echo '<source src="' . $first_media . '" type="video/mp4">';
                        echo 'Your browser does not support the video tag.';
                        echo '</video>';
                    } else {
                        echo '<img src="' . $first_media . '" class="media-item d-block shadow" alt="Student work by ' . $studentName . '">';
                    }
                    
                    echo '<div class="caption-overlay position-absolute end-0 start-0 bottom-0 pt-5 ps-4 pe-2 py-3">';
                    echo '<div class="caption-content">';
                    echo '<h3 class="mb-1 fw-semibold">' . $studentName . '</h3>';
                    echo '<p>' . $workshop_title . '</p>';
                    echo '<p>' . $uploadDate . '</p>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    echo '</a>';
                }
            } else {
                echo '<div class="text-center w-100">';
                echo '<p class="text-muted">No approved student works yet.</p>';
                echo '</div>';
            }
        ?>
        </div>
    </div>
</article>

<!-- footer -->
<?php
include "include/footer.php";
$conn->close();
?>

<!-- bootstrap framework Javascript-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script> 
<!-- javascript -->
<script src="java/main.js"></script>
<!-- masonry framework -->
<script src="https://cdn.jsdelivr.net/npm/masonry-layout@4.2.2/dist/masonry.pkgd.min.js" integrity="sha384-GNFwBvfVxBkLMJpYMOABq3c+d3KnQxudP/mGPkzpZSTYykLBNsZEnG2D9G/X/+7D" crossorigin="anonymous" async></script>
<script src="https://unpkg.com/imagesloaded@5/imagesloaded.pkgd.js"></script>
</body>
</html>
