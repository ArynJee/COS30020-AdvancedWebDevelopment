<?php
session_start();
include 'services/pdf_utils.php';

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

$isLoggedIn = isset($_SESSION['user']);
$userType = $_SESSION['user_type'] ?? 'user';

$upload_dir = 'flower_description/';
$image_dir = 'images/flower_images/';

$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
$alert = $_SESSION['alert'] ?? '';
$alertType = $_SESSION['alertType'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    unset($_SESSION['errors'], 
    $_SESSION['old'], 
    $_SESSION['alert'], 
    $_SESSION['alertType'],
);
}

// handle PDF download
if (isset($_GET['download']) && isset($_SESSION['last_flower_data'])){
    $flower_data = $_SESSION['last_flower_data'];
    $pdf_content = PDFUtils::createFlowerPDF($flower_data, 'database');
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="flower_report_' . $flower_data['id'] . '_' . date('Ymd_His') . '.pdf"');
    header('Content-Length: ' . strlen($pdf_content));
    
    echo $pdf_content;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $scientific_name = trim($_POST['scientific_name'] ?? '');
    $common_name = trim($_POST['common_name'] ?? '');
    
    $errors = [];
    
    // name validations
    if (empty($first_name)) {
        $errors['first_name'] = "First Name is required";
    } elseif (!preg_match("/^[a-zA-Z ]+$/", $first_name)) {
        $errors['first_name'] = "Only letters and white space allowed";
    }
    
    if (empty($last_name)) {
        $errors['last_name'] = "Last Name is required";
    } elseif (!preg_match("/^[a-zA-Z ]+$/", $last_name)) {
        $errors['last_name'] = "Only letters and white space allowed";
    }
    
    // email validation
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }

    if (empty($scientific_name)) {
        $errors['scientific_name'] = "Scientific Name is required";
    }

    if (empty($common_name)) {
        $errors['common_name'] = "Common Name is required";
    }
    
    // check flower image upload
    $flower_image = $_FILES['flower_image'] ?? [];
    $image_uploaded = false;
    $image_path = '';

    if (!empty($flower_image['name']) && $flower_image['error'] === UPLOAD_ERR_OK){
        $image_name = $flower_image['name'];
        $image_tmp = $flower_image['tmp_name'];
        $image_size = $flower_image['size'];
        $image_error = $flower_image['error'];
        $image_type = $flower_image['type'];

        // validate image type
        $image_ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
        $allowed_image_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($image_ext, $allowed_image_ext)) {
            $errors['flower_image'] = "Only JPG, JPEG, PNG, and GIF images are allowed. Your file: .$image_ext";
        }

        // validate image size - 5MB limit
        $max_image_size = 5 * 1024 * 1024; // 5MB in bytes
        if ($image_size > $max_image_size) {
            $errors['flower_image'] = "Image size must be less than 5MB. Your file: " . round($image_size / (1024 * 1024), 2) . "MB";
        }

        if (empty($errors)) {
            // create image directory if not exists
            if (!is_dir($image_dir)){
                mkdir($image_dir, 0777, true);
            }

            // create unique image name
            $new_image_name = uniqid('flower_img_') . '.' . $image_ext;
            $image_destination = $image_dir . $new_image_name;

            if (move_uploaded_file($image_tmp, $image_destination)) {
                $image_path = $image_destination;
                $image_uploaded = true;
            } else {
                $errors['flower_image'] = "Failed to upload image. Please try again.";
            }
        }
    } else {
        if ($flower_image['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors['flower_image'] = "Image upload error. Please try again.";
        } else {
            $errors['flower_image'] = "Flower image is required";
        }
    }
    
    // check description file upload
    $description_file = $_FILES['description_file'] ?? [];
    $file_uploaded = false;
    $file_path = '';

    if (!empty($description_file['name']) && $description_file['error'] === UPLOAD_ERR_OK){
        $file_name = $description_file['name'];
        $file_tmp = $description_file['tmp_name'];
        $file_size = $description_file['size'];
        $file_error = $description_file['error'];
        $file_type = $description_file['type'];

        // validate file type - only PDF allowed
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['pdf'];

        if (!in_array($file_ext, $allowed_ext)) {
            $errors['description_file'] = "Only PDF files are allowed. Your file: .$file_ext";
        }

        // validate file size - 7MB limit
        $max_size = 7 * 1024 * 1024; // 7MB in bytes
        if ($file_size > $max_size) {
            $errors['description_file'] = "File size must be less than 7MB. Your file: " . round($file_size / (1024 * 1024), 2) . "MB";
        }

        if (empty($errors)) {
            // create upload directory if not exists
            if (!is_dir($upload_dir)){
                mkdir($upload_dir, 0777, true);
            }

            // create unique file name
            $new_file_name = uniqid('flower_') . '.' . $file_ext;
            $destination = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $destination)) {
                $file_path = $destination;
                $file_uploaded = true;
            } else {
                $errors['description_file'] = "Failed to upload file. Please try again.";
            }
        }
    } else {
        if ($description_file['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors['description_file'] = "File upload error. Please try again.";
        } else {
            $errors['description_file'] = "Description file is required";
        }
    }

    if (empty($errors) && $file_uploaded && $image_uploaded){
        // insert into flower table WITHOUT description_extracted first
        $sql_insert = "INSERT INTO flower_table (first_name, last_name, email, Scientific_Name, Common_Name, plants_image, description) VALUES ('$first_name', '$last_name', '$email', '$scientific_name', '$common_name', '$image_path', '$file_path')";

        if (mysqli_query($conn, $sql_insert)) {
            $record_id = mysqli_insert_id($conn);

            // step1: extract text
            $extracted_text = PDFUtils::extractTextFromPDF($file_path);

            // store extracted text to database
            $extraction_success = PDFUtils::processUploadedPDF($file_path, $conn, $record_id);

            // store the inserted flower data in session for PDF generation
            $flower_data = [
                'id' => $record_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'Scientific_Name' => $scientific_name,
                'Common_Name' => $common_name,
                'plants_image' => $image_path,
                'description' => $extracted_text ?: "Description extracted from PDF file."
            ];

            // flag for enabling download pdf button
            $_SESSION['show_download_button'] = true;
            $_SESSION['last_flower_data'] = $flower_data; // store data in session for pdf

            $alert = "Contribution has been uploaded successfully!" . ($extraction_success ? " PDF description extracted." : " Note: Could not extract text from PDF.");
            $alertType = "success";
            $old = [];
        } else {
            $alert = "Error: Could not save contribution. " . mysqli_error($conn);
            $alertType = "danger";
            $old = $_POST;
            
            // delete uploaded files if database insert failed
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
    } else {
        $old = $_POST;
    }
    
    // store errors and form data in session for display
    if (!empty($errors) || isset($alert)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = $old;
        $_SESSION['alert'] = $alert;
        $_SESSION['alertType'] = $alertType;
        header("Location: flower.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="author" content="Aryn Mei Wei JEE"/>
    <meta name="description" content="Upload Flower Information Page"/>
    <meta name="keywords" content="Root Flower, floral class, Kuching-based"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style/styles.css"/>

    <!-- bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">

    <title>Root Contribution Page</title>
</head>

<body>
<?php include "include/header.php" ?>

<?php if (!empty($alert)): ?>
    <div class="alert alert-<?= htmlspecialchars($alertType) ?> fade show alert-dismissable translate-middle start-50 position-fixed mt-5 w-75 text-center" role="alert">
        <?= htmlspecialchars($alert) ?>
    </div>
<?php endif; ?>

<article>
    <div class="studentwork-hero position-relative mb-5">
        <div class="studentwork-hero-img position-relative object-fit-cover">
            <img src="images/product-hero-img.jpg" alt="Upload Flower Information Image" class="w-100">
            <div class="gradient-overlay"></div>
        </div>
        
        <div class="studentwork-caption position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center text-center">
            <h1 class="display-3 fw-bolder text-capitalize mb-4">Contribute to RootFlower</h1>
            <p class="fs-5 mb-5">Expand our flower inventory and widen our flower selection by contributing here.</p>
        </div>
    </div>
    <br>
    
    <div class="flower-container my-4 mx-auto p-5 border-0 shadow rounded-2 w-75 themed-modal">
        <h2 class="fw-semibold pb-3 text-center">RootFlower</h2>
        <form method="POST" class="mt-3" novalidate enctype="multipart/form-data">
            
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-medium">First Name</label>
                    <input type="text" class="form-control rounded-2 p-2 <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" 
                           name="first_name" id="first_name" placeholder="First Name" 
                           value="<?= htmlspecialchars($old['first_name'] ?? '') ?>">
                    <?php if (isset($errors['first_name'])): ?>
                        <div class="error"><?= htmlspecialchars($errors['first_name']) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-medium">Last Name</label>
                    <input type="text" class="form-control rounded-2 p-2 <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>" 
                           name="last_name" id="last_name" placeholder="Last Name"
                           value="<?= htmlspecialchars($old['last_name'] ?? '') ?>">
                    <?php if (isset($errors['last_name'])): ?>
                        <div class="error"><?= htmlspecialchars($errors['last_name']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12 mb-3">
                    <label class="form-label fw-medium">Email</label>
                    <input type="email" class="form-control rounded-2 p-2 <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                           name="email" id="email" placeholder="Email"
                           value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                    <?php if (isset($errors['email'])): ?>
                        <div class="error"><?= htmlspecialchars($errors['email']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <hr>

            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-medium">Scientific Name</label>
                    <input type="text" class="form-control rounded-2 p-2 <?= isset($errors['scientific_name']) ? 'is-invalid' : '' ?>" 
                           name="scientific_name" id="scientific_name" placeholder="Enter scientific name" 
                           value="<?= htmlspecialchars($old['scientific_name'] ?? '') ?>">
                    <?php if (isset($errors['scientific_name'])): ?>
                        <div class="error"><?= htmlspecialchars($errors['scientific_name']) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-medium">Common Name</label>
                    <input type="text" class="form-control rounded-2 p-2 <?= isset($errors['common_name']) ? 'is-invalid' : '' ?>" 
                           name="common_name" id="common_name" placeholder="Enter common name"
                           value="<?= htmlspecialchars($old['common_name'] ?? '') ?>">
                    <?php if (isset($errors['common_name'])): ?>
                        <div class="error"><?= htmlspecialchars($errors['common_name']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <hr>

            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-medium">Flower Image</label>
                    <input type="file" class="form-control rounded-2 p-2 <?= isset($errors['flower_image']) ? 'is-invalid' : '' ?>" 
                           name="flower_image" id="flower_image" accept="image/*">
                    <?php if (isset($errors['flower_image'])): ?>
                        <div class="error"><?= htmlspecialchars($errors['flower_image']) ?></div>
                    <?php endif; ?>
                    <div class="form-text">Only JPG, JPEG, PNG, and GIF images are allowed. Maximum file size: 5MB.</div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-medium">Description File (PDF only)</label>
                    <input type="file" class="form-control rounded-2 p-2 <?= isset($errors['description_file']) ? 'is-invalid' : '' ?>" 
                           name="description_file" id="description_file" accept=".pdf">
                    <?php if (isset($errors['description_file'])): ?>
                        <div class="error"><?= htmlspecialchars($errors['description_file']) ?></div>
                    <?php endif; ?>
                    <div class="form-text">Only PDF files are allowed. Maximum file size: 7MB.</div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12 d-flex gap-3">
                    <a href="main_menu.php" class="back-to-workshop text-decoration-none">
                        <i class="bi bi-arrow-left-circle fs-2"></i>
                    </a>
                    <button type="submit" class="btn btn-primary border-0">Submit</button>
                    <a href="flower.php" class="btn btn-outline-secondary">Reset</a>

                    <?php if (isset($_SESSION['show_download_button'])): ?>
                        <a href="flower.php?download=1" class="btn btn-primary">
                            <i class="bi bi-download me-1"></i> Download PDF
                        </a>
                        <?php 
                            unset($_SESSION['show_download_button']); 
                        ?>
                    <?php else: ?>
                        <button type="button" class="btn btn-primary" disabled>
                            <i class="bi bi-download me-1"></i> Download PDF
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
    <br>
    <div class="text-center my-5">
        <hr class="w-100 mx-auto">
        <h2 class="fw-bold about-title mt-5">Contributed Flowers</h2>
        <p class="text-muted mb-5">Explore beautiful flowers shared by our community</p>
    </div>

    <!-- contributed flowers -->
    <div class="flower-grid-container my-5">
        <div class="container-fluid px-4">
            <?php
            $flower_sql = "SELECT f.plants_image, f.Common_Name, f.Scientific_Name, f.first_name, f.last_name FROM flower_table f WHERE f.plants_image IS NOT NULL AND f.plants_image != '' ORDER BY RAND() LIMIT 9";
            
            $flower_result = $conn->query($flower_sql);
            
            if ($flower_result->num_rows > 0): 
            ?>
                <div class="row g-4">
                    <?php while($flower = $flower_result->fetch_assoc()): ?>
                        <div class="col">
                            <div class="flower-card card border-0 shadow-sm h-100 overflow-hidden rounded themed-modal">
                                <img src="<?= htmlspecialchars($flower['plants_image']) ?>" 
                                     alt="<?= htmlspecialchars($flower['Common_Name']) ?>" 
                                     class="card-img-top flower-image object-fit-cover">
                                <div class="card-body text-center p-3">
                                    <h6 class="card-title mb-1 fw-semibold"><?= htmlspecialchars($flower['Common_Name']) ?></h6>
                                    <small class="text-muted d-block mb-2"><?= htmlspecialchars($flower['Scientific_Name']) ?></small>
                                    <small>
                                        By: <?= htmlspecialchars($flower['first_name'] . ' ' . $flower['last_name']) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <p class="text-muted">No flower images available yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <br>
    <br>
</article>

<?php
include "include/footer.php";
$conn->close();
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="java/main.js"></script>
</body>
</html>