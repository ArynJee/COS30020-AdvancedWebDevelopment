<?php
session_start();
include 'include/function.php';

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "RootFlower";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error){
    die("Connection failed: " . $conn->connect_error);
}

$old = $_SESSION['old'] ?? [];
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['old'], $_SESSION['errors']);

$showForgot = $_SESSION['showForgot'] ?? false;
$showOtp    = $_SESSION['showOtp'] ?? false;
$showReset  = $_SESSION['showReset'] ?? false;
unset($_SESSION['showForgot'], $_SESSION['showOtp'], $_SESSION['showReset']);

if (isset($_POST['login'])){
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    $sql = "SELECT a.email, a.password, a.type, u.first_name, u.last_name 
            FROM account_table a 
            JOIN user_table u ON a.email = u.email 
            WHERE a.email = '$email'";
    $result = $conn->query($sql);
    
    if ($result->num_rows === 1){
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])){
            $_SESSION['user'] = [
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'type' => $user['type']
            ];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_type'] = $user['type'];
            
            $_SESSION['alert'] = "Login successful!";
            $_SESSION['alertType'] = "success";

            if ($user['type'] === 'admin'){
                header("Location: main_menu_admin.php");
            } else{
                header("Location: main_menu.php");
            }
            exit();
        } else{
            $_SESSION['alert'] = "Login failed. Incorrect email or password.";
            $_SESSION['alertType'] = "danger";
        }
    } else{
        $_SESSION['alert'] = "Login failed. Incorrect email or password.";
        $_SESSION['alertType'] = "danger";
    }
    
    $_SESSION['old'] = ['email' => $email];
    header("Location: login.php");
    exit();
}

// forgot password
if (isset($_POST['forgot_request'])) {
    $forgot_email = trim($_POST['forgot_email']);
    $errors = [];

    if (empty($forgot_email)) {
        $errors["forgot_email"] = "Please enter an email to receive OTP.";
    }

    if (empty($errors)) {
        $check_sql = "SELECT email FROM account_table WHERE email = '$forgot_email'";
        $result = $conn->query($check_sql);
        
        // otp
        if ($result->num_rows > 0) {
            $otp = rand(100000, 999999);
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
            
            $update_sql = "UPDATE account_table SET otp_code = '$otp', otp_expiry = '$otp_expiry' WHERE email = '$forgot_email'";
            if ($conn->query($update_sql)) {
                $_SESSION['forgot_email'] = $forgot_email;
                
                $subject = "Root Flower Password Reset";
                $msg = "Your OTP code is: " . $otp . "\nThis code expires in 5 minutes.";
                if (sendEmail($forgot_email, $subject, $msg)) {
                    $_SESSION['alert'] = "OTP sent. Please check your email.";
                    $_SESSION['alertType'] = "success";
                    $_SESSION["showOtp"] = true;
                    unset($_SESSION['showReset'], $_SESSION['showForgot']);
                } else {
                    $_SESSION['alert'] = "Failed to send OTP. Please try again.";
                    $_SESSION['alertType'] = "danger";
                    $_SESSION['showForgot'] = true;
                }
            } else {
                $_SESSION['alert'] = "Error generating OTP. Please try again.";
                $_SESSION['alertType'] = "danger";
                $_SESSION['showForgot'] = true;
            }
        } else {
            $_SESSION['alert'] = "Email not found. Please check your email address.";
            $_SESSION['alertType'] = "danger";
            $_SESSION['showForgot'] = true;
            $_SESSION['old']['forgot_email'] = $forgot_email;
        }
        $result->free();
    } else {
        $_SESSION['errors'] = $errors;
        $_SESSION['old']['forgot_email'] = $forgot_email;
        $_SESSION['showForgot'] = true;
    }
    header("Location: login.php");
    exit;
}

// verify otp
if (isset($_POST['verify_otp'])) {
    $entered_otp = trim($_POST['otp_code']);
    $errors = [];

    if (empty($entered_otp)) {
        $errors["otp_code"] = "Please enter the OTP sent to your email.";
    }

    if (empty($errors) && isset($_SESSION['forgot_email'])) {
        $forgot_email = $_SESSION['forgot_email'];
        
        $check_sql = "SELECT otp_code, otp_expiry FROM account_table WHERE email = '$forgot_email'";
        $result = $conn->query($check_sql);
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stored_otp = $row['otp_code'];
            $otp_expiry = $row['otp_expiry'];
            
            if ($stored_otp == $entered_otp && strtotime($otp_expiry) > time()) {
                $_SESSION['otp_verified'] = true;
                $_SESSION['alert'] = "OTP verified. You may reset your password.";
                $_SESSION['alertType'] = "success";
                $_SESSION['showReset'] = true;
                unset($_SESSION['showOtp'], $_SESSION['showForgot']);
            } else {
                if (isset($_POST['resend-otp'])) {
                    $new_otp = rand(100000, 999999);
                    $new_otp_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                    
                    $update_sql = "UPDATE account_table SET otp_code = '$new_otp', otp_expiry = '$new_otp_expiry' WHERE email = '$forgot_email'";
                    if ($conn->query($update_sql)) {
                        $subject = "Root Flower Password Reset";
                        $msg = "Your new OTP code is: " . $new_otp . "\nThis code expires in 5 minutes.";
                        sendEmail($forgot_email, $subject, $msg);
                        $_SESSION['alert'] = "New OTP sent. Please check your email.";
                        $_SESSION['alertType'] = "success";
                    }
                } else {
                    $errors["otp_code"] = "Invalid or expired OTP. Receive a new OTP";
                    $_SESSION['alert'] = "Invalid or expired OTP.";
                    $_SESSION['alertType'] = "danger";
                }
                $_SESSION["showOtp"] = true;
            }
        } else {
            $_SESSION['alert'] = "OTP not found. Please request to resend OTP";
            $_SESSION['alertType'] = "danger";
            $_SESSION["showForgot"] = true;
        }
        $result->free();
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['showOtp'] = true;
    }

    header("Location: login.php");
    exit;
}

// reset password
if (isset($_POST['reset_password'])) {
    if (isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] && isset($_SESSION['forgot_email'])){
        $newPass = trim($_POST['new_password']);
        $confirmPass = trim($_POST['confirm_password']);
        $errors = [];

        if (empty($newPass)) {
            $errors["new_password"] = "Password is required";
        } elseif (strlen($newPass) < 8 || !preg_match("/[0-9]/", $newPass) || !preg_match("/[\W]/", $newPass)) {
            $errors["new_password"] = "Password must be at least 8 chars, including 1 number and 1 symbol";
        }

        if (empty($confirmPass)) {
            $errors["confirm_password"] = "Please confirm your password";
        } elseif ($newPass !== $confirmPass) {
            $errors["confirm_password"] = "Passwords do not match";
        }

        if (empty($errors)) {
            $hashed_password = password_hash($newPass, PASSWORD_DEFAULT);
            $forgot_email = $_SESSION['forgot_email'];
            
            // clear OTP from account_table when password is reset
            $update_sql = "UPDATE account_table SET password = '$hashed_password', otp_code = NULL, otp_expiry = NULL WHERE email = '$forgot_email'";
            
            if ($conn->query($update_sql)) {
                $_SESSION['alert'] = "Password reset successfully.";
                $_SESSION['alertType'] = "success";
                unset($_SESSION['otp_verified'], $_SESSION['forgot_email']);
            } else {
                $_SESSION['alert'] = "Error resetting password. Please try again.";
                $_SESSION['alertType'] = "danger";
                $_SESSION['showReset'] = true;
            }
        } else {
            $_SESSION['errors'] = $errors;
            $_SESSION['showReset'] = true;
        }
    }
    header("Location: login.php");
    exit;
}
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="author" content="Aryn Mei Wei JEE"/>
    <meta name="description" content="Login Page"/>
    <meta name="keywords" content="Root Flower, floral class, Kuching-based"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style/styles.css"/>

    <!--bootstrap framework CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">

    <title>Root Flower Login Page</title>
</head>
<body 
    data-show-forgot="<?= $showForgot ? 'true' : 'false' ?>"
    data-show-otp="<?= $showOtp ? 'true' : 'false' ?>"
    data-show-reset="<?= $showReset ? 'true' : 'false' ?>"
>

<!-- navigation bar -->
<?php include "include/header.php" ?>

<?php if (isset($_SESSION['alert'])): ?>
    <div class="alert alert-<?= htmlspecialchars($_SESSION['alertType']) ?> fade show alert-dismissable translate-middle start-50 position-fixed mt-5 w-75 text-center" role="alert">
        <?= htmlspecialchars($_SESSION['alert']) ?>
    </div>
<?php unset ($_SESSION['alert'], $_SESSION['alertType']); ?><?php endif; ?>

<article class="justify-content-center align-items-center d-flex my-5">
    <div class="login-card overflow-hidden d-flex flex-wrap my-5 rounded-4">
        <div class="login-card-left d-flex flex-column justify-content-center p-5">
            <h2 class="fw-bold text-center">Welcome Back!</h2>
            <p class="text-center text-center pb-3">We're happy to see you again. Log in to continue shopping flower bouquets.</p>

            <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="login-form">
                <div class="mb-3">
                    <input type="email" class="form-control" name="email" placeholder="Email Address" value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <input type="password" class="form-control" name="password" placeholder="Password">
                </div>
                <a href="#" data-bs-toggle="modal" data-bs-target="#forgotModal" class="forgot-link">Forgot Password?</a>

                <button type="submit" class="btn btn-submit mt-2 mb-2 fw-medium text-uppercase" name="login">Login</button>

                <div class="mb-3 text-center mt-4">
                    <p>Don't have an account? Register <a href="registration.php" class="login-link">here</a></p>
                </div>
            </form>
        </div>

        <div class="login-card-right text-center justify-content-center d-flex flex-column p-5"></div>
    </div>

    <!-- forgot password -->
    <div class="modal fade" id="forgotModal" tabindex="-1" aria-labelledby="forgotModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content themed-modal rounded-4 p-2">
                <div class="modal-header border-0 position-relative">
                    <button type="button" class="btn position-absolute" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                    <h5 class="modal-title fw-bold mx-auto" id="forgotModalLabel">Forgot Password</h5>
                </div>
                <div class="modal-body">
                    <p>Enter your registered email address. We'll send you an OTP to reset your password.</p>
                    <input type="email" name="forgot_email" class="form-control mb-1" placeholder="Email Address" value="<?= htmlspecialchars($old['forgot_email'] ?? '') ?>">
                    <?php if (isset($errors['forgot_email'])): ?>
                        <div class="text-danger small mb-3"><?= $errors['forgot_email'] ?></div>
                    <?php endif; ?>
                    <button type="submit" name="forgot_request" class="btn btn-modal w-100 fw-medium border-0 pt-4">Send OTP</button>
                </div>
            </form>
        </div>
    </div>

    <!-- verify otp -->
    <div class="modal fade" id="otpModal" tabindex="-1" aria-labelledby="otpModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content themed-modal rounded-4 p-2">
                <form method="POST">
                    <div class="modal-header border-0 position-relative">
                        <h5 class="modal-title fw-bold mx-auto" id="otpModalLabel">Verify OTP</h5>
                    </div>
                    <div class="modal-body">
                        <p>Please enter the OTP sent to your email.</p>
                        <input type="text" name="otp_code" class="form-control mb-1" placeholder="Enter OTP" maxlength="6">
                        
                        <!-- resend otp -->
                        <?php if (isset($errors['otp_code'])): ?>
                            <div class="text-danger small mb-3">
                                Invalid OTP. Receive a new OTP
                                <form method="POST" class="d-inline">
                                    <button type="submit" name="resend-otp" class="btn btn-link p-0 text-danger text-decoration-underline border-0">
                                        here
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <button type="submit" name="verify_otp" class="btn btn-modal w-100 fw-medium border-0 pt-4">Verify OTP</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- reset password -->
    <div class="modal fade" id="resetModal" tabindex="-1" aria-labelledby="resetModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content themed-modal rounded-4 p-2">
                <div class="modal-header border-0 position-relative">
                    <h5 class="modal-title fw-bold mx-auto" id="resetModalLabel">Reset Password</h5>
                </div>
                <div class="modal-body">
                    <p>Enter your new password below.</p>
                    <input type="password" name="new_password" class="form-control mb-4" placeholder="New Password">
                    <?php if (isset($errors['new_password'])): ?>
                        <div class="text-danger small mb-3"><?= $errors['new_password'] ?></div>
                    <?php endif; ?>
                    <input type="password" name="confirm_password" class="form-control mb-1" placeholder="Confirm Password">
                    <?php if (isset($errors['confirm_password'])): ?>
                        <div class="text-danger small mb-3"><?= $errors['confirm_password'] ?></div>
                    <?php endif; ?>
                    <button type="submit" name="reset_password" class="btn btn-modal w-100 fw-medium border-0 pt-4">Reset Password</button>
                </div>
            </form>
        </div>
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