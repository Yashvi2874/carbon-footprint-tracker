<?php
session_start();
include "../db/config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = password_hash($_POST["password"], PASSWORD_BCRYPT);
    $energy_source_id = $_POST["energy_source"];

    // Check if email already exists
    $check_query = "SELECT id FROM users WHERE email = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "s", $email);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        echo "Email already exists!";
        mysqli_stmt_close($check_stmt);
        exit();
    }
    mysqli_stmt_close($check_stmt);

    // Insert user into database
    $insert_query = "INSERT INTO users (name, email, password, energy_source_id) VALUES (?, ?, ?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($insert_stmt, "sssi", $name, $email, $password, $energy_source_id);
    
    if (mysqli_stmt_execute($insert_stmt)) {
        $user_id = mysqli_insert_id($conn);
        $_SESSION["user_id"] = $user_id;
        $_SESSION["user_name"] = $name;
        mysqli_stmt_close($insert_stmt);
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Registration failed!";
        mysqli_stmt_close($insert_stmt);
    }
}
?>
