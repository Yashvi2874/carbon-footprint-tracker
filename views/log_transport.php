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

// Fetch transport modes
$transport_query = mysqli_query($conn, "SELECT id, name, carbon_emission FROM transport_modes ORDER BY name ASC");
$transport_modes = [];
while ($row = mysqli_fetch_assoc($transport_query)) {
    $transport_modes[] = $row;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $transport_id = $_POST['transport_id'];
    $distance_km = $_POST['distance_km'];
    $log_date = $_POST['log_date'] ?? date('Y-m-d');
    
    // Check if there's already an entry for this user, transport mode, and date
    $check_query = "SELECT id FROM user_transport WHERE user_id = ? AND transport_id = ? AND date = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    
    // Add error handling for mysqli_prepare
    if ($check_stmt === false) {
        $_SESSION['error'] = "Error preparing statement: " . mysqli_error($conn);
        header("Location: " . BASE_URL . "/views/log_transport.php");
        exit();
    }
    
    mysqli_stmt_bind_param($check_stmt, "iis", $user_id, $transport_id, $log_date);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        // Update existing entry
        $update_query = "UPDATE user_transport SET distance_km = ? WHERE user_id = ? AND transport_id = ? AND date = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        
        // Add error handling for mysqli_prepare
        if ($update_stmt === false) {
            $_SESSION['error'] = "Error preparing update statement: " . mysqli_error($conn);
            header("Location: " . BASE_URL . "/views/log_transport.php");
            exit();
        }
        
        mysqli_stmt_bind_param($update_stmt, "diis", $distance_km, $user_id, $transport_id, $log_date);
        $result = mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
    } else {
        // Insert new entry
        $insert_query = "INSERT INTO user_transport (user_id, transport_id, distance_km, date) VALUES (?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        
        // Add error handling for mysqli_prepare
        if ($insert_stmt === false) {
            $_SESSION['error'] = "Error preparing insert statement: " . mysqli_error($conn);
            header("Location: " . BASE_URL . "/views/log_transport.php");
            exit();
        }
        
        mysqli_stmt_bind_param($insert_stmt, "iisd", $user_id, $transport_id, $distance_km, $log_date);
        $result = mysqli_stmt_execute($insert_stmt);
        mysqli_stmt_close($insert_stmt);
    }
    mysqli_stmt_close($check_stmt);
    
    if ($result) {
        $_SESSION['success'] = "Transport usage logged successfully for " . date('F j, Y', strtotime($log_date)) . "!";
        header("Location: " . BASE_URL . "/views/dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Error logging transport usage: " . mysqli_error($conn);
        header("Location: " . BASE_URL . "/views/log_transport.php");
        exit();
    }
}
?>

<main>
    <h2>Log Your Transport Usage</h2>
    
    <div class="card">
        <form method="POST" id="transport-form">
            <div>
                <label for="log_date">Date:</label>
                <input type="date" name="log_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div>
                <label for="transport_id">Select Transport Mode:</label>
                <select name="transport_id" required>
                    <?php foreach ($transport_modes as $mode): ?>
                        <option value="<?php echo $mode['id']; ?>">
                            <?php echo htmlspecialchars($mode['name']); ?> (<?php echo round($mode['carbon_emission'], 3); ?> kg CO₂/km)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="distance_km">Distance (km):</label>
                <input type="number" name="distance_km" step="0.1" min="0" required>
            </div>
            
            <button type="submit">Log Transport Usage</button>
        </form>
    </div>
    
    <div class="action-buttons">
        <a href="<?php echo BASE_URL; ?>/views/dashboard.php" class="btn">Back to Dashboard</a>
    </div>
</main>

<?php require_once(__DIR__ . "/../includes/footer.php"); ?>