<?php
session_start();
require_once("../db/config.php");

$user_id = $_SESSION["user_id"];

// Get total energy consumption
$query = "SELECT SUM(energy_used) AS total_energy FROM energy_usage WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$totalEnergy = $row["total_energy"] ?: 0;
mysqli_stmt_close($stmt);

// Get carbon footprint (assuming 0.92 kg CO₂ per kWh)
$carbonFootprint = $totalEnergy * 0.92;

// Get recent entries
$query = "SELECT e.date, a.name AS appliance, e.energy_used 
          FROM energy_usage e 
          JOIN appliances a ON e.appliance_id = a.id 
          WHERE e.user_id = ? 
          ORDER BY e.date DESC 
          LIMIT 5";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$entries = [];
while ($row = mysqli_fetch_assoc($result)) {
    $entries[] = $row;
}
mysqli_stmt_close($stmt);

echo json_encode([
    "totalEnergy" => $totalEnergy,
    "carbonFootprint" => round($carbonFootprint, 2),
    "entries" => $entries
]);
?>
