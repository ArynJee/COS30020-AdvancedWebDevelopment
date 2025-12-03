<?php
session_start();
include 'include/function.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// clear session if not from form submissions
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && 
    (!isset($_GET['form_error']) || $_GET['form_error'] !== 'add2') &&
    empty($errors)) {
    unset($_SESSION['workshop_reg_data']);
}

// check if open #workshopOptionModal
$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "RootFlower";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$filteredWorkshops = $workshops;
// prefill data
$edit_workshop = null;
$delete_workshop = null;

if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_sql = "SELECT * FROM workshop_table WHERE id = $edit_id";
    $edit_result = $conn->query($edit_sql);
    
    if ($edit_result->num_rows > 0) {
        $edit_workshop = $edit_result->fetch_assoc();
    }
}

// form validation
if (isset($_GET['form_error']) && $_GET['form_error'] === 'edit' && isset($_SESSION['edit_id'])) {
    $edit_id = $_SESSION['edit_id'];
    $edit_sql = "SELECT * FROM workshop_table WHERE id = $edit_id";
    $edit_result = $conn->query($edit_sql);
    
    if ($edit_result->num_rows > 0) {
        $edit_workshop = $edit_result->fetch_assoc();
    }
    unset($_SESSION['edit_id']);
}

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $delete_sql = "SELECT * FROM workshop_table WHERE id = $delete_id";
    $delete_result = $conn->query($delete_sql);
    
    if ($delete_result->num_rows > 0) {
        $delete_workshop = $delete_result->fetch_assoc();
    }
}

// handle form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // add workshop registration
    if(isset($_POST['add_workshop_reg'])){
        $email = trim($_POST['email'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $workshop_title = trim($_POST['workshop_title'] ?? '');
        $attendees = trim($_POST['attendees'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        
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
        
        if (empty($contact_number)) {
            $errors['contact_number'] = "Contact Number is required";
        } elseif (!preg_match("/^[0-9+\-\s()]+$/", $contact_number)) {
            $errors['contact_number'] = "Invalid contact number format";
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
        
        if (empty($workshop_title)) {
            $errors['workshop_title'] = "Workshop title is required";
        }

        // check for duplicate email for the same workshop
        if(empty($errors)){
            $check_sql = "SELECT email FROM workshop_table WHERE email = '$email' AND workshop_title = '$workshop_title'";
            $result = $conn->query($check_sql);
            if ($result->num_rows > 0) {
                $errors["email"] = "Email already registered for this workshop";
            }
            $result->free();
        }

        if(empty($errors)){
            $_SESSION['workshop_reg_data'] = [
                'email' => $email,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'workshop_title' => $workshop_title,
                'attendees' => $attendees,
                'contact_number' => $contact_number
            ];
            header("Location: manage_workshop_reg.php?form_error=add2");
            exit();
        }else{
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            header("Location: manage_workshop_reg.php?form_error=add");
            exit();
        }
    }

    // choose workshop date and time
    if(isset($_POST['add_workshop_reg2'])){
        if (!isset($_SESSION['workshop_reg_data'])) {
            $_SESSION['alert'] = "Session expired. Please start over.";
            $_SESSION['alertType'] = "danger";
            header("Location: manage_workshop_reg.php");
            exit();
        }
        
        $workshop_data = $_SESSION['workshop_reg_data'];
        $workshop_date = trim($_POST['workshop_date'] ?? '');
        
        // get timeslots based on workshop type
        $selectedTimes = [];
        $current_workshop = null;
        foreach ($filteredWorkshops as $workshop) {
            if ($workshop['title'] === $workshop_data['workshop_title']) {
                $current_workshop = $workshop;
                break;
            }
        }
        
        if ($current_workshop) {
            $workshopType = getWorkshopDurationType($current_workshop);
            
            if ($workshopType === 'single') {
                $selectedTimes[] = $_POST['day1_time'] ?? '';
            } elseif ($workshopType === 'two_day') {
                $selectedTimes[] = $_POST['day1_time'] ?? '';
                $selectedTimes[] = $_POST['day2_time'] ?? '';
            } elseif ($workshopType === 'four_day') {
                $selectedTimes[] = $_POST['day1_time'] ?? '';
                $selectedTimes[] = $_POST['day2_time'] ?? '';
                $selectedTimes[] = $_POST['day3_time'] ?? '';
                $selectedTimes[] = $_POST['day4_time'] ?? '';
            }
        }
        
        $workshop_time_value = implode(', ', $selectedTimes);
        $status = 'pending';
        
        $errors = [];
        
        if (empty($workshop_date)) {
            $errors['workshop_date'] = "Workshop date is required";
        }
        
        // validation
        $dayNumber = 1;
        foreach ($selectedTimes as $time) {
            if (empty($time)) {
                $errors["day{$dayNumber}_time"] = "Please select a time slot for Day $dayNumber";
            }
            $dayNumber++;
        }

        if(empty($errors)){
            $workshop_schedule_type = '';
            
            foreach ($workshops as $key => $workshop){
                if ($workshop['title'] === $workshop_data['workshop_title']){
                    $workshop_schedule_type = $workshop['template'] === 'flexible_dates' ? 'flexible' : 'fixed';
                    break;
                }
            }
            
            $sql = "INSERT INTO workshop_table (email, first_name, last_name, workshop_title, workshop_schedule_type, workshop_date, workshop_time, attendees, contact_number, status) VALUES ('{$workshop_data['email']}', '{$workshop_data['first_name']}', '{$workshop_data['last_name']}', '{$workshop_data['workshop_title']}', '$workshop_schedule_type', '$workshop_date', '$workshop_time_value', {$workshop_data['attendees']}, '{$workshop_data['contact_number']}', '$status')";
            
            if ($conn->query($sql)) {
                $_SESSION['alert'] = "Workshop registration added successfully!";
                $_SESSION['alertType'] = "success";
                unset($_SESSION['workshop_reg_data']);
            } else {
                $_SESSION['alert'] = "Error adding workshop registration: " . $conn->error;
                $_SESSION['alertType'] = "danger";
            }
            header("Location: manage_workshop_reg.php");
            exit();
        } else {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            header("Location: manage_workshop_reg.php?form_error=add2");
            exit();
        }
    }

    // edit workshop registration 
    if (isset($_POST['edit_workshop_reg'])){
        $id = $_POST['id'];
        $workshop_date = $_POST['workshop_date'];
        $attendees = $_POST['attendees'];

        $selectedTimes = [];
        
        // get the current workshop data to determine workshop type
        $current_sql = "SELECT * FROM workshop_table WHERE id = $id";
        $current_result = $conn->query($current_sql);
        $current_data = $current_result->fetch_assoc();
        
        $workshop_title = $current_data['workshop_title'];
        
        // find the workshop to determine type
        $current_workshop = null;
        foreach ($filteredWorkshops as $workshop) {
            if ($workshop['title'] === $workshop_title) {
                $current_workshop = $workshop;
                break;
            }
        }
        
        // collect time slots based on workshop type
        if ($current_workshop) {
            $workshopType = getWorkshopDurationType($current_workshop);
            
            if ($workshopType === 'single') {
                $selectedTimes[] = $_POST['day1_time'] ?? '';
            } elseif ($workshopType === 'two_day') {
                $selectedTimes[] = $_POST['day1_time'] ?? '';
                $selectedTimes[] = $_POST['day2_time'] ?? '';
            } elseif ($workshopType === 'four_day') {
                $selectedTimes[] = $_POST['day1_time'] ?? '';
                $selectedTimes[] = $_POST['day2_time'] ?? '';
                $selectedTimes[] = $_POST['day3_time'] ?? '';
                $selectedTimes[] = $_POST['day4_time'] ?? '';
            }
        }
        
        $workshop_time_value = implode(', ', $selectedTimes);

        $errors = [];

        // validation
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

        if (empty($attendees)){
            $errors['attendees'] = "Number of attendees is required";
        } elseif (!is_numeric($attendees) || $attendees < 1 || $attendees > 10) {
            $errors['attendees'] = "Please enter a valid number of attendees (1-10)";
        }

        if(empty($errors)){
            $user_sql = "SELECT email, workshop_title FROM workshop_table WHERE id = $id";
            $user_result = $conn->query($user_sql);
            $user_data = $user_result->fetch_assoc();
            $sql = "UPDATE workshop_table SET workshop_date = '$workshop_date', workshop_time = '$workshop_time_value', attendees = $attendees WHERE id = $id";
            
            if ($conn->query($sql)){
                createNotification(
                    $user_data['email'],
                    "Workshop Updated",
                    "Your workshop \"{$user_data['workshop_title']}\" details has been updated by the admin.",
                    "workshop",
                    "updated"
                );

                $_SESSION['alert'] = "Workshop registration updated successfully!";
                $_SESSION['alertType'] = "success";
            } else{
                $_SESSION['alert'] = "Error updating workshop registration: " . $conn->error;
                $_SESSION['alertType'] = "danger";
            }
            header("Location: manage_workshop_reg.php");
            exit();
        } else{
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            $_SESSION['edit_id'] = $id;
            header("Location: manage_workshop_reg.php?form_error=edit");
            exit();
        }
    }

    // delete workshop registration
    if(isset($_POST['delete_workshop_reg'])){
        $id = $_POST['delete_id'];
        
        $user_sql = "SELECT email, workshop_title FROM workshop_table WHERE id = $id";
        $user_result = $conn->query($user_sql);

        if ($user_result->num_rows > 0) {
            $user_data = $user_result->fetch_assoc();

            $delete_sql = "DELETE FROM workshop_table WHERE id = $id";

            if ($conn->query($delete_sql)){
                createNotification(
                    $user_data['email'],
                    "Workshop Registration Deleted",
                    "Your workshop registration for \"{$user_data['workshop_title']}\" has been deleted by the admin.",
                    "workshop",
                    "deleted"
                );

                $_SESSION['alert'] = "Workshop registration deleted successfully!";
                $_SESSION['alertType'] = "success";
            } else{
                $_SESSION['alert'] = "Error deleting workshop registration: " . $conn->error;
                $_SESSION['alertType'] = "danger";
            }
        }else{
            $_SESSION['alert'] = "Workshop registration not found.";
            $_SESSION['alertType'] = "danger";           
        }
        header("Location: manage_workshop_reg.php");
        exit();
    }

    // update status 
    if(isset($_POST['update_status_btn'])){
        $id = $_POST['id'] ?? null;
        $status = $_POST['status'] ?? null;

        $user_sql = "SELECT email, workshop_title FROM workshop_table WHERE id = $id";
        $user_result = $conn->query($user_sql);
        $user_data = $user_result->fetch_assoc();

        $sql = "UPDATE workshop_table SET status = '$status' WHERE id = $id";

        if ($conn->query($sql)){
            $action = $status;
            createNotification(
                $user_data['email'],
                "Workshop " . ucfirst($status),
                "Your workshop \"{$user_data['workshop_title']}\" has been $status",
                'workshop',
                $action
            );

            $_SESSION['alert'] = "Status updated successfully!";
            $_SESSION['alertType'] = "success";
        } else{
            $_SESSION['alert'] = "Error updating status.";
            $_SESSION['alertType'] = "danger";
        }
        header("Location: manage_workshop_reg.php");
        exit();
    }
}

// workshop titles for dropdown
$workshop_titles = [];
foreach ($filteredWorkshops as $workshop) {
    $hasAvailableDates = false;
    
    if ($workshop['template'] === 'flexible_dates') {
        $hasAvailableDates = !empty($workshop['dates']);
    } else {
        $hasAvailableDates = !empty($workshop['fixed_sessions']);
    }
    
    if ($hasAvailableDates) {
        $workshop_titles[] = $workshop['title'];
    }
}

$records_current_page = 5;
$page = isset($_GET['page']) ? (int)$_GET['page']: 1;
$offset = ($page - 1) * $records_current_page;

// total records in database
$count_sql = "SELECT COUNT(*) as total FROM workshop_table";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_current_page);

$sql = "SELECT * FROM workshop_table ORDER BY registration_date DESC LIMIT $offset, $records_current_page";
$result = $conn->query($sql);

$alert = $_SESSION['alert'] ?? '';
$alertType = $_SESSION['alertType'] ?? '';
unset($_SESSION['alert'], $_SESSION['alertType']);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="author" content="Aryn Mei Wei JEE"/>
    <meta name="description" content="Manage Workshop Registration Page"/>
    <meta name="keywords" content="Root Flower, floral class, Kuching-based"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/styles.css"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <title>RootFlower Manage Workshop Registration Page</title>
</head>

<body class="ps-4">
<header>
    <?php include "include/admin_sidebar.php" ?>
</header>

<article class="admin-side">
    <div class="container-fluid">
        <?php if (!empty($alert)): ?>
            <div class="alert alert-<?= htmlspecialchars($alertType) ?> fade show alert-dismissable translate-middle start-50 position-fixed mt-1 ms-5 text-center w-60" role="alert">
                <?= htmlspecialchars($alert) ?>
            </div>
        <?php endif; ?>

        <div class="table-wrapper">
            <div class="table-title">
                <div class="row">
                    <div class="d-flex align-items-center justify-content-between">
                        <h2 class="ml-lg-2 mb-3 fw-semibold">Manage Workshop Registration</h2>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addWorkshopRegModal"><i class="bi bi-person-add"></i> Add Workshop Registration</button>
                    </div>
                </div>
            </div>

            <div class="table-container overflow-x-scroll w-100">
                <table class="table table-bordered table-striped">
                    <thead class="text-center">
                        <tr>
                            <th>No.</th>
                            <th>Email</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Workshop Title</th>
                            <th>Workshop Date</th>
                            <th>Workshop Time</th>
                            <th>Attendees</th>
                            <th>Registration Date</th>
                            <th>Contact Number</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-center table-group-divider">
                        <?php if ($result->num_rows > 0): ?>
                            <?php $counter = $offset + 1; ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $counter ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['first_name']) ?></td>
                                    <td><?= htmlspecialchars($row['last_name']) ?></td>
                                    <td><?= htmlspecialchars($row['workshop_title']) ?></td>
                                    <td>
                                        <div class="overflow-hidden text-wrap" title="<?= htmlspecialchars($row['workshop_date']) ?>"> 
                                            <?= htmlspecialchars($row['workshop_date']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="overflow-hidden text-wrap" title="<?= htmlspecialchars($row['workshop_time']) ?>"> 
                                            <?= htmlspecialchars($row['workshop_time']) ?>
                                        </div>
                                    </td>
                                    <td><?= $row['attendees'] ?></td>
                                    <td><?= $row['registration_date']?></td>
                                    <td><?= $row['contact_number'] ?></td>
                                    <td>
                                        <?php 
                                            $currentStatus = $row['status'];
                                            $isApproved = $currentStatus === 'approved';
                                            $isRejected = $currentStatus === 'rejected';
                                        ?>
                                        
                                        <!-- approve -->
                                        <form method="POST" class="d-inline-block">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" name="update_status_btn" class="btn btn-sm mb-1 <?= $isApproved ? 'btn-outline-secondary' : 'btn-outline-success' ?>" <?= $isApproved ? 'disabled' : '' ?>>
                                                Approve
                                            </button>
                                        </form>
                                        
                                        <!-- reject -->
                                        <form method="POST" class="d-inline-block">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" name="update_status_btn" class="btn btn-sm px-3 <?= $isRejected ? 'btn-outline-secondary' : 'btn-outline-danger' ?>" <?= $isRejected ? 'disabled' : '' ?>>
                                                Reject
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-nowrap">
                                        <div class="btn-group">
                                            <!-- edit and delete button -->
                                            <form method="GET" class="d-inline me-2">
                                                <input type="hidden" name="edit" value="<?= $row['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pen"></i>
                                                </button>
                                            </form>
                                            
                                            <form method="GET" class="d-inline">
                                                <input type="hidden" name="delete" value="<?= $row['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php $counter++; ?>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="12" class="text-center">No workshop registrations found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- pagination -->
            <div class="d-flex justify-content-start align-items-center mt-3 mb-5 ms-4 gap-3">
                <div class="hint-text">Showing <?= min($records_current_page, $result->num_rows) ?> out of <?= $total_records ?> entries</div>
                <ul class="pagination mb-0">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </div>
        </div>

        <!-- add workshop registration modal -->
        <div class="modal fade" id="addWorkshopRegModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content themed-modal">
                    <form method="POST" id="addWorkshopForm">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Workshop Registration</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">  
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control <?= !empty($errors['first_name']) ? 'is-invalid' : '' ?>" name="first_name" value="<?= htmlspecialchars($old['first_name'] ?? '') ?>">
                                        <?php if (!empty($errors['first_name'])): ?>
                                            <div class="error"><?= htmlspecialchars($errors['first_name']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control <?= !empty($errors['last_name']) ? 'is-invalid' : '' ?>" name="last_name" value="<?= htmlspecialchars($old['last_name'] ?? '') ?>">
                                        <?php if (!empty($errors['last_name'])): ?>
                                            <div class="error"><?= htmlspecialchars($errors['last_name']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                                <?php if (!empty($errors['email'])): ?>
                                    <div class="error"><?= htmlspecialchars($errors['email']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Workshop Title</label>
                                <select class="form-control <?= !empty($errors['workshop_title']) ? 'is-invalid' : '' ?>" name="workshop_title" id="add_workshop_title">
                                    <option value="">Select Workshop</option>
                                    <?php foreach ($workshop_titles as $title): ?>
                                        <option value="<?= htmlspecialchars($title) ?>" <?= ($old['workshop_title'] ?? '') === $title ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($title) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (!empty($errors['workshop_title'])): ?>
                                    <div class="error"><?= htmlspecialchars($errors['workshop_title']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Attendees</label>
                                <input type="number" class="form-control <?= !empty($errors['attendees']) ? 'is-invalid' : '' ?>" name="attendees" value="<?= $old['attendees'] ?? '' ?>">
                                <?php if (!empty($errors['attendees'])): ?>
                                    <div class="error"><?= htmlspecialchars($errors['attendees']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="text" class="form-control <?= !empty($errors['contact_number']) ? 'is-invalid' : '' ?>" name="contact_number" value="<?= htmlspecialchars($old['contact_number'] ?? '') ?>">
                                <?php if (!empty($errors['contact_number'])): ?>
                                    <div class="error"><?= htmlspecialchars($errors['contact_number']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_workshop_reg" class="btn btn-primary" href="manage_workshop_reg.php?form_error=add2">Confirm</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- edit workshop registration modal -->
        <?php if ($edit_workshop): ?>
        <div class="modal fade modal-lg" id="editWorkshopRegModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content themed-modal">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Workshop Registration</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id" value="<?= $edit_workshop['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label">Workshop Title</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($edit_workshop['workshop_title']) ?>" readonly>
                            </div>

                            <?php
                                $current_workshop = null;
                                $workshopType = 'single';
                                foreach ($filteredWorkshops as $workshop) {
                                    if ($workshop['title'] === $edit_workshop['workshop_title']) {
                                        $current_workshop = $workshop;
                                        $workshopType = getWorkshopDurationType($workshop);
                                        break;
                                    }
                                }
                            ?>
                            
                            <div class="mb-3">
                                <label class="form-label">Workshop Date</label>
                                    <select class="form-control <?= !empty($errors['workshop_date']) ? 'is-invalid' : '' ?>" name="workshop_date">
                                    <option value="">Select Date</option>
                                    <?php 
                                    if ($current_workshop['template'] === 'flexible_dates'):
                                        foreach ($current_workshop['dates'] as $month => $dates): 
                                            $optionValue = $month . ' (' . implode(', ', $dates) . ')';
                                        ?>
                                            <option value="<?= htmlspecialchars($optionValue) ?>" <?= ($old['workshop_date'] ?? '') === $optionValue ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($month) ?> (<?= htmlspecialchars(implode(', ', $dates)) ?>)
                                            </option>
                                        <?php endforeach;
                                    else:
                                        foreach ($current_workshop['fixed_sessions'] as $date => $sessionData): ?>
                                            <option value="<?= htmlspecialchars($date) ?>" <?= ($old['workshop_date'] ?? '') === $date ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($date) ?>
                                            </option>
                                        <?php endforeach;
                                    endif;
                                    ?>
                                </select>
                                <?php if (!empty($errors['workshop_date'])): ?>
                                    <div class="error"><?= htmlspecialchars($errors['workshop_date']) ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Workshop Time</label>
                                
                                <?php if ($workshopType === 'single'): ?>
                                    <!-- single date workshop -->
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <select class="form-select rounded-2 p-2 <?= isset($errors['day1_time']) ? 'is-invalid' : '' ?>" 
                                                    id="day1_time" name="day1_time">
                                                <option value="">Select time...</option>
                                                <?php foreach ($current_workshop['timeslots']['Single Day'] as $timeslot): ?>
                                                    <option value="<?= htmlspecialchars($timeslot) ?>" 
                                                            <?= (($old['day1_time'] ?? $edit_workshop['workshop_time']) === $timeslot) ? 'selected' : '' ?>>
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
                                                <?php foreach ($current_workshop['timeslots']['Day 1'] as $timeslot): ?>
                                                    <option value="<?= htmlspecialchars($timeslot) ?>" 
                                                            <?= (($old['day1_time'] ?? explode(', ', $edit_workshop['workshop_time'])[0]) === $timeslot) ? 'selected' : '' ?>>
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
                                                <?php foreach ($current_workshop['timeslots']['Day 2'] as $timeslot): ?>
                                                    <option value="<?= htmlspecialchars($timeslot) ?>" 
                                                            <?= (($old['day2_time'] ?? explode(', ', $edit_workshop['workshop_time'])[1]) === $timeslot) ? 'selected' : '' ?>>
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
                                                <?php foreach ($current_workshop['timeslots']['Day 1'] as $timeslot): ?>
                                                    <option value="<?= htmlspecialchars($timeslot) ?>" 
                                                            <?= (($old['day1_time'] ?? explode(', ', $edit_workshop['workshop_time'])[0]) === $timeslot) ? 'selected' : '' ?>>
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
                                                <?php foreach ($current_workshop['timeslots']['Day 2'] as $timeslot): ?>
                                                    <option value="<?= htmlspecialchars($timeslot) ?>" 
                                                            <?= (($old['day2_time'] ?? explode(', ', $edit_workshop['workshop_time'])[1]) === $timeslot) ? 'selected' : '' ?>>
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
                                                <?php foreach ($current_workshop['timeslots']['Day 3'] as $timeslot): ?>
                                                    <option value="<?= htmlspecialchars($timeslot) ?>" 
                                                            <?= (($old['day3_time'] ?? explode(', ', $edit_workshop['workshop_time'])[2]) === $timeslot) ? 'selected' : '' ?>>
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
                                                <?php foreach ($current_workshop['timeslots']['Day 4'] as $timeslot): ?>
                                                    <option value="<?= htmlspecialchars($timeslot) ?>" 
                                                            <?= (($old['day4_time'] ?? explode(', ', $edit_workshop['workshop_time'])[3]) === $timeslot) ? 'selected' : '' ?>>
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
                            
                            <div class="mb-3">
                                <label class="form-label">Attendees</label>
                                <input type="number" class="form-control <?= !empty($errors['attendees']) ? 'is-invalid' : '' ?>" name="attendees" value="<?= !empty($errors['attendees']) ? ($old['attendees'] ?? '') : $edit_workshop['attendees'] ?>">
                                <?php if (!empty($errors['attendees'])): ?>
                                    <div class="error"><?= htmlspecialchars($errors['attendees']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="edit_workshop_reg" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- workshop date/month option modal -->
        <div class="modal fade" id="workshopOptionModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content themed-modal">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Select Workshop Date & Time</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                        <?php
                            $current_workshop = null;
                            $workshopType = 'single';
                            if (isset($_SESSION['workshop_reg_data'])){
                                foreach ($filteredWorkshops as $workshop){
                                    if ($workshop['title'] === $_SESSION['workshop_reg_data']['workshop_title']) {
                                        $current_workshop = $workshop;
                                        $workshopType = getWorkshopDurationType($workshop);
                                        break;
                                    }
                                }
                            }
                        ?>
                            
                            <?php if ($current_workshop): ?>
                                <div class="mb-4">
                                    <label class="form-label">Workshop Date</label>
                                    <select class="form-control <?= !empty($errors['workshop_date']) ? 'is-invalid' : '' ?>" name="workshop_date">
                                        <option value="">Select Date</option>
                                        <?php 
                                        if ($current_workshop['template'] === 'flexible_dates'):
                                            foreach ($current_workshop['dates'] as $month => $dates): 
                                                $optionValue = $month . ' (' . implode(', ', $dates) . ')';
                                            ?>
                                                <option value="<?= htmlspecialchars($optionValue) ?>" <?= ($old['workshop_date'] ?? '') === $optionValue ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($month) ?> (<?= htmlspecialchars(implode(', ', $dates)) ?>)
                                                </option>
                                            <?php endforeach;
                                        else:
                                            foreach ($current_workshop['fixed_sessions'] as $date => $sessionData): ?>
                                                <option value="<?= htmlspecialchars($date) ?>" <?= ($old['workshop_date'] ?? '') === $date ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($date) ?>
                                                </option>
                                            <?php endforeach;
                                        endif;
                                        ?>
                                    </select>
                                    <?php if (!empty($errors['workshop_date'])): ?>
                                        <div class="error"><?= htmlspecialchars($errors['workshop_date']) ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Workshop Time</label>
                                    
                                    <?php if ($workshopType === 'single'): ?>
                                        <!-- single date workshop -->
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <select class="form-select rounded-2 p-2 <?= isset($errors['day1_time']) ? 'is-invalid' : '' ?>" 
                                                        id="day1_time" name="day1_time">
                                                    <option value="">Select time...</option>
                                                    <?php foreach ($current_workshop['timeslots']['Single Day'] as $timeslot): ?>
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
                                                    <?php foreach ($current_workshop['timeslots']['Day 1'] as $timeslot): ?>
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
                                                    <?php foreach ($current_workshop['timeslots']['Day 2'] as $timeslot): ?>
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
                                                    <?php foreach ($current_workshop['timeslots']['Day 1'] as $timeslot): ?>
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
                                                    <?php foreach ($current_workshop['timeslots']['Day 2'] as $timeslot): ?>
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
                                                    <?php foreach ($current_workshop['timeslots']['Day 3'] as $timeslot): ?>
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
                                                    <?php foreach ($current_workshop['timeslots']['Day 4'] as $timeslot): ?>
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
                            <?php else: ?>
                                <div class="alert alert-danger">Workshop data not found. Please start over.</div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_workshop_reg2" class="btn btn-primary">Confirm Registration</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- delete workshop registration modal -->
        <?php if ($delete_workshop): ?>
        <div class="modal fade" id="deleteWorkshopRegModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content themed-modal">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Delete Workshop Registration</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="delete_id" value="<?= $delete_workshop['id'] ?>">
                            <p>Are you sure you want to delete this workshop registration?</p>
                            <p class="fw-semibold">Email: <?= htmlspecialchars($delete_workshop['email']) ?></p>
                            <p class="fw-semibold">Workshop: <?= htmlspecialchars($delete_workshop['workshop_title']) ?></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="delete_workshop_reg" class="btn btn-danger">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</article>

<?php 
include 'include/admin_footer.php' ?>

<!-- bootstrap framework Javascript-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script> 
    
<!-- keep modal open with validation error with URL parameter -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const formError = urlParams.get('form_error');
    
    if (formError === 'add') {
        const addModal = new bootstrap.Modal(document.getElementById('addWorkshopRegModal'));
        addModal.show();
    }
    
    if (formError === 'edit') {
        const editModal = new bootstrap.Modal(document.getElementById('editWorkshopRegModal'));
        editModal.show();
    }

    if (formError === 'add2') {
        const optionModal = new bootstrap.Modal(document.getElementById('workshopOptionModal'));
        optionModal.show();

        const addModal = bootstrap.Modal.getInstance(document.getElementById('addWorkshopRegModal'));
            if (addModal) {
                addModal.hide();
            }
    }

    <?php if (isset($_GET['edit']) && $edit_workshop): ?>
        const editUrlModal = new bootstrap.Modal(document.getElementById('editWorkshopRegModal'));
        editUrlModal.show();
    <?php endif; ?>
    
    <?php if (isset($_GET['delete']) && $delete_workshop): ?>
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteWorkshopRegModal'));
        deleteModal.show();
    <?php endif; ?>
});
</script>

<!-- clear URL -->
<?php if ((isset($_GET['edit']) || isset($_GET['delete']) || isset($_GET['form_error'])) && empty($errors)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.history.replaceState) {
                const clearURL = window.location.pathname;
                window.history.replaceState(null, null, clearURL);
            }
        });
    </script>
<?php endif; ?>
<!-- javascript -->
<script src="java/main.js"></script>
</body>
</html>