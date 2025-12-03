<?php
session_start();
include 'include/function.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// clear session if not from form submissions
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['form_error'])) {
    unset($_SESSION['']);
}

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

// pre fill data
$edit_user = null;
$edit_email = null;

if (isset($_GET['edit'])) {
    $edit_email = $_GET['edit'];
} elseif (isset($_GET['form_error']) && $_GET['form_error'] === 'edit' && isset($_SESSION['edit_email'])) {
    $edit_email = $_SESSION['edit_email'];
    unset($_SESSION['edit_email']);
}

// form validation
if ($edit_email) {
    $edit_sql = "SELECT u.*, a.type FROM user_table u JOIN account_table a ON u.email = a.email WHERE u.email = '$edit_email'";
    $edit_result = $conn->query($edit_sql);
    if ($edit_result->num_rows > 0) {
        $edit_user = $edit_result->fetch_assoc();
    }
}

if (isset($_GET['delete'])) {
    $delete_email = $_GET['delete'];
    $delete_user = ['email' => $delete_email];
}


// handle form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // add account
    if (isset($_POST['add_account'])) {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $dob = $_POST['dob'];
        $gender = $_POST['gender'];
        $hometown = $_POST['hometown'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $type = 'user';

        $errors = [];
        
        // validation
        if (empty($first_name)){
            $errors["first_name"] = "First Name is required";
        } elseif (!preg_match("/^[a-zA-Z ]+$/", $first_name)) {
            $errors["first_name"] = "Only letters and white space allowed";
        }

        if (empty($last_name)){
            $errors["last_name"] = "Last Name is required";
        } elseif (!preg_match("/^[a-zA-Z ]+$/", $last_name)) {
            $errors["last_name"] = "Only letters and white space allowed";
        }

        if (empty($dob)){
            $errors["dob"] = "Date of Birth is required";
        }

        if (empty($email)){
            $errors["email"] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors["email"] = "Invalid email format";
        }

        if (empty($hometown)){
            $errors["hometown"] = "Hometown is required";
        }

        if (empty($password)){
            $errors["password"] = "Password is required";
        } elseif (strlen($password) < 8 || !preg_match("/[0-9]/", $password) || !preg_match("/[\W]/", $password)) {
            $errors["password"] = "Password must be at least 8 chars, including 1 number and 1 symbol";
        }

        // check for duplicated email
        if (empty($errors)){
            $check_sql = "SELECT email FROM account_table WHERE email = '$email'";
            $result = $conn->query($check_sql);
            if ($result->num_rows > 0) {
                $errors["email"] = "Email already exists";
            }
            $result->free();
        }

        if (empty($errors)){
            // upload profile image
            $profile_image = NULL;
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0){
                $target_dir = "profile_images/";
                $profile_image = $target_dir . basename($_FILES["profile_image"]["name"]);
                move_uploaded_file($_FILES["profile_image"]["tmp_name"], $profile_image);
            }

            $user_sql = "INSERT INTO user_table (email, first_name, last_name, dob, gender, hometown, profile_image) VALUES ('$email', '$first_name', '$last_name', '$dob', '$gender', '$hometown', '$profile_image')";
            $account_sql = "INSERT INTO account_table (email, password, type, otp_code, otp_expiry) VALUES ('$email', '$password', '$type', NULL, NULL)";

            if ($conn->query($user_sql) && $conn->query($account_sql)) {
                $_SESSION['alert'] = "Account added successfully!";
                $_SESSION['alertType'] = "success";
            } else {
                $_SESSION['alert'] = "Error adding account: " . $conn->error;
                $_SESSION['alertType'] = "danger";
            }
        } else{
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            header("Location: manage_accounts.php?form_error=add");
            exit(); 
        }
    }

    // edit account
    if (isset($_POST['edit_account'])){
        $original_email = $_POST['original_email'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $dob = $_POST['dob'];
        $gender = $_POST['gender'];
        $hometown = $_POST['hometown'];
        $email = $_POST['email'];
        $type = 'user';

        $errors = [];
        
        // validation
        if (empty($first_name)){
            $errors["first_name"] = "First Name is required";
        } elseif (!preg_match("/^[a-zA-Z ]+$/", $first_name)) {
            $errors["first_name"] = "Only letters and white space allowed";
        }

        if (empty($last_name)){
            $errors["last_name"] = "Last Name is required";
        } elseif (!preg_match("/^[a-zA-Z ]+$/", $last_name)) {
            $errors["last_name"] = "Only letters and white space allowed";
        }

        if (empty($dob)){
            $errors["dob"] = "Date of Birth is required";
        }

        if (empty($email)){
            $errors["email"] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors["email"] = "Invalid email format";
        }

        if (empty($hometown)){
            $errors["hometown"] = "Hometown is required";
        }

        if(empty($errors)){
            // check if gender has changes
            $original_sql = "SELECT gender, profile_image FROM user_table WHERE email = '$original_email'";
            $original_result = $conn->query($original_sql);
            $original_data = $original_result->fetch_assoc();
            $original_gender = $original_data['gender'];
            $original_profile_image = $original_data['profile_image'];

            // upload profile image
            $profile_image = $_POST['current_profile_image'];
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0){
                $target_dir = "profile_images/";
                $profile_image = $target_dir . basename($_FILES["profile_image"]["name"]);
                move_uploaded_file($_FILES["profile_image"]["tmp_name"], $profile_image);
            }elseif ($gender !== $original_gender && (empty($original_profile_image) || $original_profile_image === 'profile_images/boys.jpg' || $original_profile_image === 'profile_images/girl.png')){
                $profile_image = ($gender === 'Male') ? 'profile_images/boys.jpg' : 'profile_images/girl.png';
            }

            // update user_table
            $user_sql = "UPDATE user_table SET first_name = '$first_name', last_name = '$last_name', dob = '$dob', gender = '$gender', hometown = '$hometown', profile_image = '$profile_image', email = '$email' WHERE email = '$original_email'";

            // update account_table
            $account_sql = "UPDATE account_table SET email = '$email' WHERE email = '$original_email'";
            
            if ($conn->query($user_sql) && $conn->query($account_sql)){
                $_SESSION['alert'] = "Account updated successfully!";
                $_SESSION['alertType'] = "success";
            } else {
                $_SESSION['alert'] = "Error updating account: " . $conn->error;
                $_SESSION['alertType'] = "danger";
            }
        } else{
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            $_SESSION['edit_email'] = $original_email;
            header("Location: manage_accounts.php?form_error=edit");
            exit();
        }
    }

    // delete account
    if(isset($_POST['delete_account'])){
        $email = $_POST['delete_email'];

        $delete_sql = "DELETE FROM user_table WHERE email = '$email'";

        if ($conn->query($delete_sql)){
            $_SESSION['alert'] = "Account deleted successfully!";
            $_SESSION['alertType'] = "success";
        } else{
            $_SESSION['alert'] = "Error deleting account: " . $conn->error;
            $_SESSION['alertType'] = "danger";
        }
        header("Location: manage_accounts.php");
        exit();
    }
}

$records_current_page = 5;
$page = isset($_GET['page']) ? (int)$_GET['page']: 1;
$offset = ($page - 1) * $records_current_page;

// total records in database
$count_sql = "SELECT COUNT(*) as total FROM user_table u JOIN account_table a ON u.email = a.email";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_current_page);


$sql = "SELECT u.*, a.password, a.type, a.otp_code, a.otp_expiry FROM user_table u JOIN account_table a ON u.email = a.email LIMIT $offset, $records_current_page";
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
    <meta name="description" content="Manage Account Page"/>
    <meta name="keywords" content="Root Flower, floral class, Kuching-based"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style/styles.css"/>

    <!--bootstrap framework CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">

    <title> RootFlower Manage Account Page</title>
</head>

<body class="ps-4">
<header>
    <?php include 'include/admin_sidebar.php'?>
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
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="ml-lg-2 mb-3 fw-semibold">Manage Accounts</h2>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAccountModal"><i class="bi bi-person-add"></i> Add Account</button>
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
                            <th>Date of Birth</th>
                            <th>Gender</th>
                            <th>Hometown</th>
                            <th>Type</th>
                            <th>Profile Image</th>
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
                                    <td><?= $row['dob'] ?></td>
                                    <td><?= htmlspecialchars($row['gender']) ?></td>
                                    <td><?= htmlspecialchars($row['hometown']) ?></td>
                                    <td><?= htmlspecialchars($row['type']) ?></td>
                                    <td class="profile-image-cell fs-3">
                                        <?php 
                                            $profileImage = $row['profile_image'];
                                            if (!empty($profileImage)): 
                                        ?>
                                            <img src="<?= htmlspecialchars($profileImage) ?>" alt="Profile" class="profile-image-preview rounded-5 object-fit-cover">
                                        <?php else: ?>
                                            <div class="text-center text-muted">
                                                <i class="bi bi-person-circle"></i>
                                                <br>
                                                <p class="fs-6">No image</p>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <!-- edit and delete button -->
                                    <td class="text-nowrap">
                                        <form method="GET" action="manage_accounts.php" class="d-inline me-2">
                                            <input type="hidden" name="edit" value="<?= $row['email'] ?>">
                                            <button type="submit" class="btn btn-sm btn-warning"><i class="bi bi-pen"></i></button>
                                        </form>
                                        
                                        <form method="GET" action="manage_accounts.php" class="d-inline">
                                            <input type="hidden" name="delete" value="<?= $row['email'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php $counter++; ?>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center">No accounts found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- pagination -->
            <div class="d-flex justify-content-start align-items-center mt-3 mb-5 gap-3">
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
        

        <!-- add account modal -->
        <div class="modal fade" id="addAccountModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content themed-modal">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Account</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">  
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control <?php if (!empty($errors['first_name'])) { echo 'is-invalid'; } ?>" name="first_name" value="<?php echo htmlspecialchars(isset($old['first_name']) ? $old['first_name'] : ''); ?>">
                                        <?php if (!empty($errors['first_name'])) { ?>
                                            <div class="error"><?php echo htmlspecialchars($errors['first_name']); ?></div>
                                        <?php } ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control <?php if (!empty($errors['last_name'])) { echo 'is-invalid'; } ?>" name="last_name" value="<?php echo htmlspecialchars(isset($old['last_name']) ? $old['last_name'] : ''); ?>">
                                        <?php if (!empty($errors['last_name'])) { ?>
                                            <div class="error"><?php echo htmlspecialchars($errors['last_name']); ?></div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control <?php if (!empty($errors['dob'])) { echo 'is-invalid'; } ?>" name="dob" value="<?php echo htmlspecialchars(isset($old['dob']) ? $old['dob'] : ''); ?>">
                                        <?php if (!empty($errors['dob'])) { ?>
                                            <div class="error"><?php echo htmlspecialchars($errors['dob']); ?></div>
                                        <?php } ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Gender</label>
                                        <select class="form-control <?php if (!empty($errors['gender'])) { echo 'is-invalid'; } ?>" name="gender">
                                            <option value="Male" <?php echo (isset($old['gender']) && $old['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo (isset($old['gender']) && $old['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                        <?php if (!empty($errors['gender'])) { ?>
                                            <div class="error"><?php echo htmlspecialchars($errors['gender']); ?></div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Hometown</label>
                                <input type="text" class="form-control <?php if (!empty($errors['hometown'])) { echo 'is-invalid'; } ?>" name="hometown" value="<?php echo htmlspecialchars(isset($old['hometown']) ? $old['hometown'] : ''); ?>">
                                <?php if (!empty($errors['hometown'])) { ?>
                                    <div class="error"><?php echo htmlspecialchars($errors['hometown']); ?></div>
                                <?php } ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Profile Image</label>
                                <input type="file" class="form-control" name="profile_image" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control <?php if (!empty($errors['email'])) { echo 'is-invalid'; } ?>" name="email" value="<?php echo htmlspecialchars(isset($old['email']) ? $old['email'] : ''); ?>">
                                <?php if (!empty($errors['email'])) { ?>
                                    <div class="error"><?php echo htmlspecialchars($errors['email']); ?></div>
                                <?php } ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="text"class="form-control <?php if (!empty($errors['password'])) { echo 'is-invalid'; } ?>" name="password" value="<?php echo htmlspecialchars(isset($old['password']) ? $old['password'] : ''); ?>">
                                <?php if (!empty($errors['password'])) { ?>
                                    <div class="error"><?php echo htmlspecialchars($errors['password']); ?></div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_account" class="btn btn-primary">Add Account</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        <!-- edit account modal -->
        <div class="modal fade" id="editAccountModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content themed-modal">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Account</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="original_email" value="<?= $edit_user ? htmlspecialchars($edit_user['email']) : '' ?>">
                            <input type="hidden" name="current_profile_image" value="<?= $edit_user ? htmlspecialchars($edit_user['profile_image'] ?? '') : '' ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control <?= !empty($errors['first_name']) ? 'is-invalid' : '' ?>" name="first_name" value="<?= !empty($errors['first_name']) && isset($old['first_name']) ? htmlspecialchars($old['first_name']) : ($edit_user ? htmlspecialchars($edit_user['first_name']) : '') ?>">
                                        <?php if (!empty($errors['first_name'])): ?>
                                            <div class="invalid-feedback"><?= htmlspecialchars($errors['first_name']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control <?= !empty($errors['last_name']) ? 'is-invalid' : '' ?>" name="last_name" value="<?= !empty($errors['last_name']) && isset($old['last_name']) ? htmlspecialchars($old['last_name']) : ($edit_user ? htmlspecialchars($edit_user['last_name']) : '')
                                        ?>">
                                        <?php if (!empty($errors['last_name'])): ?>
                                            <div class="invalid-feedback"><?= htmlspecialchars($errors['last_name']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control <?= !empty($errors['dob']) ? 'is-invalid' : '' ?>" name="dob" value="<?= !empty($errors['dob']) && isset($old['dob']) ?htmlspecialchars($old['dob']) : ($edit_user ? htmlspecialchars($edit_user['dob']) : '') ?>">
                                        <?php if (!empty($errors['dob'])): ?>
                                            <div class="invalid-feedback"><?= htmlspecialchars($errors['dob']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Gender</label>
                                        <select class="form-control <?= !empty($errors['gender']) ? 'is-invalid' : '' ?>" name="gender">
                                            <option value="Male" <?= 
                                                (!empty($errors['gender']) && isset($old['gender']) && $old['gender'] === 'Male') || 
                                                ($edit_user && $edit_user['gender'] === 'Male') ? 'selected' : ''
                                            ?>>Male</option>
                                            <option value="Female" <?= 
                                                (!empty($errors['gender']) && isset($old['gender']) && $old['gender'] === 'Female') || 
                                                ($edit_user && $edit_user['gender'] === 'Female') ? 'selected' : ''
                                            ?>>Female</option>
                                        </select>
                                        <?php if (!empty($errors['gender'])): ?>
                                            <div class="invalid-feedback"><?= htmlspecialchars($errors['gender']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Hometown</label>
                                <input type="text" class="form-control <?= !empty($errors['hometown']) ? 'is-invalid' : '' ?>" name="hometown" value="<?= !empty($errors['hometown']) && isset($old['hometown']) ? htmlspecialchars($old['hometown']) : ($edit_user ? htmlspecialchars($edit_user['hometown']) : '') ?>">
                                <?php if (!empty($errors['hometown'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['hometown']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Profile Image</label>
                                <input type="file" class="form-control" name="profile_image" accept="image/*">
                                <p class="text-muted">
                                    Current: <?= $edit_user && !empty($edit_user['profile_image']) ? htmlspecialchars(basename($edit_user['profile_image'])) : 'None' ?>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" name="email" value="<?= !empty($errors['email']) && isset($old['email']) ?htmlspecialchars($old['email']) : ($edit_user ? htmlspecialchars($edit_user['email']) : '') ?>">
                                <?php if (!empty($errors['email'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="edit_account" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        <!-- delete account modal -->
        <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content themed-modal">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteAccountModalLabel">Delete Account</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="delete_email" value="<?= $delete_user ? htmlspecialchars($delete_user['email']) : '' ?>">
                            <p>Are you sure you want to delete this account?</p>
                            <p><strong>Email: <?= $delete_user ? htmlspecialchars($delete_user['email']) : 'Unknown' ?></strong></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="delete_account" class="btn btn-danger">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</article>

<?php
include 'include/admin_footer.php' ?>

<!-- bootstrap framework Javascript-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script> 

<!-- keep modal open with validation error with URL parameter -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const formError = urlParams.get('form_error');
    const hasAddErrors = <?= !empty($errors) && isset($_POST['add_account']) ? 'true' : 'false' ?>;
    const hasEditErrors = <?= !empty($errors) && isset($_POST['edit_account']) ? 'true' : 'false' ?>;
    
    if (formError === 'add' || hasAddErrors) {
        const addModal = new bootstrap.Modal(document.getElementById('addAccountModal'));
        addModal.show();
    }
    
    if (formError === 'edit' || hasEditErrors) {
        const editModal = new bootstrap.Modal(document.getElementById('editAccountModal'));
        editModal.show();
    }

    <?php if (isset($_GET['edit']) && $edit_user): ?>
    const editUrlModal = new bootstrap.Modal(document.getElementById('editAccountModal'));
    editUrlModal.show();
    <?php endif; ?>
    
    <?php if (isset($_GET['delete']) && $delete_user): ?>
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteAccountModal'));
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