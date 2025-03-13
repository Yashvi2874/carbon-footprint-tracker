<?php
require_once(__DIR__ . "/../includes/header.php");
require_once(__DIR__ . "/../includes/navbar.php");
require_once(__DIR__ . "/../db/config.php");

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/views/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch appliances from DB
$query = "SELECT id, name, power_rating FROM appliances ORDER BY name ASC";
$result = mysqli_query($conn, $query);
$appliances = [];
while ($row = mysqli_fetch_assoc($result)) {
    $appliances[] = $row;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $appliance_id = $_POST['appliance_id'];
    $usage_hours = $_POST['usage_hours'];
    $log_date = $_POST['log_date'];
    
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
    $kwh_consumed = ($power_rating * $usage_hours) / 1000;
    
    // Save or update the user appliance record
    $user_appliance_query = "INSERT INTO user_appliances (user_id, appliance_id, usage_hours) 
                             VALUES (?, ?, ?) 
                             ON DUPLICATE KEY UPDATE usage_hours = ?";
                             
    $user_appliance_stmt = mysqli_prepare($conn, $user_appliance_query);
    mysqli_stmt_bind_param($user_appliance_stmt, "iidd", $user_id, $appliance_id, $usage_hours, $usage_hours);
    $user_appliance_result = mysqli_stmt_execute($user_appliance_stmt);
    mysqli_stmt_close($user_appliance_stmt);
    
    if (!$user_appliance_result) {
        $_SESSION['error'] = "Error updating appliance usage: " . mysqli_error($conn);
        header("Location: " . BASE_URL . "/views/log_energy.php");
        exit();
    }
    
    // Single day logging
    $insert_query = "INSERT INTO energy_usage (user_id, date, kwh_consumed) 
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE kwh_consumed = kwh_consumed + VALUES(kwh_consumed)";
    
    $insert_stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($insert_stmt, "isd", $user_id, $log_date, $kwh_consumed);
    $insert_result = mysqli_stmt_execute($insert_stmt);
    mysqli_stmt_close($insert_stmt);
    
    if ($insert_result) {
        $_SESSION['success'] = "Energy usage logged successfully for " . date('F j, Y', strtotime($log_date)) . "!";
    } else {
        $_SESSION['error'] = "Error logging energy usage.";
    }
    
    header("Location: " . BASE_URL . "/views/dashboard.php");
    exit();
}
?>

<main>
    <h2>Log Your Energy Usage</h2>
    
    <div class="card">
        <form method="POST" id="energy-form">
            <div>
                <label for="log_date">Date:</label>
                <input type="date" name="log_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div>
                <label for="appliance_id">Select Appliance:</label>
                <select name="appliance_id" required>
                    <?php foreach ($appliances as $appliance): ?>
                        <option value="<?php echo $appliance['id']; ?>">
                            <?php echo htmlspecialchars($appliance['name']); ?> (<?php echo $appliance['power_rating']; ?> W)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="usage_hours">Hours Used:</label>
                <input type="number" name="usage_hours" step="0.1" min="0" required>
            </div>
            
            <button type="submit">Log Energy Usage</button>
        </form>
    </div>
    
    <div class="action-buttons">
        <a href="<?php echo BASE_URL; ?>/views/dashboard.php" class="btn">Back to Dashboard</a>
    </div>
</main>

<?php require_once(__DIR__ . "/../includes/footer.php"); ?>