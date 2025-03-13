<?php
include("../db/config.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$appliance_id = filter_input(INPUT_POST, 'appliance_id', FILTER_VALIDATE_INT);
$hours_used = filter_input(INPUT_POST, 'hours_used', FILTER_VALIDATE_FLOAT);

// Validate input
if (!$appliance_id || !$hours_used || $hours_used < 0) {
    $_SESSION['error'] = "Invalid input data.";
    header("Location: ../views/log_energy.php");
    exit();
}

// Get appliance power rating
$power_query = "SELECT power_rating FROM appliances WHERE id = ?";
$power_stmt = mysqli_prepare($conn, $power_query);
mysqli_stmt_bind_param($power_stmt, "i", $appliance_id);
mysqli_stmt_execute($power_stmt);
$power_result = mysqli_stmt_get_result($power_stmt);
$appliance = mysqli_fetch_assoc($power_result);
$power_rating = $appliance['power_rating'];
mysqli_stmt_close($power_stmt);

// Calculate energy consumption in kWh
$kwh_consumed = ($power_rating * $hours_used) / 1000;

// Insert energy usage - MySQL doesn't have ON CONFLICT, so we need to use INSERT ... ON DUPLICATE KEY UPDATE
$query = "INSERT INTO energy_usage (user_id, date, kwh_consumed) 
          VALUES (?, CURRENT_DATE, ?)
          ON DUPLICATE KEY UPDATE kwh_consumed = kwh_consumed + VALUES(kwh_consumed)";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "id", $user_id, $kwh_consumed);
$result = mysqli_stmt_execute($stmt);

if (!$result) {
    $_SESSION['error'] = "Error saving energy data.";
    header("Location: ../views/log_energy.php");
    exit();
}

mysqli_stmt_close($stmt);
$_SESSION['success'] = "Energy usage added successfully!";
header("Location: ../views/dashboard.php");
?> 