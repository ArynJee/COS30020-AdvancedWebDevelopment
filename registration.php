<?php
session_start();

$errors  = $_SESSION['errors'] ?? '';
$old = $_SESSION['old'] ?? '';

unset($_SESSION['errors'], $_SESSION['old']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8"/>
    <meta name="author" content="Aryn Mei Wei JEE"/>
    <meta name="description" content="Registration Page"/>
    <meta name="keywords" content="Root Flower, floral class, Kuching-based"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/styles.css"/>

    <!--bootstrap framework CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">

    <title>Root Flower Registration Page</title>
</head>

<body>
<?php include "include/header.php" ?>

<?php if (isset($_SESSION['alert'])): ?>
    <div class="alert alert-<?= htmlspecialchars($_SESSION['alertType']) ?> fade show alert-dismissable translate-middle start-50 position-fixed mt-5 w-75 text-center" role="alert">
        <?= htmlspecialchars($_SESSION['alert']) ?>
    </div>

<?php unset ($_SESSION['alert'], $_SESSION['alertType']); ?><?php endif; ?>

<article class="justify-content-center align-items-center d-flex my-5">
    <div class="registration-card overflow-hidden d-flex flex-wrap my-5">
        <!-- left side -->
        <div class="registration-card-left text-center justify-content-center d-flex flex-column">
            <h2 class="position-relative fw-bold mb-3">Create Your Account</h2>
            <p class="position-relative">Join us now to submit your own beautiful curation.</p>
        </div>

        <!-- right side -->
        <div class="registration-card-right">
            <h3 class="fw-bold mb-4">Welcome,</h3>
            <form action="process_register.php" method="POST">
                <div class="row mb-3">
                    <!-- first and last name -->
                    <div class="col">
                        <input type="text" 
                            class="form-control <?php if (!empty($errors['first_name'])) { echo 'is-invalid'; } ?>" 
                            name="first_name" 
                            placeholder="First Name" 
                            value="<?php echo htmlspecialchars(isset($old['first_name']) ? $old['first_name'] : ''); ?>">
                        <?php if (!empty($errors['first_name'])) { ?>
                            <div class="error"><?php echo htmlspecialchars($errors['first_name']); ?></div>
                        <?php } ?>
                    </div>

                    <div class="col">
                        <input type="text" 
                            class="form-control <?php if (!empty($errors['last_name'])) { echo 'is-invalid'; } ?>" 
                            name="last_name" 
                            placeholder="Last Name" 
                            value="<?php echo htmlspecialchars(isset($old['last_name']) ? $old['last_name'] : ''); ?>">
                        <?php if (!empty($errors['last_name'])) { ?>
                            <div class="error"><?php echo htmlspecialchars($errors['last_name']); ?></div>
                        <?php } ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" 
                        class="form-control <?php if (!empty($errors['dob'])) { echo 'is-invalid'; } ?>" 
                        name="dob" 
                        value="<?php echo htmlspecialchars(isset($old['dob']) ? $old['dob'] : ''); ?>">
                    <?php if (!empty($errors['dob'])) { ?>
                        <div class="error"><?php echo htmlspecialchars($errors['dob']); ?></div>
                    <?php } ?>
                </div>

                <div class="mb-3">
                    <label class="form-label">Gender</label>
                    <select class="form-control <?php if (!empty($errors['gender'])) { echo 'is-invalid'; } ?>" name="gender" required>
                        <?php 
                            $oldGender = isset($old['gender']) ? $old['gender'] : 'Female';
                        ?>
                        <option value="Female" <?php if ($oldGender === 'Female') { echo 'selected'; } ?>>Female</option>
                        <option value="Male" <?php if ($oldGender === 'Male') { echo 'selected'; } ?>>Male</option>
                    </select>
                    <?php if (!empty($errors['gender'])) { ?>
                        <div class="error"><?php echo htmlspecialchars($errors['gender']); ?></div>
                    <?php } ?>
                </div>

                <div class="mb-3">
                    <input type="text" 
                        class="form-control <?php if (!empty($errors['email'])) { echo 'is-invalid'; } ?>" 
                        name="email" 
                        placeholder="Email" 
                        value="<?php echo htmlspecialchars(isset($old['email']) ? $old['email'] : ''); ?>">
                    <?php if (!empty($errors['email'])) { ?>
                        <div class="error"><?php echo htmlspecialchars($errors['email']); ?></div>
                    <?php } ?>
                </div>

                <div class="mb-3">
                    <input type="text" 
                        class="form-control <?php if (!empty($errors['hometown'])) { echo 'is-invalid'; } ?>" 
                        name="hometown" 
                        placeholder="Hometown" 
                        value="<?php echo htmlspecialchars(isset($old['hometown']) ? $old['hometown'] : ''); ?>">
                    <?php if (!empty($errors['hometown'])) { ?>
                        <div class="error"><?php echo htmlspecialchars($errors['hometown']); ?></div>
                    <?php } ?>
                </div>

                <div class="row mb-3">
                    <div class="col">
                        <input type="text" 
                            class="form-control <?php if (!empty($errors['password'])) { echo 'is-invalid'; } ?>" 
                            name="password" 
                            placeholder="Password">
                        <?php if (!empty($errors['password'])) { ?>
                            <div class="error"><?php echo htmlspecialchars($errors['password']); ?></div>
                        <?php } ?>
                    </div>

                    <div class="col">
                        <input type="text" 
                            class="form-control <?php if (!empty($errors['confirm_password'])) { echo 'is-invalid'; } ?>" 
                            name="confirm_password" 
                            placeholder="Confirm Password">
                        <?php if (!empty($errors['confirm_password'])) { ?>
                            <div class="error"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
                        <?php } ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-submit mt-2 mb-2 fw-medium text-uppercase">Submit</button>
                <a href="registration.php" class="btn btn-reset btn-outline-secondary text-uppercase">Reset</a>

                <!-- duplicate email -->
                <?php if (!empty($errors['duplicate'])): ?>
                    <div class="error text-center mt-3"><?= htmlspecialchars($errors['duplicate']) ?></div>
                <?php endif; ?>

                <!-- success message -->
                <?php if (!empty($success)): ?>
                    <div class="success text-center mt-3"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <div class="mb-3 text-center mt-4">
                    <p>Already have an account? Log in <a href="login.php" class="login-link">here</a></p>
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
<script src="js/main.js"></script>
</body>
</html>
