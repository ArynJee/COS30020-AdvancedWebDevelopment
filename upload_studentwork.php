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

$upload_dir = 'images/studentworks/';

$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
$alert = $_SESSION['alert'] ?? '';
$alertType = $_SESSION['alertType'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    unset($_SESSION['errors'], $_SESSION['old'], $_SESSION['alert'], $_SESSION['alertType']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $workshop_title = trim($_POST['workshop_title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    $errors = [];
    
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
    
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }
    
    if (empty($workshop_title)) {
        $errors['workshop_title'] = "Workshop Title is required";
    }
    
    // check file uploads
    $uploaded_files = $_FILES['media_files'] ?? [];
    $file_count = 0;
    $valid_files = [];

    foreach ($uploaded_files['name'] as $index => $name){
        if (!empty($name) && $uploaded_files['error'][$index] === UPLOAD_ERR_OK){
            $file_count++;
        }
    }

    if ($file_count == 0){
        $errors['media_files'] = "You must upload at least one media file";
    } elseif ($file_count > 4){
        $errors['media_files'] = "You can only upload a maximum of 4 media files";
    }

    if (empty($errors)){
        // constraint 1: check total approved + pending count < 2 (rejected doesn't count)
        $sql_check_total = "SELECT 
            SUM(CASE WHEN status IN ('approved', 'pending') THEN 1 ELSE 0 END) as active_count
            FROM studentwork_table WHERE email = '$email'";
        
        $result_total = $conn->query($sql_check_total);
        $row_total = $result_total->fetch_assoc();
        
        $active_count = $row_total['active_count'] ?? 0;

        if ($active_count >= 2) {
            $alert = "You have reached the maximum limit of 2 active uploads (approved + pending). You cannot upload more works until some are reviewed.";
            $alertType = "danger";
            $old = $_POST;
        } else {
            // constraints 3: must have attended workshop and workshop date passed
            $sql_check_attendance = "SELECT COUNT(*) FROM workshop_table WHERE email = '$email' AND workshop_title = '$workshop_title' AND workshop_date <= CURDATE()";
            $result_attendance = $conn->query($sql_check_attendance);
            $row_attendance = $result_attendance->fetch_row();
            $attended_count = $row_attendance[0];

            if ($attended_count == 0) {
                $alert = "You must have attended this workshop before you can upload your work for it.";
                $alertType = "danger";
                $old = $_POST;
            } else {
                // constraints 4: 2 uploads per workshop (only counting approved + pending)
                $sql_check_limit = "SELECT COUNT(*) FROM studentwork_table WHERE email = '$email' AND workshop_title = '$workshop_title' AND status IN ('approved', 'pending')";
                $result_limit = $conn->query($sql_check_limit);
                $row_limit = $result_limit->fetch_row();
                $current_uploads = $row_limit[0];

                if ($current_uploads >= 2) {
                    $alert = "You have already reached the maximum of 2 uploads for the workshop: $workshop_title";
                    $alertType = "danger";
                    $old = $_POST;
                } else{
                    $media_paths = [];
                    $upload_ok = true;
                    
                    if (!is_dir($upload_dir)){
                        mkdir($upload_dir, 0777, true);
                    }

                    foreach ($uploaded_files['name'] as $index => $name){
                        if (empty($name) || $uploaded_files['error'][$index] !== UPLOAD_ERR_OK) {
                            continue;
                        }
                        
                        $file_name = $name;
                        $file_tmp = $uploaded_files['tmp_name'][$index];
                        $file_size = $uploaded_files['size'][$index];
                        $file_error = $uploaded_files['error'][$index];
                        $file_type = $uploaded_files['type'][$index];

                        if ($file_error !== UPLOAD_ERR_OK) {
                            $errors['media_files'] = "File upload error on file $file_name (Code: $file_error).";
                            $upload_ok = false;
                            break;
                        }

                        // validate file type
                        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/quicktime'];
                        if (!in_array($file_type, $allowed_types)) {
                            $errors['media_files'] = "File type error: $file_name has an unsupported file type ($file_type). Only JPEG, PNG, GIF, MP4, and MOV are allowed.";
                            $upload_ok = false;
                            break;
                        }

                        // create unique file name and move file
                        $ext = pathinfo($file_name, PATHINFO_EXTENSION);
                        $new_file_name = uniqid('work_') . '.' . $ext;
                        $destination = $upload_dir . $new_file_name;

                        if (move_uploaded_file($file_tmp, $destination)) {
                            $media_paths[] = $destination;
                        } else {
                            $errors['media_files'] = "Failed to move uploaded file: $file_name.";
                            $upload_ok = false;
                            break;
                        }
                    }

                    if ($upload_ok && empty($errors)){
                        $media_files_json = json_encode($media_paths);
                        
                        $sql_insert = "INSERT INTO studentwork_table (email, first_name, last_name, workshop_title, media_files, description) VALUES ('$email', '$first_name', '$last_name', '$workshop_title', '$media_files_json', '$description')";

                        if (mysqli_query($conn, $sql_insert)){
                            include_once 'include/function.php';
                            createAdminNotification(
                                "New Student Work\npending for review",
                                "user: $email\nworkshop: $workshop_title",
                                'student_work',
                                'new_submission'
                            );

                            $alert = "Your work for $workshop_title has been uploaded successfully! It is now pending approval.";
                            $alertType = "success";
                            $old = [];
                        } else {
                            $alert = "Error: Could not save your work. " . mysqli_error($conn);
                            $alertType = "danger";
                            $old = $_POST;
                        }
                    } else {
                        $old = $_POST;
                    }
                }
            }
        }
        
        // store errors and form data in session for display
        if (!empty($errors) || isset($alert)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $old;
            $_SESSION['alert'] = $alert;
            $_SESSION['alertType'] = $alertType;
            header("Location: upload_studentwork.php");
            exit();
        }
    }
}

// workshop titles for dropdown
$workshop_titles = [];
$sql_workshops = "SELECT DISTINCT workshop_title FROM workshop_table";
$result_workshops = $conn->query($sql_workshops);
while ($row = $result_workshops->fetch_assoc()) {
    $workshop_titles[] = $row['workshop_title'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="author" content="Aryn Mei Wei JEE"/>
    <meta name="description" content="Upload Student Work Page"/>
    <meta name="keywords" content="Root Flower, floral class, Kuching-based"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style/styles.css"/>

    <!-- bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">

    <title>Root Flower Upload Student Work Page</title>
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
            <img src="images/product-hero-img.jpg" alt="Upload Student Work Image" class="w-100">
            <div class="gradient-overlay"></div>
        </div>
        

        <div class="studentwork-caption position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center text-center">
            <h1 class="display-3 fw-bolder text-capitalize mb-4">Upload Student Work</h1>
            <p class="fs-5 mb-5">Show off your beautiful creations after attending our workshop.</p>
        </div>
    </div>
    <br>
    
    <div class="studentwork-container my-4 mx-auto p-5 border-0 shadow rounded-2 w-75 themed-modal">
        <h2 class="fw-semibold pb-3 text-center">RootFlower</h2>
        <form method="POST" class="mt-3" novalidate enctype="multipart/form-data">
            
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <input type="text" class="form-control rounded-2 p-2 <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" 
                           name="first_name" id="first_name" placeholder="First Name" 
                           value="<?= htmlspecialchars($old['first_name'] ?? '') ?>">
                    <?php if (isset($errors['first_name'])): ?>
                        <div class="error"><?= htmlspecialchars($errors['first_name']) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6 mb-3">
                    <input type="text" class="form-control rounded-2 p-2 <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>" 
                           name="last_name" id="last_name" placeholder="Last Name"
                           value="<?= htmlspecialchars($old['last_name'] ?? '') ?>">
                    <?php if (isset($errors['last_name'])): ?>
                        <div class="error"><?= htmlspecialchars($errors['last_name']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <input type="email" class="form-control rounded-2 p-2 <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                           name="email" id="email" placeholder="Email"
                           value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                    <?php if (isset($errors['email'])): ?>
                        <div class="error"><?= htmlspecialchars($errors['email']) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6 mb-3">
                    <select class="form-select rounded-2 p-2 <?= isset($errors['workshop_title']) ? 'is-invalid' : '' ?>" 
                            name="workshop_title" id="workshop_title" required>
                        <option value="">Choose your attended workshop...</option>
                        <?php foreach ($workshop_titles as $title): ?>
                            <option value="<?= htmlspecialchars($title) ?>" 
                                    <?= (($old['workshop_title'] ?? '') === $title) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($title) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['workshop_title'])): ?>
                        <div class="error"><?= htmlspecialchars($errors['workshop_title']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <hr>

            <div class="row mb-4">
                <div class="col-12 mb-3">
                    <label class="form-label fw-medium">Upload Your Media (Images/Videos)</label>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <input type="file" class="form-control rounded-2 p-2" name="media_files[]" accept="image/*,video/*">
                        </div>
                        <div class="col-md-6 mb-2">
                            <input type="file" class="form-control rounded-2 p-2" name="media_files[]" accept="image/*,video/*">
                        </div>
                        <div class="col-md-6 mb-2">
                            <input type="file" class="form-control rounded-2 p-2" name="media_files[]" accept="image/*,video/*">
                        </div>
                        <div class="col-md-6 mb-2">
                            <input type="file" class="form-control rounded-2 p-2" name="media_files[]" accept="image/*,video/*">
                        </div>
                    </div>
                    <?php if (isset($errors['media_files'])): ?>
                        <div class="error"><?= htmlspecialchars($errors['media_files']) ?></div>
                    <?php endif; ?>
                    <div class="form-text">You can ONLY upload up to 4 files (images or videos).</div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12 mb-3">
                    <label for="description" class="form-label fw-medium">Description</label>
                    <textarea name="description" id="description" class="form-control rounded-2 p-2" rows="5" placeholder="Write a brief description of your lovely floral creation and what you learned after attending the workshop."><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12 d-flex gap-3">
                    <a href="update_profile.php?tab=myWork#myWork" class="back-to-workshop text-decoration-none">
                        <i class="bi bi-arrow-left-circle fs-2"></i>
                    </a>
                    <button type="submit" class="btn btn-primary border-0">Submit</button>
                    <a href="upload_studentwork.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
    <br>
    <br>
    <br>
</article>

<?php include "include/footer.php" ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="java/main.js"></script>
</body>
</html>