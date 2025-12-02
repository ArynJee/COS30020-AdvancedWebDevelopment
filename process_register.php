<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "RootFlower";

    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // sanitize inputs
    $first = trim($_POST['first_name']);
    $last = trim($_POST['last_name']);
    $dob = trim($_POST['dob']);
    $gender = $_POST['gender'] ?? "";
    $email = trim($_POST['email']);
    $hometown = trim($_POST['hometown']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $profile_image = NULL;

    $errors = [
        "first_name" => "",
        "last_name" => "",
        "dob" => "",
        "gender" => "",
        "email" => "",
        "hometown" => "",
        "password" => "",
        "confirm_password" => ""
    ];

    // validation
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
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors["email"] = "Invalid email format";
    }

    if (empty($hometown)) {
        $errors["hometown"] = "Hometown is required";
    }

    if (empty($password)) {
        $errors["password"] = "Password is required";
    } elseif (strlen($password) < 8 || !preg_match("/[0-9]/", $password) || !preg_match("/[\W]/", $password)) {
        $errors["password"] = "Password must be at least 8 chars, including 1 number and 1 symbol";
    }

    if (empty($confirm_password)) {
        $errors["confirm_password"] = "Please confirm your password";
    } elseif ($password !== $confirm_password) {
        $errors["confirm_password"] = "Passwords do not match";
    }

    // check for duplicate email
    if (!array_filter($errors)) {
        $check_sql = "SELECT email FROM account_table WHERE email = '$email'";
        $result = $conn->query($check_sql);

         if ($result->num_rows > 0) {
            $_SESSION['alert'] = "User already exists. Please proceed to login.";
            $_SESSION['alertType'] = "danger";
            header("Location: registration.php");
            exit(); 
        }
        $result->free();
    }

    // insert if no error
    if (!array_filter($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $user_type = "user";

        $user_sql = "INSERT INTO user_table (email, first_name, last_name, dob, gender, hometown, profile_image) VALUES ('$email', '$first', '$last', '$dob', '$gender', '$hometown', '$profile_image')";

        if($conn->query($user_sql)){
            $account_sql = "INSERT INTO account_table (email, password, type) VALUES ('$email', '$hashed_password', '$user_type')";

            if($conn->query($account_sql)){
                    $_SESSION['alert'] = "Registration successful!";
                    $_SESSION['alertType'] = "success";
                    header("Location: login.php");
                    exit();
                } else {
                    $_SESSION['alert'] = "Error creating account: " . $conn->error;
                    $_SESSION['alertType'] = "danger";
                    header("Location: registration.php");
                    exit();
                }
            } else {
                $_SESSION['alert'] = "Error creating user: " . $conn->error;
                $_SESSION['alertType'] = "danger";
                header("Location: registration.php");
                exit();
            }
        }
        $conn->close();

    if(array_filter($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = $_POST;
        header("Location: registration.php");
        exit();
    }
}
?>