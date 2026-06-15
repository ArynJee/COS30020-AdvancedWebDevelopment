<?php 
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "RootFlower";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$work_id = $_GET['id'] ?? 0;

// fetch student work details from database
$sql = "SELECT * FROM studentwork_table WHERE id = '$work_id' AND status = 'approved'";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    header("Location: studentworks.php");
    exit();
}

$work = $result->fetch_assoc();
$media_files = json_decode($work['media_files'], true);
$studentName = $work['first_name'] . ' ' . $work['last_name'];
$studentDate = date('d.m.Y', strtotime($work['upload_date']));

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="author" content="Aryn Mei Wei JEE"/>
    <meta name="description" content="Student Work Detail Page"/>
    <meta name="keywords" content="Root Flower, floral class, Kuching-based"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/styles.css"/>

    <!--bootstrap framework CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">

    <title><?php echo $studentName; ?>'s Work - Root Flower</title>
</head>

<body>
<?php include "include/header.php" ?>

<article class="studentwork-detail mb-5">
    <div class="studentwork-detail-container container my-5">
        <a href="studentworks.php" class="btn back-btn">
            <i class="bi bi-arrow-left"></i> Back to Student Works
        </a>
        <h1 class="text-center mb-5 fw-bold"><?php echo $studentName; ?>'s Work Details</h1>
        
        <div class="detail-card rounded-4 overflow-hidden">
            <div class="row g-0">

                <!-- image slider -->
                <div class="col-lg-7">
                    <div class="media-section position-relative">
                        <div id="workCarousel" class="carousel slide h-100" data-bs-ride="carousel">
                            <div class="carousel-inner h-100">
                                <?php foreach ($media_files as $index => $media): 
                                    $isVideo = pathinfo($media, PATHINFO_EXTENSION) === 'mp4';
                                ?>
                                    <div class="carousel-item h-100 <?= $index === 0 ? 'active' : '' ?>">
                                        <?php if ($isVideo): ?>
                                            <video class="w-100 h-100 object-fit-cover" controls autoplay muted loop>
                                                <source src="<?= $media ?>" type="video/mp4">
                                                Your browser does not support the video tag.
                                            </video>
                                        <?php else: ?>
                                            <img src="<?= $media ?>" class="w-100 h-100 object-fit-cover" alt="Work image <?= $index + 1 ?>">
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($media_files) > 1): ?>
                                <button class="carousel-control-prev" type="button" data-bs-target="#workCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon"></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#workCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon"></span>
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <!-- overlay -->
                        <div class="media-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-end">
                            <div class="overlay-content p-4 w-100 pt-3">
                                <h2 class="display-6 fw-bold mb-2 ps-2"><?php echo $studentName; ?></h2>
                                <p class="mb-0 fs-5 ps-2"><?php echo $studentDate; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- work details -->
                <div class="col-lg-5">
                    <div class="details-section p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                        <div class="detail-item mb-4 ps-3">
                            <h4 class="text-primary mb-3 pb-2">
                                <i class="bi bi-person-circle me-2"></i>Student Information
                            </h4>
                            <p class="mb-2"><strong>Name:</strong> <?php echo $studentName; ?></p>
                            <p class="mb-2"><strong>Workshop:</strong> <?php echo htmlspecialchars($work['workshop_title']); ?></p>
                            <p class="mb-2"><strong>Submission Date:</strong> <?php echo $studentDate; ?></p>
                        </div>

                        <!-- description section -->
                        <div class="description-section detail-item p-3 d-flex flex-column justify-content-center">
                            <h4 class="text-primary mb-3">
                                <i class="bi bi-chat-left-text me-2"></i>Work Description
                            </h4>
                            <div class="description-container bg-light rounded-3 p-4">
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($work['description'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</article>

<?php include "include/footer.php" ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script> 
<script src="js/main.js"></script>
</body>
</html>