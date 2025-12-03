<?php
session_start();
include 'include/function.php';

if (!isset($_SESSION['user']) || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php");
    exit();
}

// get workshop
$workshopKey = $_GET['workshop'] ?? '';
if (!isset($workshops[$workshopKey])) {
    header("Location: workshops.php");
    exit();
}

$workshop = $workshops[$workshopKey];
$isFlexible = $workshop['template'] === 'flexible_dates';
$workshopType = getWorkshopDurationType($workshop);

$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
$alert = $_SESSION['alert'] ?? '';
$alertType = $_SESSION['alertType'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    unset($_SESSION['errors'], $_SESSION['old'], $_SESSION['alert'], $_SESSION['alertType']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // sanitize inputs
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $attendees = trim($_POST['attendees'] ?? '');
    $workshop_date = trim($_POST['workshop_date'] ?? '');

    $selectedTimes = [];
    if ($workshopType === 'single') {
        $selectedTimes['day1'] = $_POST['day1_time'] ?? '';
    } elseif ($workshopType === 'two_day') {
        $selectedTimes['day1'] = $_POST['day1_time'] ?? '';
        $selectedTimes['day2'] = $_POST['day2_time'] ?? '';
    } elseif ($workshopType === 'four_day') {
        $selectedTimes['day1'] = $_POST['day1_time'] ?? '';
        $selectedTimes['day2'] = $_POST['day2_time'] ?? '';
        $selectedTimes['day3'] = $_POST['day3_time'] ?? '';
        $selectedTimes['day4'] = $_POST['day4_time'] ?? '';
    }
    
    $errors = [];
    
    // validation
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
    
    if (empty($contact)) {
        $errors['contact'] = "Contact Number is required";
    } elseif (!preg_match("/^[0-9+\-\s()]+$/", $contact)) {
        $errors['contact'] = "Invalid contact number format";
    }
    
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }
    
    if (empty($attendees)) {
        $errors['attendees'] = "Number of attendees is required";
    } elseif (!is_numeric($attendees) || $attendees < 1 || $attendees > 10) {
        $errors['attendees'] = "Please enter a valid number of attendees (1-10)";
    }
    
    if (empty($workshop_date)) {
        $errors['workshop_date'] = "Workshop date is required";
    }

    $dayNumber = 1;
    foreach ($selectedTimes as $time) {
        if (empty($time)) {
            $errors["day{$dayNumber}_time"] = "Please select a time slot for Day $dayNumber";
        }
        $dayNumber++;
    }

    // check if workshop date is already over
    if (empty($errors)){
    $currentDate = new DateTime();
    $currentDate->setTime(0, 0, 0);
    $hasPastDate = false;

        if ($isFlexible) {
            // flexible dates
            $workshop_date_selection = $_POST['workshop_date'] ?? '';
            if (!empty($workshop_date_selection)){
                $selected_month = trim(explode('(', $workshop_date_selection)[0]);
                if (isset($workshop['dates'][$selected_month])) {
                    foreach ($workshop['dates'][$selected_month] as $dateStr) {
                        $workshopDate = DateTime::createFromFormat('Y-m-d', $dateStr);
                        if ($workshopDate && $workshopDate < $currentDate){
                            $hasPastDate = true;
                            break;
                        }
                    }
                }
            }
        } else{
            // fixed dates
            $workshop_date_selection = $_POST['workshop_date'] ?? '';
            if (!empty($workshop_date_selection) && isset($workshop['fixed_sessions'][$workshop_date_selection])){
                $dateStr = $workshop['fixed_sessions'][$workshop_date_selection]['dates'][0];
                $workshopDate = DateTime::createFromFormat('Y-m-d', $dateStr);
                if ($workshopDate && $workshopDate < $currentDate) {
                    $hasPastDate = true;
                }
            }
        }

        if ($hasPastDate) {
            $errors['workshop_date'] = "Workshop date(s) already over. Please choose another date(s).";
        }
    }

    if (empty($errors)){
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "RootFlower";
        
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error){
            die("Connection failed: " . $conn->connect_error);
        }

        // extract dates based on workshop type
        if ($isFlexible) {
            $workshop_schedule_type = 'flexible';
            $workshop_date_selection = $_POST['workshop_date'] ?? '';
            if (!empty($workshop_date_selection)) {
                $selected_month = trim(explode('(', $workshop_date_selection)[0]);
                if (isset($workshop['dates'][$selected_month])) {
                    $workshop_dates = $workshop['dates'][$selected_month];
                    $workshop_date_value = implode(', ', $workshop_dates);
                }
            }
        } else {
            $workshop_schedule_type = 'fixed';
            $workshop_date_selection = $_POST['workshop_date'] ?? '';
            if (!empty($workshop_date_selection) && isset($workshop['fixed_sessions'][$workshop_date_selection])) {
                $workshop_date_value = $workshop['fixed_sessions'][$workshop_date_selection]['dates'][0];
            }
        }

        $workshop_time_value = implode(', ', $selectedTimes);

        $check_user_exists = "SELECT email FROM user_table WHERE email = '$email'";
        $result_exists = $conn->query($check_user_exists);
        
        if ($result_exists->num_rows === 0) {
            $_SESSION['alert'] = "Email not found. Please use a registered account.";
            $_SESSION['alertType'] = "danger";
            $_SESSION['old'] = $_POST;
            $conn->close();
            header("Location: workshop_reg.php?workshop=" . $workshopKey);
            exit();
        }

        $check_duplicate = "SELECT id FROM workshop_table WHERE email = '$email' AND workshop_title = '{$workshop['title']}'";
        $result = $conn->query($check_duplicate);

        if ($result && $result->num_rows > 0){
            $_SESSION['alert'] = "This email has already registered for this workshop.";
            $_SESSION['alertType'] = "danger";
            $_SESSION['old'] = $_POST;
            header("Location: workshop_reg.php?workshop=" . $workshopKey);
            exit();
        } else{
            $sql = "INSERT INTO workshop_table (email, first_name, last_name, workshop_title, workshop_schedule_type, workshop_date, workshop_time, attendees, contact_number) VALUES ('$email', '$first_name', '$last_name', '{$workshop['title']}', '$workshop_schedule_type', '$workshop_date_value', '$workshop_time_value', $attendees, '$contact')";

            if ($conn->query($sql)){
                include_once 'include/function.php';
                createAdminNotification(
                    "New Workshop Registration\npending for review",
                    "user: $email\nworkshop: \"{$workshop['title']}\"",
                    'workshop',
                    'new_registration'
                );
                $alert = "Registration successful! Thank you for registering.";
                $alertType = "success";
                $old = [];
            } else{
                $alert = "There was an error processing your registration. Please try again.";
                $alertType = "danger";
                $old = $_POST;
            }
        }
        $conn->close();
    } else {
        $old = $_POST;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8"/>
    <meta name="author" content="Aryn Mei Wei JEE"/>
    <meta name="description" content="Workshop Registration Page"/>
    <meta name="keywords" content="Root Flower, floral class, Kuching-based"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/styles.css"/>

    <!--bootstrap framework CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">

    <title>Root Flower Workshop Registration</title>
</head>

<body>
<?php include "include/header.php" ?>

<?php if (!empty($alert)): ?>
    <div class="alert alert-<?= htmlspecialchars($alertType) ?> fade show alert-dismissable translate-middle start-50 position-fixed mt-5 w-75 text-center" role="alert">
        <?= htmlspecialchars($alert) ?>
    </div>
<?php endif; ?>

<article class="justify-content-center align-items-center d-flex my-5">
    <div class="container workshop-reg my-5 p-5 border-0 shadow rounded-2 position-relative">
        <h2 class="fw-semibold pb-5 text-center">Workshop Registration</h2>
        <form method="POST" action="" novalidate>

            <!-- personal information -->
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <input type="text" class="form-control rounded-2 p-2 <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" 
                           id="first_name" name="first_name" placeholder="First Name" 
                           value="<?= htmlspecialchars($old['first_name'] ?? '') ?>">
                    <?php if (isset($errors['first_name'])): ?>
                        <div class="error"><?= htmlspecialchars($errors['first_name']) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6 mb-3">
                    <input type="text" class="form-control rounded-2 p-2 <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>" 
                           id="last_name" name="last_name" placeholder="Last Name"
                           value="<?= htmlspecialchars($old['last_name'] ?? '') ?>">
                    <?php if (isset($errors['last_name'])): ?>
                        <div class="error"><?= htmlspecialchars($errors['last_name']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <input type="text" class="form-control rounded-2 p-2 <?= isset($errors['contact']) ? 'is-invalid' : '' ?>" 
                           id="contact" name="contact" placeholder="Contact Number"
                           value="<?= htmlspecialchars($old['contact'] ?? '') ?>">
                    <?php if (isset($errors['contact'])): ?>
                        <div class="error"><?= htmlspecialchars($errors['contact']) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6 mb-3">
                    <input type="text" class="form-control rounded-2 p-2 <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                           id="email" name="email" placeholder="Email"
                           value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                    <?php if (isset($errors['email'])): ?>
                        <div class="error"><?= htmlspecialchars($errors['email']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label for="attendees" class="form-label fw-medium">Number of Attendees</label>
                    <input type="number" class="form-control rounded-2 p-2 <?= isset($errors['attendees']) ? 'is-invalid' : '' ?>" 
                           id="attendees" name="attendees" min="1" max="10" 
                           value="<?= htmlspecialchars($old['attendees'] ?? '') ?>">
                    <?php if (isset($errors['attendees'])): ?>
                        <div class="error"><?= htmlspecialchars($errors['attendees']) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="workshop_title" class="form-label fw-medium">Workshop Title</label>
                    <input type="text" class="form-control rounded-2 p-2" id="workshop_title" 
                           value="<?= htmlspecialchars($workshop['title']) ?>" readonly>
                    <input type="hidden" name="workshop_title" value="<?= htmlspecialchars($workshop['title']) ?>">
                </div>
            </div>

            <!-- workshop date -->
            <div class="row mb-4">
                <div class="col-12 mb-3">
                    <label for="workshop_date" class="form-label fw-medium">Select Workshop Date</label>
                    <select class="form-select rounded-2 p-2 <?= isset($errors['workshop_date']) ? 'is-invalid' : '' ?>" 
                            id="workshop_date" name="workshop_date" onchange="updateSchedule(this)">
                        <option value="">Choose a date...</option>
                        
                        <?php if ($isFlexible): ?>
                        <!-- flexible dates -->
                            <?php foreach ($workshop['dates'] as $month => $dates): ?>
                                <?php $optionValue = $month . ' (' . implode('-', $dates) . ')'; ?>
                                <option value="<?= htmlspecialchars($optionValue) ?>" 
                                        <?= (($old['workshop_date'] ?? '') === $optionValue) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($month) ?> (<?= htmlspecialchars(implode(', ', $dates)) ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- fixed dates -->
                            <?php foreach ($workshop['fixed_sessions'] as $date => $sessions): ?>
                                <option value="<?= htmlspecialchars($date) ?>" 
                                        <?= (($old['workshop_date'] ?? '') === $date) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($date) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <?php if (isset($errors['workshop_date'])): ?>
                        <div class="error"><?= htmlspecialchars($errors['workshop_date']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- time slot selection -->
            <div class="row mb-4">
                <div class="col-12">
                    <?php if ($workshopType === 'single'): ?>
                        <!-- single date workshop -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="day1_time" class="form-label fw-medium">Time</label>
                                <select class="form-select rounded-2 p-2 <?= isset($errors['day1_time']) ? 'is-invalid' : '' ?>" 
                                        id="day1_time" name="day1_time">
                                    <option value="">Select time...</option>
                                    <?php foreach ($workshop['timeslots']['Single Day'] as $timeslot): ?>
                                        <option value="<?= htmlspecialchars($timeslot) ?>" 
                                                <?= (($old['day1_time'] ?? '') === $timeslot) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($timeslot) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['day1_time'])): ?>
                                    <div class="error"><?= htmlspecialchars($errors['day1_time']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    
                    <?php elseif ($workshopType === 'two_day'): ?>
                        <!-- 2 days workshop -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="day1_time" class="form-label fw-medium">Day 1 Time</label>
                                <select class="form-select rounded-2 p-2 <?= isset($errors['day1_time']) ? 'is-invalid' : '' ?>" 
                                        id="day1_time" name="day1_time">
                                    <option value="">Select time for Day 1...</option>
                                    <?php foreach ($workshop['timeslots']['Day 1'] as $timeslot): ?>
                                        <option value="<?= htmlspecialchars($timeslot) ?>" 
                                                <?= (($old['day1_time'] ?? '') === $timeslot) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($timeslot) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['day1_time'])): ?>
                                    <div class="error"><?= htmlspecialchars($errors['day1_time']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="day2_time" class="form-label fw-medium">Day 2 Time</label>
                                <select class="form-select rounded-2 p-2 <?= isset($errors['day2_time']) ? 'is-invalid' : '' ?>" 
                                        id="day2_time" name="day2_time">
                                    <option value="">Select time for Day 2...</option>
                                    <?php foreach ($workshop['timeslots']['Day 2'] as $timeslot): ?>
                                        <option value="<?= htmlspecialchars($timeslot) ?>" 
                                                <?= (($old['day2_time'] ?? '') === $timeslot) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($timeslot) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['day2_time'])): ?>
                                    <div class="error"><?= htmlspecialchars($errors['day2_time']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    
                    <?php elseif ($workshopType === 'four_day'): ?>
                        <!-- 4 days workshop -->
                        <div class="row">
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label for="day1_time" class="form-label fw-medium">Day 1 Time</label>
                                <select class="form-select rounded-2 p-2 <?= isset($errors['day1_time']) ? 'is-invalid' : '' ?>" 
                                        id="day1_time" name="day1_time">
                                    <option value="">Select time...</option>
                                    <?php foreach ($workshop['timeslots']['Day 1'] as $timeslot): ?>
                                        <option value="<?= htmlspecialchars($timeslot) ?>" 
                                                <?= (($old['day1_time'] ?? '') === $timeslot) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($timeslot) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['day1_time'])): ?>
                                    <div class="error"><?= htmlspecialchars($errors['day1_time']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label for="day2_time" class="form-label fw-medium">Day 2 Time</label>
                                <select class="form-select rounded-2 p-2 <?= isset($errors['day2_time']) ? 'is-invalid' : '' ?>" 
                                        id="day2_time" name="day2_time">
                                    <option value="">Select time...</option>
                                    <?php foreach ($workshop['timeslots']['Day 2'] as $timeslot): ?>
                                        <option value="<?= htmlspecialchars($timeslot) ?>" 
                                                <?= (($old['day2_time'] ?? '') === $timeslot) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($timeslot) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['day2_time'])): ?>
                                    <div class="error"><?= htmlspecialchars($errors['day2_time']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label for="day3_time" class="form-label fw-medium">Day 3 Time</label>
                                <select class="form-select rounded-2 p-2 <?= isset($errors['day3_time']) ? 'is-invalid' : '' ?>" 
                                        id="day3_time" name="day3_time">
                                    <option value="">Select time...</option>
                                    <?php foreach ($workshop['timeslots']['Day 3'] as $timeslot): ?>
                                        <option value="<?= htmlspecialchars($timeslot) ?>" 
                                                <?= (($old['day3_time'] ?? '') === $timeslot) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($timeslot) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['day3_time'])): ?>
                                    <div class="error"><?= htmlspecialchars($errors['day3_time']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label for="day4_time" class="form-label fw-medium">Day 4 Time</label>
                                <select class="form-select rounded-2 p-2 <?= isset($errors['day4_time']) ? 'is-invalid' : '' ?>" 
                                        id="day4_time" name="day4_time">
                                    <option value="">Select time...</option>
                                    <?php foreach ($workshop['timeslots']['Day 4'] as $timeslot): ?>
                                        <option value="<?= htmlspecialchars($timeslot) ?>" 
                                                <?= (($old['day4_time'] ?? '') === $timeslot) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($timeslot) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['day4_time'])): ?>
                                    <div class="error"><?= htmlspecialchars($errors['day4_time']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- buttons -->
            <div class="row mt-4">
                <div class="col-12 d-flex gap-3">
                    <a href="workshops.php" class="back-to-workshop text-decoration-none">
                        <i class="bi bi-arrow-left-circle fs-2"></i>
                    </a>
                    <button type="submit" class="btn btn-primary border-0">Submit</button>
                    <a href="workshop_reg.php?workshop=<?= $workshopKey ?>" class="btn btn-outline-secondary">Reset</a>
                </div>
            </div>
        </form>
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