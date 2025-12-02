<?php
session_start();
include 'include/function.php';

if (!isset($_SESSION['user']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php");
    exit();
}

$isLoggedIn = isset($_SESSION['user']);
$userType = $_SESSION['user_type'] ?? 'user';

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

$userData = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // handle profile image
    if (isset($_POST['upload_profile_image']) && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'profile_images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            $_SESSION['alert'] = "Only JPG, JPEG, and PNG files are allowed.";
            $_SESSION['alertType'] = "danger";
            header("Location: update_profile.php");
            exit;
        }
        
        if ($_FILES['profile_image']['size'] > 5 * 1024 * 1024) {
            $_SESSION['alert'] = "File size must be less than 5MB.";
            $_SESSION['alertType'] = "danger";
            header("Location: update_profile.php");
            exit;
        }
        
        $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
            $loggedInEmail = $_SESSION['user']['email'];
            $sql_update_image = "UPDATE user_table SET profile_image = '$uploadPath' WHERE email = '$loggedInEmail'";
            
            if ($conn->query($sql_update_image)) {
                $_SESSION['alert'] = "Profile picture updated successfully!";
                $_SESSION['alertType'] = "success";
            } else {
                $_SESSION['alert'] = "Error updating profile picture: " . $conn->error;
                $_SESSION['alertType'] = "danger";
            }
        } else {
            $_SESSION['alert'] = "Error uploading file. Please try again.";
            $_SESSION['alertType'] = "danger";
        }
        header("Location: update_profile.php");
        exit;
    }
    
    if (isset($_POST['update_profile'])) {
        $first = trim($_POST['first_name']);
        $last = trim($_POST['last_name']);
        $dob = trim($_POST['dob']);
        $gender = $_POST['gender'] ?? "";
        $email = trim($_POST['email']);
        $hometown = trim($_POST['hometown']);
        
        $originalEmail = $_SESSION['user']['email'];

        $errors = [];
        
        // Validation
        if (empty($first)){
            $errors["first_name"] = "First Name is required";
        } elseif (!preg_match("/^[a-zA-Z ]+$/", $first)) {
            $errors["first_name"] = "Only letters and white space allowed";
        }

        if (empty($last)) {
            $errors["last_name"] = "Last Name is required";
        } elseif (!preg_match("/^[a-zA-Z ]+$/", $last)) {
            $errors["last_name"] = "Only letters and white space allowed";
        }

        if (empty($dob)) {
            $errors["dob"] = "Date of Birth is required";
        }

        if (empty($email)) {
            $errors["email"] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $errors["email"] = "Invalid email format";
        }

        if (empty($hometown)) {
            $errors["hometown"] = "Hometown is required";
        } elseif (!preg_match("/^[a-zA-Z ,.\-]+$/", $hometown)) {
            $errors["hometown"] = "Only letters, spaces, commas, periods and hyphens allowed";
        }

        if (!empty($errors)){
            $_SESSION['errors'] = $errors;
            $_SESSION['form_data'] = ['first_name' => $first,
                'last_name' => $last,
                'dob' => $dob,
                'gender' => $gender,
                'email' => $email,
                'hometown' => $hometown
            ];
            header("Location: update_profile.php");
            exit;
        }
        
        $sql_update_user = "UPDATE user_table SET first_name = '$first', 
                            last_name = '$last', 
                            dob = '$dob', 
                            gender = '$gender', 
                            email = '$email', 
                            hometown = '$hometown' 
                            WHERE email = '$originalEmail'";
        
        if ($conn->query($sql_update_user)){
            $sql_update_workshop = "UPDATE workshop_table SET email = '$email', first_name = '$first', last_name = '$last' WHERE email = '$originalEmail'";
            $conn->query($sql_update_workshop);
            
            $userData = [
                'First Name' => $first,
                'Last Name' => $last,
                'DOB' => $dob,
                'Gender' => $gender,
                'Email' => $email,
                'Hometown' => $hometown
            ];
            
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['first_name'] = $first;
            $_SESSION['user']['last_name'] = $last;

            unset($_SESSION['errors']);
            unset($_SESSION['form_data']);
            
            $_SESSION['alert'] = "Profile updated successfully!";
            $_SESSION['alertType'] = "success";
            header("Location: update_profile.php");
            exit;
        } else {
            $_SESSION['alert'] = "Error updating profile: " . $conn->error;
            $_SESSION['alertType'] = "danger";
            header("Location: update_profile.php");
            exit;
        }
    }
}

// fetch user data
if (!$userData) {
    $loggedInEmail = $_SESSION['user']['email'];
    
    $sql = "SELECT * FROM user_table WHERE email = '$loggedInEmail'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userData = [
            'First Name' => $row['first_name'],
            'Last Name' => $row['last_name'],
            'DOB' => $row['dob'],
            'Gender' => $row['gender'],
            'Email' => $row['email'],
            'Hometown' => $row['hometown'],
            'Profile Image' => $row['profile_image']
        ];
    } else {
        header("Location: login.php");
        exit;
    }
}

// default profile pic based on gender
$profilePic = $userData['Profile Image'] ?? null;
if (empty($profilePic)) {
    $gender = $userData['Gender'] ?? 'Female';
    $genderLower = strtolower($gender);
    $profilePic = $genderLower === 'male' ? 'profile_images/boys.jpg' : 'profile_images/girl.png';
}
$firstName = $userData['First Name'] ?? '';
$lastName = $userData['Last Name'] ?? '';
$userEmail = $userData['Email'] ?? '';
$userDOB = $userData['DOB'] ?? '';
$userGender = $userData['Gender'] ?? 'Female';
$userHometown = $userData['Hometown'] ?? '';


// redirect
$active_tab = $_GET['tab'] ?? 'myProfile'; 
$valid_tabs = ['myProfile', 'myWorkshop', 'myWork'];
if (!in_array($active_tab, $valid_tabs)) {
    $active_tab = 'myProfile';
}

// display registered workshop
$registeredWorkshops = [];
$sql_workshops = "SELECT * FROM workshop_table WHERE email = '$userEmail'";
$result_workshops = $conn->query($sql_workshops);

if ($result_workshops && $result_workshops->num_rows>0) {
    while ($row = $result_workshops->fetch_assoc()) {
        
        $displayDate = !empty($row['workshop_date']) ? $row['workshop_date'] : 'Date not specified';
        
        // parse workshop_time for display
        $scheduleTimes = [];
        if (!empty($row['workshop_time'])) {
            $times = explode(', ', $row['workshop_time']);
            $dayLabels = ['Day 1', 'Day 2', 'Day 3', 'Day 4'];
            
            foreach ($times as $index => $time) {
                if (!empty(trim($time))) {
                    $dayLabel = isset($dayLabels[$index]) ? $dayLabels[$index] : 'Session ' . ($index + 1);
                    $scheduleTimes[$dayLabel] = [trim($time)];
                }
            }
        }

        $registeredWorkshops[] = [
            'id' => $row['id'],
            'workshop_title' => $row['workshop_title'],
            'workshop_type' => $row['workshop_schedule_type'] ?? '',
            'workshop_date' => $displayDate,
            'workshop_time' => $row['workshop_time'] ?? '',
            'attendees' => $row['attendees'] ?? '1',
            'registration_date' => $row['registration_date'] ?? 'Not specified',
            'status' => $row['status'] ?? 'pending',
            'schedule' => $scheduleTimes 
        ];
    }
}

// handle changes in number of attendees
if (isset($_POST['update_attendees'])) {
    $workshop_id = $_POST['workshop_id'];
    $new_attendees = $_POST['attendees'];

    // validation
    if ($new_attendees < 1 || $new_attendees > 10) {
        $_SESSION['alert'] = "Number of attendees must be between 1 and 10.";
        $_SESSION['alertType'] = "danger";
        header("Location: update_profile.php?tab=myWorkshop");
        exit;
    }
    
    $status_sql = "SELECT status FROM workshop_table WHERE id = $workshop_id";
    $status_result = $conn->query($status_sql);
    $current_status = 'pending';
    
    if ($status_result && $status_result->num_rows > 0){
        $status_row = $status_result->fetch_assoc();
        $current_status = $status_row['status'];
    }
    
    // update status to pending when number of attendees changed
    $new_status = $current_status === 'rejected' ? 'pending' : $current_status;
    
    $update_attendees_sql = "UPDATE workshop_table SET attendees = $new_attendees, status = '$new_status' WHERE id = $workshop_id";
    
    if ($conn->query($update_attendees_sql)){
        $workshop_sql = "SELECT workshop_title, attendees FROM workshop_table WHERE id = $workshop_id";
        $workshop_result = $conn->query($workshop_sql);
        $workshop_data = $workshop_result->fetch_assoc();
        
        // create admin notification for changed attendees
        include_once 'include/function.php';
        createAdminNotification(
            "Changed Number of Attendees",
            "user: {$_SESSION['user']['email']}\nworkshop: \"{$workshop_data['workshop_title']}\"\nattendees (new): $new_attendees",
            'workshop',
            'attendees_updated'
        );
        if ($current_status === 'rejected'){
            $_SESSION['alert'] = "Attendees updated. Status reset to pending for admin review.";
        } else{
            $_SESSION['alert'] = "Number of attendees updated successfully!";
        }
        $_SESSION['alertType'] = "success";
        header("Location: update_profile.php?tab=myWorkshop");
    } else{
        $_SESSION['alert'] = "Error updating attendees: " . $conn->error;
        $_SESSION['alertType'] = "danger";
        header("Location: update_profile.php?tab=myWorkshop");
    }
    exit;
}

// check workshop expiry to allow changes in number of attendees
foreach ($registeredWorkshops as &$registration) {
    $isEditable = true;
    $workshopExpiryStatus = 'upcoming';
    
    if (!empty($registration['workshop_date'])) {
        $workshopDateStr = $registration['workshop_date'];
        
        // extract first date for flexible workshops
        if (strpos($workshopDateStr, '(') !== false && preg_match('/\(([^)]+)\)/', $workshopDateStr, $matches)) {
            $dates = explode(', ', $matches[1]);
            $firstWorkshopDate = !empty($dates[0]) ? trim($dates[0]) : $workshopDateStr;
        } else {
            $firstWorkshopDate = $workshopDateStr;
        }

        $currentDate = new DateTime();
        $workshopDate = DateTime::createFromFormat('Y-m-d', $firstWorkshopDate);

        if ($workshopDate) {
            $threeDaysBefore = clone $workshopDate;
            $threeDaysBefore->modify('-3 days');
            
            // check if workshop date is in the past
            if ($currentDate > $workshopDate) {
                $isEditable = false;
                $workshopExpiryStatus = 'expired';
            } 
            // check if within 3 days of workshop
            elseif ($currentDate >= $threeDaysBefore) {
                $isEditable = false;
                $workshopExpiryStatus = 'upcoming (within 3 days)';
            } else {
                $isEditable = true;
                $workshopExpiryStatus = 'upcoming';
            }
        }
    }
    $registration['is_editable'] = ($registration['status'] === 'rejected' || $registration['status'] === 'pending') && $isEditable;
    $registration['expiry_status'] = $workshopExpiryStatus;
}
unset($registration);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8"/>
    <meta name="author" content="Aryn Mei Wei JEE"/>
    <meta name="description" content="Update Profile Page"/>
    <meta name="keywords" content="Root Flower, floral class, Kuching-based"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style/styles.css"/>

    <!--bootstrap framework CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">

    <title>Root Flower Update Profile Page</title>
</head>
<body>
<!-- navigation bar -->
<?php include "include/header.php" ?>


<?php if (isset($_SESSION['alert'])): ?>
    <div class="alert alert-<?= htmlspecialchars($_SESSION['alertType'])?> fade show alert-dismissable translate-middle start-50 position-fixed mt-5 w-75 text-center" role="alert">
        <?= htmlspecialchars($_SESSION['alert']) ?>
    </div>
<?php unset ($_SESSION['alert'], $_SESSION['alertType']); ?><?php endif; ?>

<article>
    <section class="user-profile-banner pt-5 px-3 pb-4 text-center position-relative">
        <div class="profile-image-container position-relative d-inline-block">
            <img src="<?php echo $profilePic; ?>" alt="Profile Picture" class="profile-pic object-fit-cover border-4 mb-2">
            
            <!-- upload profile image -->
            <form action="update_profile.php" method="POST" enctype="multipart/form-data" class="mt-2">
                <div class="profile-upload-wrapper">
                    <label for="profile_image" class="btn btn-sm rounded-pill shadow-sm mb-3">
                        <i class="bi bi-camera me-1"></i> Change Photo
                    </label>
                    <input type="file" class="d-none" id="profile_image" name="profile_image" 
                        accept=".jpg,.jpeg,.png" onchange="this.form.submit()">
                    <input type="hidden" name="upload_profile_image" value="1">
                </div>
            </form>
        </div>
        <h4 class="text-white mb-2"><?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></h4>
        <p class="text-white-50"><?php echo htmlspecialchars($userEmail); ?></p>
        
        <ul class="nav justify-content-center profile-nav mt-3" id="profileTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link fw-medium mx-3 my-2 px-3 py-2 rounded-5 <?= $active_tab === 'myProfile' ? 'active' : '' ?>" data-bs-toggle="tab" href="#myProfile" role="tab">My Profile</a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-medium mx-3 my-2 px-3 py-2 rounded-5 <?= $active_tab === 'myWorkshop' ? 'active' : '' ?>" data-bs-toggle="tab" href="#myWorkshop" role="tab">My Workshop</a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-medium mx-3 my-2 px-3 py-2 rounded-5 <?= $active_tab === 'myWork' ? 'active' : '' ?>" data-bs-toggle="tab" href="#myWork" role="tab">My Work</a>
            </li>
        </ul>
    </section>

    <section class="user-profile-section px-5 py-5">
        <div class="tab-content" id="profileTabContent">
            <!-- my profile -->
            <div class="tab-pane fade <?= $active_tab === 'myProfile' ? 'show active' : '' ?> p-4" id="myProfile" role="tabpanel">
                <div class="profile-form">
                    <h3 class="mb-4 text-center fw-semibold mb-5">My Profile</h3>
                    <form action="update_profile.php" method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control <?= isset($_SESSION['errors']['first_name']) ? 'is-invalid' : '' ?>"  name="first_name" 
                                       value="<?= htmlspecialchars($_SESSION['form_data']['first_name'] ?? $userData['First Name'] ?? '') ?>">
                                    <?php if (isset($_SESSION['errors']['first_name'])): ?>
                                        <div class="error"><?= htmlspecialchars($_SESSION['errors']['first_name']) ?></div>
                                    <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control <?= isset($_SESSION['errors']['last_name']) ? 'is-invalid' : '' ?>" name="last_name" 
                                       value="<?= htmlspecialchars($_SESSION['form_data']['last_name'] ?? $userData['Last Name'] ?? '') ?>">
                                <?php if (isset($_SESSION['errors']['last_name'])): ?>
                                    <div class="error"><?= htmlspecialchars($_SESSION['errors']['last_name']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Gender</label>
                                <select class="form-control" name="gender">
                                    <option value="Female" <?php echo ($userGender === 'Female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="Male" <?php echo ($userGender === 'Male') ? 'selected' : ''; ?>>Male</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control <?= isset($_SESSION['errors']['dob']) ? 'is-invalid' : '' ?>" name="dob" 
                                       value="<?= htmlspecialchars($_SESSION['form_data']['dob'] ?? $userData['DOB'] ?? '') ?>">
                                <?php if (isset($_SESSION['errors']['dob'])): ?>
                                    <div class="error"><?= htmlspecialchars($_SESSION['errors']['dob']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control <?= isset($_SESSION['errors']['email']) ? 'is-invalid' : '' ?>" name="email" 
                                   value="<?= htmlspecialchars($userData['Email'] ?? '') ?>" readonly>
                            <?php if (isset($_SESSION['errors']['email'])): ?>
                                <div class="error"><?= htmlspecialchars($_SESSION['errors']['email']) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Hometown</label>
                            <input type="text" class="form-control <?= isset($_SESSION['errors']['hometown']) ? 'is-invalid' : '' ?>" name="hometown" 
                                   value="<?= htmlspecialchars($_SESSION['form_data']['hometown'] ?? $userData['Hometown'] ?? '') ?>">
                            <?php if (isset($_SESSION['errors']['hometown'])): ?>
                                <div class="error"><?= htmlspecialchars($_SESSION['errors']['hometown']) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4 profile-button">
                            <a href="main_menu.php" class="btn me-md-2">Cancel</a>
                            <button type="submit" name="update_profile" class="btn btn-update border-0">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- my workshop tab -->
            <div class="tab-pane fade <?= $active_tab === 'myWorkshop' ? 'show active' : '' ?> p-4" id="myWorkshop" role="tabpanel">
                <?php if (empty($registeredWorkshops)): ?>
                    <div class="empty-state text-center px-5 py-5 my-5">
                        <i class="bi bi-calendar-x mb-2 fs-1"></i>
                        <h4>No attended workshop yet.</h4>
                        <p class="text-muted mb-4">You haven't joined any workshops yet. Start your floral journey today!</p>
                        <a href="workshops.php" class="btn btn-update">Join a Workshop</a>
                    </div>
                <?php else: ?>
                    <div class="registered-workshops">
                        <h3 class="section-title mb-5 fw-semibold text-center">My Registered Workshops</h3>
                        <div class="row justify-content-center">
                            <?php foreach ($registeredWorkshops as $registration): ?>
                                <div class="col-12 col-lg-10 col-xl-8 mb-4">
                                    <div class="workshop-registration-card card border-0 shadow-sm h-100 rounded-2 themed-modal">
                                        <div class="card-body p-4">
                                            <div class="row align-items-stretch g-4">
                                                <!-- workshop image -->
                                                <div class="col-md-3">
                                                    <div class="workshop-image-container d-flex align-items-center">
                                                        <img src="<?php echo getWorkshopImageByTitle($registration['workshop_title']); ?>" 
                                                            class="workshop-card-img img-fluid rounded objcet-fit-cover rounded" 
                                                            alt="<?php echo htmlspecialchars($registration['workshop_title']); ?>">
                                                    </div>
                                                </div>
                                                
                                                <!-- workshop details -->
                                                <div class="col-md-4 pt-5">
                                                    <div class="workshop-details h-100 d-flex flex-column text-center justify-content-between">
                                                        <h5 class="workshop-card-title mb-0 fw-semibold fs-5"><?php echo htmlspecialchars($registration['workshop_title']); ?></h5>
                                                        
                                                        <div class="workshop-meta mt-3">
                                                            <div class="meta-item mb-3 pb-1">
                                                                <div class="meta-label fw-medium mb-1">Workshop Date</div>
                                                                <div class="meta-value text-muted"><?php echo !empty($registration['workshop_date']) ? htmlspecialchars($registration['workshop_date']) : 'Date not specified' ?></div>
                                                            </div>
                                                            
                                                            <div class="meta-item mb-3">
                                                                <div class="meta-label fw-medium mb-1">Number of Attendees</div>
                                                                <?php if ($registration['is_editable']): ?>
                                                                    <form method="POST" class="d-inline-block w-50">
                                                                        <input type="hidden" name="workshop_id" value="<?= $registration['id'] ?>">
                                                                        <div class="input-group input-group-sm">
                                                                            <input type="number" class="form-control form-control-sm ms-3" name="attendees" 
                                                                                value="<?= $registration['attendees'] ?>">
                                                                            <button type="submit" name="update_attendees" class="btn confirm-btn me-3 py-2">Confirm</button>
                                                                        </div>
                                                                    </form>
                                                                <?php else: ?>
                                                                    <div class="meta-value text-muted">
                                                                        <?php echo htmlspecialchars($registration['attendees']); ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                            
                                                            <div class="meta-item mb-3">
                                                                <div class="meta-label fw-medium mb-1">Registered on</div>
                                                                <div class="meta-value text-muted"><?php echo htmlspecialchars($registration['registration_date']); ?></div>
                                                            </div>

                                                            <div class="meta-item">
                                                                <div class="meta-label fw-medium mb-1">Status</div>
                                                                <div class="meta-value">
                                                                    <?php 
                                                                    $status_class = '';
                                                                    switch($registration['status']) {
                                                                        case 'approved':
                                                                            $status_class = 'text-success';
                                                                            break;
                                                                        case 'rejected':
                                                                            $status_class = 'text-danger';
                                                                            break;
                                                                        default:
                                                                            $status_class = 'text-warning';
                                                                    }
                                                                    ?>
                                                                    <span class="fw-semibold text-uppercase <?= $status_class ?>"> <?= $registration['status'] ?></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- class schedule -->
                                                <div class="col-md-5">
                                                    <div class="schedule-section h-100 rounded-2 p-3 shadow-sm">
                                                        <h6 class="schedule-title mb-3 fw-semibold pb-2">Class Schedule</h6>
                                                        <?php if (!empty($registration['schedule'])): ?>
                                                            <div class="schedule-content">
                                                                <?php foreach ($registration['schedule'] as $day => $sessions): ?>
                                                                        <ul class="session-list list-unstyled mb-0 ms-2">
                                                                            <?php foreach ($sessions as $session): ?>
                                                                                <li class="session-item d-flex mb-2 text-start">
                                                                                    <?php echo htmlspecialchars($day); ?>
                                                                                    <i class="bi bi-clock session-icon mx-2 mb-2"></i>
                                                                                    <span class="session-time text-muted"><?php echo htmlspecialchars($session); ?></span>
                                                                                </li>
                                                                            <?php endforeach; ?>
                                                                        </ul>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="no-schedule text-muted px-2 text-center fs-italic">Schedule not available</div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="meta-item p-3">
                                            <div class="schedule-section shadow-sm d-flex align-items-center rounded p-2">
                                                <span class="meta-label fw-medium me-2 mb-0">Workshop Expiry:</span>
                                                <div class="meta-value">
                                                    <?php 
                                                    $expiry_class = $registration['expiry_status'] === 'expired' ? 'text-danger' : 'text-success';
                                                    ?>
                                                    <span class="fw-semibold <?= $expiry_class ?> text-uppercase"><?= $registration['expiry_status'] ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- my work -->
            <div class="tab-pane fade <?= $active_tab === 'myWork' ? 'show active' : '' ?> p-4" id="myWork" role="tabpanel">
                <?php
                $user_email = $_SESSION['user']['email'] ?? '';
                if ($user_email) {
                    $sql = "SELECT * FROM studentwork_table WHERE email = '$user_email' ORDER BY upload_date DESC";
                    $result = $conn->query($sql);
                    
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $media_files = json_decode($row['media_files'], true);
                            $status_class = [
                                'pending' => 'warning',
                                'approved' => 'success', 
                                'rejected' => 'danger'
                            ][$row['status']];
                            ?>
                            <div class="card studentwork-card mb-4 shadow rounded w-50 mx-auto themed-modal border-0 p-4">
                                <div class="row g-0 align-items-stretch">
                                    <!-- image slider -->
                                    <div class="col-md-6">
                                        <div id="carousel-<?= $row['id'] ?>" class="carousel slide" data-bs-ride="carousel">
                                            <div class="carousel-inner rounded">
                                                <?php foreach ($media_files as $index => $media): ?>
                                                    <div class="carousel-item w-100 h-100 <?= $index === 0 ? 'active' : '' ?>">
                                                        <?php if (pathinfo($media, PATHINFO_EXTENSION) === 'mp4'): ?>
                                                            <video class="d-block w-100 h-100 object-fit-cover" controls autoplay muted>
                                                                <source src="<?= $media ?>" type="video/mp4">
                                                            </video>
                                                        <?php else: ?>
                                                            <img src="<?= $media ?>" class="d-block w-100 h-100 object-fit-cover" alt="Work image <?= $index + 1 ?>">
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php if (count($media_files) > 1): ?>
                                                <button class="carousel-control-prev" type="button" data-bs-target="#carousel-<?= $row['id'] ?>" data-bs-slide="prev">
                                                    <span class="carousel-control-prev-icon"></span>
                                                </button>
                                                <button class="carousel-control-next" type="button" data-bs-target="#carousel-<?= $row['id'] ?>" data-bs-slide="next">
                                                    <span class="carousel-control-next-icon"></span>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- student work details -->
                                    <div class="col-md-6">
                                        <div class="card-body d-flex flex-column h-100">
                                            <h5 class="card-title fw-semibold"><?= htmlspecialchars($row['first_name']) ?> <?= htmlspecialchars($row['last_name']) ?></h5>
                                            <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($row['workshop_title']) ?></h6>
                                            <p class="card rounded shadow-sm p-3 card-text flex-grow-1 overflow-y-scroll"><?= htmlspecialchars($row['description']) ?></p>
                                            <p class="card-text"><small class="text-muted">Uploaded on: <?= date('d.m.Y', strtotime($row['upload_date'])) ?></small></p>
                                            <div class="mb-3">
                                                <span class="badge text-uppercase p-2 bg-<?= $status_class ?>"><?= $row['status']?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                        <div class="text-center mt-5">
                            <a href="upload_studentwork.php" class="btn btn-update"><i class="bi bi-plus-circle me-2"></i>Upload More Work</a>
                        </div>
                        <?php
                    } else{
                        ?>
                        <div class="empty-state text-center px-5 my-5 py-5">
                            <i class="bi bi-images mb-2 fs-1"></i>
                            <h4>No uploaded work yet.</h4>
                            <p class="text-muted mb-4">Your beautiful floral creations will appear here once you start uploading.</p>
                            <a href="upload_studentwork.php" class="btn btn-update">Upload a Work</a>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
    </section>
</article>

<!-- footer -->
<?php
$conn->close();
include "include/footer.php" ?>


<!-- bootstrap framework Javascript-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script> 
<!-- javascript -->
<script src="java/main.js"></script>
</body>
</html>