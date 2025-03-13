<?php
session_start();
require_once("../db/config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION["user_id"];
    $appliance_id = $_POST["appliance_id"];
    $hours_used = $_POST["hours_used"];

    // Get power rating of selected appliance
    $query = "SELECT power_rating FROM appliances WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $appliance_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$row) {
        die("Invalid appliance selected.");
    }

    $power_rating = $row["power_rating"];  // Power in kW
    $energy_used = $power_rating * $hours_used;  // Energy = Power × Time (kWh)

    // Store in database
    $query = "INSERT INTO energy_usage (user_id, appliance_id, energy_used) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iid", $user_id, $appliance_id, $energy_used);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($result) {
        header("Location: ../views/dashboard.php");
        exit();
    } else {
        echo "Error saving data.";
    }
}
?>
