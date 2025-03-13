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

// Get user details
$user_stmt = mysqli_prepare($conn, "SELECT name, email, household_size FROM users WHERE id = ?");
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($user_stmt);
$household_size = max(1, intval($user['household_size'] ?? 1));

// Set default date range (last 30 days)
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-30 days'));

// Handle date range form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_date']) && isset($_POST['end_date'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
}

// Fetch energy usage data
$energy_stmt = mysqli_prepare($conn, 
    "SELECT date, SUM(kwh_consumed) as total_energy 
     FROM energy_usage 
     WHERE user_id = ? AND date BETWEEN ? AND ? 
     GROUP BY date 
     ORDER BY date DESC"
);
mysqli_stmt_bind_param($energy_stmt, "iss", $user_id, $start_date, $end_date);
mysqli_stmt_execute($energy_stmt);
$energy_result = mysqli_stmt_get_result($energy_stmt);
$energy_data = [];
while ($row = mysqli_fetch_assoc($energy_result)) {
    $energy_data[] = $row;
}
mysqli_stmt_close($energy_stmt);

$total_energy = 0;
foreach ($energy_data as $day) {
    $total_energy += $day['total_energy'];
}

// Fetch carbon footprint from energy usage
$carbon_energy_stmt = mysqli_prepare($conn, 
    "SELECT SUM(kwh_consumed * 0.92) AS total_carbon 
     FROM energy_usage 
     WHERE user_id = ? AND date BETWEEN ? AND ?"
);
mysqli_stmt_bind_param($carbon_energy_stmt, "iss", $user_id, $start_date, $end_date);
mysqli_stmt_execute($carbon_energy_stmt);
$carbon_energy_result = mysqli_stmt_get_result($carbon_energy_stmt);
$carbon_energy_row = mysqli_fetch_assoc($carbon_energy_result);
$carbon_energy = $carbon_energy_row['total_carbon'] ?? 0;
mysqli_stmt_close($carbon_energy_stmt);

// Check the structure of the energy_usage table
$table_check = mysqli_query($conn, "SHOW COLUMNS FROM energy_usage");
$columns = [];
while ($row = mysqli_fetch_assoc($table_check)) {
    $columns[] = $row['Field'];
}

// Initialize energy breakdown
$energy_breakdown = [];

// Determine if we have an appliance_id or appliance_name column
if (in_array('appliance_id', $columns)) {
    // If we have appliance_id, join with appliances table
    $energy_breakdown_stmt = mysqli_prepare($conn, 
        "SELECT a.name, SUM(e.kwh_consumed) as total_energy
         FROM energy_usage e
         JOIN appliances a ON e.appliance_id = a.id
         WHERE e.user_id = ? AND e.date BETWEEN ? AND ?
         GROUP BY a.name
         ORDER BY total_energy DESC"
    );
    mysqli_stmt_bind_param($energy_breakdown_stmt, "iss", $user_id, $start_date, $end_date);
    mysqli_stmt_execute($energy_breakdown_stmt);
    $energy_breakdown_result = mysqli_stmt_get_result($energy_breakdown_stmt);
    
    if ($energy_breakdown_result) {
        while ($row = mysqli_fetch_assoc($energy_breakdown_result)) {
            $energy_breakdown[] = $row;
        }
    }
    mysqli_stmt_close($energy_breakdown_stmt);
} else {
    // If we don't have specific appliance columns, just group by date
    $energy_breakdown_stmt = mysqli_prepare($conn, 
        "SELECT date, SUM(kwh_consumed) as total_energy
         FROM energy_usage
         WHERE user_id = ? AND date BETWEEN ? AND ?
         GROUP BY date
         ORDER BY date DESC"
    );
    mysqli_stmt_bind_param($energy_breakdown_stmt, "iss", $user_id, $start_date, $end_date);
    mysqli_stmt_execute($energy_breakdown_stmt);
    $energy_breakdown_result = mysqli_stmt_get_result($energy_breakdown_stmt);
    
    if ($energy_breakdown_result) {
        while ($row = mysqli_fetch_assoc($energy_breakdown_result)) {
            $energy_breakdown[] = $row;
        }
        // Transform the data to show dates as "appliance names"
        foreach ($energy_breakdown as &$item) {
            $item['name'] = 'Energy on ' . date('M j, Y', strtotime($item['date']));
        }
    }
    mysqli_stmt_close($energy_breakdown_stmt);
}

$carbon_transport = 0;
$transport_breakdown = [];

// Fetch carbon footprint from transport usage
$carbon_transport_stmt = mysqli_prepare($conn, 
    "SELECT SUM(tm.carbon_emission * t.distance_km) AS transport_carbon 
     FROM user_transport t 
     JOIN transport_modes tm ON t.transport_id = tm.id 
     WHERE t.user_id = ? AND t.date BETWEEN ? AND ?"
);
mysqli_stmt_bind_param($carbon_transport_stmt, "iss", $user_id, $start_date, $end_date);
mysqli_stmt_execute($carbon_transport_stmt);
$carbon_transport_result = mysqli_stmt_get_result($carbon_transport_stmt);

if ($carbon_transport_result) {
    $result = mysqli_fetch_assoc($carbon_transport_result);
    $carbon_transport = $result['transport_carbon'] ?? 0;
}
mysqli_stmt_close($carbon_transport_stmt);

// Fetch transport breakdown
$transport_breakdown_stmt = mysqli_prepare($conn, 
    "SELECT tm.name, SUM(t.distance_km) as total_distance, 
     SUM(tm.carbon_emission * t.distance_km) as carbon_emission
     FROM user_transport t
     JOIN transport_modes tm ON t.transport_id = tm.id
     WHERE t.user_id = ? AND t.date BETWEEN ? AND ?
     GROUP BY tm.name
     ORDER BY carbon_emission DESC"
);
mysqli_stmt_bind_param($transport_breakdown_stmt, "iss", $user_id, $start_date, $end_date);
mysqli_stmt_execute($transport_breakdown_stmt);
$transport_breakdown_result = mysqli_stmt_get_result($transport_breakdown_stmt);

if ($transport_breakdown_result) {
    while ($row = mysqli_fetch_assoc($transport_breakdown_result)) {
        $transport_breakdown[] = $row;
    }
}
mysqli_stmt_close($transport_breakdown_stmt);

$total_carbon = $carbon_energy + $carbon_transport;

// Calculate per-person metrics
$energy_per_person = $household_size > 0 ? $total_energy / $household_size : 0;
$carbon_per_person = $household_size > 0 ? $total_carbon / $household_size : 0;

// Average values for comparison
$avg_household_energy = 900; // kWh per month
$avg_carbon_footprint = 1000; // kg CO2 per month
$avg_energy_per_person = $avg_household_energy / 2.5; // Assuming average household size of 2.5
$avg_carbon_per_person = $avg_carbon_footprint / 2.5;

// Calculate comparison percentages
$energy_percent = $avg_energy_per_person > 0 ? ($energy_per_person / $avg_energy_per_person) * 100 : 0;
$carbon_percent = $avg_carbon_per_person > 0 ? ($carbon_per_person / $avg_carbon_per_person) * 100 : 0;

// Format date range for display
$formatted_start = date('F j, Y', strtotime($start_date));
$formatted_end = date('F j, Y', strtotime($end_date));

// Generate recommendations
$recommendations = [];
if ($energy_percent > 100) {
    $recommendations[] = "Your energy usage is above average. Consider using energy-efficient appliances and turning off devices when not in use.";
}
if ($carbon_transport > $carbon_energy) {
    $recommendations[] = "Transportation is your largest carbon source. Consider carpooling, public transport, or cycling for shorter trips.";
}
$recommendations[] = "Replace incandescent bulbs with LED lighting to reduce energy consumption.";
$recommendations[] = "Unplug chargers and appliances when not in use to eliminate phantom energy usage.";
$recommendations[] = "Consider renewable energy options for your home if available in your area.";

// Check if user_appliances table exists
$table_exists_query = mysqli_query($conn, "SHOW TABLES LIKE 'user_appliances'");
$table_exists = mysqli_num_rows($table_exists_query) > 0;

$appliance_history = [];
if ($table_exists) {
    // Check the structure of user_appliances table
    $appliance_columns = [];
    $appliance_columns_query = mysqli_query($conn, "SHOW COLUMNS FROM user_appliances");
    while ($row = mysqli_fetch_assoc($appliance_columns_query)) {
        $appliance_columns[] = $row['Field'];
    }
    
    // Fetch appliance history based on available columns
    if (in_array('appliance_id', $appliance_columns) && in_array('usage_hours', $appliance_columns)) {
        $appliance_history_stmt = mysqli_prepare($conn,
            "SELECT a.name, ua.usage_hours, ua.created_at
             FROM user_appliances ua
             JOIN appliances a ON ua.appliance_id = a.id
             WHERE ua.user_id = ?
             ORDER BY ua.created_at DESC"
        );
        mysqli_stmt_bind_param($appliance_history_stmt, "i", $user_id);
        mysqli_stmt_execute($appliance_history_stmt);
        $appliance_history_result = mysqli_stmt_get_result($appliance_history_stmt);
        
        if ($appliance_history_result) {
            while ($row = mysqli_fetch_assoc($appliance_history_result)) {
                $appliance_history[] = $row;
            }
        }
        mysqli_stmt_close($appliance_history_stmt);
    } else if (in_array('name', $appliance_columns) && in_array('usage_hours', $appliance_columns)) {
        $appliance_history_stmt = mysqli_prepare($conn,
            "SELECT name, usage_hours, created_at
             FROM user_appliances
             WHERE user_id = ?
             ORDER BY created_at DESC"
        );
        mysqli_stmt_bind_param($appliance_history_stmt, "i", $user_id);
        mysqli_stmt_execute($appliance_history_stmt);
        $appliance_history_result = mysqli_stmt_get_result($appliance_history_stmt);
        
        if ($appliance_history_result) {
            while ($row = mysqli_fetch_assoc($appliance_history_result)) {
                $appliance_history[] = $row;
            }
        }
        mysqli_stmt_close($appliance_history_stmt);
    }
}

// Fetch transport history
$transport_history_stmt = mysqli_prepare($conn,
    "SELECT tm.name, t.distance_km, t.date
     FROM user_transport t
     JOIN transport_modes tm ON t.transport_id = tm.id
     WHERE t.user_id = ?
     ORDER BY t.date DESC"
);
mysqli_stmt_bind_param($transport_history_stmt, "i", $user_id);
mysqli_stmt_execute($transport_history_stmt);
$transport_history_result = mysqli_stmt_get_result($transport_history_stmt);
$transport_history = [];
while ($row = mysqli_fetch_assoc($transport_history_result)) {
    $transport_history[] = $row;
}
mysqli_stmt_close($transport_history_stmt);
?>

<main class="report-page">
    <div class="container">
        <h1>Carbon Footprint Report</h1>
        
        <div class="date-range-form">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Start Date:</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">End Date:</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Report</button>
                </div>
            </form>
        </div>
        
        <div class="report-summary">
            <div class="summary-header">
                <h2>Report Summary</h2>
                <p class="date-range"><?php echo $formatted_start; ?> to <?php echo $formatted_end; ?></p>
            </div>
            
            <div class="summary-cards">
                <div class="card gradient-card">
                    <div class="card-header">
                        <i class="fas fa-bolt"></i>
                        <h3>Energy Usage</h3>
                    </div>
                    <div class="card-body">
                        <div class="card-value"><?php echo number_format($total_energy, 1); ?> kWh</div>
                        <div class="card-comparison">
                            <?php if ($energy_percent > 100): ?>
                                <span class="text-danger"><?php echo number_format($energy_percent - 100, 1); ?>% above average</span>
                            <?php else: ?>
                                <span class="text-success"><?php echo number_format(100 - $energy_percent, 1); ?>% below average</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card gradient-card">
                    <div class="card-header">
                        <i class="fas fa-cloud"></i>
                        <h3>Carbon Footprint</h3>
                    </div>
                    <div class="card-body">
                        <div class="card-value"><?php echo number_format($total_carbon, 1); ?> kg CO₂</div>
                        <div class="card-comparison">
                            <?php if ($carbon_percent > 100): ?>
                                <span class="text-danger"><?php echo number_format($carbon_percent - 100, 1); ?>% above average</span>
                            <?php else: ?>
                                <span class="text-success"><?php echo number_format(100 - $carbon_percent, 1); ?>% below average</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card gradient-card">
                    <div class="card-header">
                        <i class="fas fa-users"></i>
                        <h3>Household Size</h3>
                    </div>
                    <div class="card-body">
                        <div class="card-value"><?php echo $household_size; ?> <?php echo $household_size == 1 ? 'person' : 'people'; ?></div>
                        <div class="card-comparison">
                            <span class="text-info"><?php echo number_format($energy_per_person, 1); ?> kWh per person</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="report-details">
            <div class="report-section card">
                <h2>Energy Breakdown</h2>
                <?php if (empty($energy_breakdown)): ?>
                    <p>No energy usage data available for the selected period.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date/Appliance</th>
                                <th>Energy Usage (kWh)</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($energy_breakdown as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name'] ?? $item['date']); ?></td>
                                    <td><?php echo number_format($item['total_energy'], 1); ?></td>
                                    <td><?php echo number_format(($item['total_energy'] / $total_energy) * 100, 1); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="report-section card">
                <h2>Transport Breakdown</h2>
                <?php if (empty($transport_breakdown)): ?>
                    <p>No transport data available for the selected period.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Transport Mode</th>
                                <th>Distance (km)</th>
                                <th>Carbon Emission (kg CO₂)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transport_breakdown as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo number_format($item['total_distance'], 1); ?></td>
                                    <td><?php echo number_format($item['carbon_emission'], 1); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="report-section card">
                <h2>Recommendations</h2>
                <ul class="recommendations-list">
                    <?php foreach ($recommendations as $recommendation): ?>
                        <li><?php echo $recommendation; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <?php if (!empty($appliance_history)): ?>
            <div class="report-section card">
                <h2>Appliance History</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Appliance</th>
                            <th>Usage Hours</th>
                            <th>Added On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appliance_history as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo $item['usage_hours']; ?> hours/day</td>
                                <td><?php echo date('M j, Y', strtotime($item['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <div class="report-section card">
                <h2>Transport History</h2>
                <?php if (empty($transport_history)): ?>
                    <p>No transport data available.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Transport Mode</th>
                                <th>Distance (km)</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transport_history as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo number_format($item['distance_km'], 1); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($item['date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="report-actions">
            <button id="downloadReport" class="btn btn-primary">Download Report</button>
            <a href="<?php echo BASE_URL; ?>/views/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
</main>

<style>
.report-page {
    padding: 2rem 0;
}

.date-range-form {
    margin-bottom: 2rem;
    background-color: var(--bg-card);
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.form-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
}

.form-group {
    flex: 1;
    min-width: 200px;
}

.report-summary {
    margin-bottom: 2rem;
}

.summary-header {
    margin-bottom: 1.5rem;
}

.date-range {
    color: var(--text-muted);
    font-size: 1.1rem;
}

.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.gradient-card {
    background: linear-gradient(135deg, var(--bg-card) 0%, var(--bg-card-dark) 100%);
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.gradient-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.card-header {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
}

.card-header i {
    font-size: 1.5rem;
    margin-right: 0.75rem;
    color: var(--primary-color);
}

.card-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.card-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.card-comparison {
    font-size: 1rem;
    color: var(--text-muted);
}

.text-success {
    color: var(--success-color);
}

.text-danger {
    color: var(--danger-color);
}

.text-info {
    color: var(--info-color);
}

.report-details {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
}

.report-section {
    background-color: var(--bg-card);
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.report-section h2 {
    margin-top: 0;
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--primary-color);
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 0.75rem;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th, .data-table td {
    padding: 0.75rem 1rem;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

.data-table th {
    font-weight: 600;
    color: var(--text-muted);
    background-color: rgba(255, 255, 255, 0.05);
}

.data-table tr:last-child td {
    border-bottom: none;
}

.recommendations-list {
    padding-left: 1.5rem;
}

.recommendations-list li {
    margin-bottom: 0.75rem;
    line-height: 1.5;
}

.report-actions {
    margin-top: 2rem;
    display: flex;
    gap: 1rem;
}

@media (max-width: 768px) {
    .summary-cards {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        flex-direction: column;
    }
    
    .report-actions {
        flex-direction: column;
    }
    
    .report-actions .btn {
        width: 100%;
    }
}
</style>

<script>
document.getElementById('downloadReport').addEventListener('click', function() {
    // Create report content
    let reportContent = "CARBON FOOTPRINT REPORT\n";
    reportContent += "======================\n\n";
    reportContent += "Period: <?php echo $formatted_start; ?> to <?php echo $formatted_end; ?>\n\n";
    
    reportContent += "SUMMARY\n";
    reportContent += "-------\n";
    reportContent += "Total Energy Usage: <?php echo number_format($total_energy, 1); ?> kWh\n";
    reportContent += "Total Carbon Footprint: <?php echo number_format($total_carbon, 1); ?> kg CO₂\n";
    reportContent += "Carbon Per Person: <?php echo number_format($carbon_per_person, 1); ?> kg CO₂\n\n";
    
    reportContent += "ENERGY BREAKDOWN\n";
    reportContent += "----------------\n";
    <?php foreach ($energy_breakdown as $item): ?>
    reportContent += "<?php echo isset($item['name']) ? $item['name'] : $item['date']; ?>: <?php echo number_format($item['total_energy'], 1); ?> kWh (<?php echo number_format(($item['total_energy'] / $total_energy) * 100, 1); ?>%)\n";
    <?php endforeach; ?>
    reportContent += "\n";
    
    reportContent += "TRANSPORT BREAKDOWN\n";
    reportContent += "-------------------\n";
    <?php foreach ($transport_breakdown as $item): ?>
    reportContent += "<?php echo $item['name']; ?>: <?php echo number_format($item['total_distance'], 1); ?> km (<?php echo number_format($item['carbon_emission'], 1); ?> kg CO₂)\n";
    <?php endforeach; ?>
    reportContent += "\n";
    
    reportContent += "RECOMMENDATIONS\n";
    reportContent += "---------------\n";
    <?php foreach ($recommendations as $recommendation): ?>
    reportContent += "- <?php echo $recommendation; ?>\n";
    <?php endforeach; ?>
    
    // Create a blob and download
    const blob = new Blob([reportContent], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'carbon_footprint_report_<?php echo date('Y-m-d'); ?>.txt';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
});
</script>

<?php require_once(__DIR__ . "/../includes/footer.php"); ?> 