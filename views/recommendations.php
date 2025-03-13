<?php
require_once(__DIR__ . "/../includes/header.php");
require_once(__DIR__ . "/../includes/navbar.php");
require_once(__DIR__ . "/../db/config.php");

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/views/login.php");
    exit();
}

// Get user's energy usage
$user_id = $_SESSION['user_id'];
$energy_stmt = mysqli_prepare($conn, "SELECT SUM(kwh_consumed) AS total_energy FROM energy_usage WHERE user_id = ?");
mysqli_stmt_bind_param($energy_stmt, "i", $user_id);
mysqli_stmt_execute($energy_stmt);
$energy_result = mysqli_stmt_get_result($energy_stmt);
$energy_data = mysqli_fetch_assoc($energy_result);
mysqli_stmt_close($energy_stmt);
$total_energy = $energy_data['total_energy'] ?? 0;

// Get user's transport usage
$transport_stmt = mysqli_prepare($conn, 
    "SELECT tm.name, ut.distance_km, tm.carbon_emission
     FROM user_transport ut 
     JOIN transport_modes tm ON ut.transport_id = tm.id 
     WHERE ut.user_id = ?"
);
mysqli_stmt_bind_param($transport_stmt, "i", $user_id);
mysqli_stmt_execute($transport_stmt);
$transport_result = mysqli_stmt_get_result($transport_stmt);
$transport_data = [];
while ($row = mysqli_fetch_assoc($transport_result)) {
    $transport_data[] = $row;
}
mysqli_stmt_close($transport_stmt);

// Get user's appliances
$appliance_stmt = mysqli_prepare($conn, 
    "SELECT a.name, ua.usage_hours, a.power_rating
     FROM user_appliances ua 
     JOIN appliances a ON ua.appliance_id = a.id 
     WHERE ua.user_id = ?"
);
mysqli_stmt_bind_param($appliance_stmt, "i", $user_id);
mysqli_stmt_execute($appliance_stmt);
$appliance_result = mysqli_stmt_get_result($appliance_stmt);
$appliance_data = [];
while ($row = mysqli_fetch_assoc($appliance_result)) {
    $appliance_data[] = $row;
}
mysqli_stmt_close($appliance_stmt);

// Determine high-usage appliances
$high_usage_appliances = [];
foreach ($appliance_data as $appliance) {
    if ($appliance['usage_hours'] > 5 && $appliance['power_rating'] > 1000) {
        $high_usage_appliances[] = $appliance['name'];
    }
}

// Determine high-emission transport
$high_emission_transport = [];
foreach ($transport_data as $transport) {
    if ($transport['carbon_emission'] > 0.1 && $transport['distance_km'] > 10) {
        $high_emission_transport[] = $transport['name'];
    }
}

// General recommendations
$general_recommendations = [
    "Switch to LED bulbs to save up to 80% energy compared to traditional bulbs.",
    "Unplug electronics when not in use to reduce standby power consumption.",
    "Use a programmable thermostat to automatically adjust temperature settings.",
    "Wash clothes in cold water to save energy used for heating.",
    "Keep your refrigerator coils clean to improve efficiency.",
    "Use natural light when possible instead of artificial lighting.",
    "Install weather stripping around doors and windows to prevent air leaks.",
    "Consider upgrading to ENERGY STAR certified appliances.",
    "Use power strips to easily turn off multiple devices at once.",
    "Regularly maintain your HVAC system for optimal efficiency."
];

// Energy-specific recommendations
$energy_recommendations = [
    "Turn off lights when leaving a room.",
    "Use energy-efficient appliances.",
    "Adjust your thermostat by a few degrees to save energy.",
    "Insulate your home to prevent heat loss.",
    "Use a clothesline instead of a dryer when possible.",
    "Install solar panels to generate renewable energy.",
    "Replace old windows with energy-efficient ones.",
    "Use a laptop instead of a desktop computer.",
    "Cook with lids on pots to reduce cooking time.",
    "Use a microwave instead of an oven for small meals."
];

// Transport-specific recommendations
$transport_recommendations = [
    "Use public transportation when possible.",
    "Consider carpooling to reduce emissions.",
    "Maintain proper tire pressure for better fuel efficiency.",
    "Combine errands to reduce the number of trips.",
    "Consider an electric or hybrid vehicle for your next purchase.",
    "Walk or bike for short distances.",
    "Avoid excessive idling of your vehicle.",
    "Use video conferencing instead of traveling for meetings.",
    "Plan efficient routes to minimize distance traveled.",
    "Consider telecommuting if your job allows it."
];

// Select recommendations based on user data
$selected_recommendations = [];

// Add appliance-specific recommendations
if (!empty($high_usage_appliances)) {
    $selected_recommendations[] = "Consider reducing usage of high-energy appliances like: " . implode(", ", $high_usage_appliances);
}

// Add transport-specific recommendations
if (!empty($high_emission_transport)) {
    $selected_recommendations[] = "Look for alternatives to high-emission transport modes like: " . implode(", ", $high_emission_transport);
}

// Add energy recommendations if energy usage is high
if ($total_energy > 300) {
    shuffle($energy_recommendations);
    $selected_recommendations = array_merge($selected_recommendations, array_slice($energy_recommendations, 0, 3));
}

// Add transport recommendations
shuffle($transport_recommendations);
$selected_recommendations = array_merge($selected_recommendations, array_slice($transport_recommendations, 0, 2));

// Add general recommendations
shuffle($general_recommendations);
$selected_recommendations = array_merge($selected_recommendations, array_slice($general_recommendations, 0, 3));

// Ensure we have at least 5 recommendations
if (count($selected_recommendations) < 5) {
    $more_general = array_slice($general_recommendations, 0, 5 - count($selected_recommendations));
    $selected_recommendations = array_merge($selected_recommendations, $more_general);
}
?>

<main>
    <h1>Personalized Energy Saving Recommendations</h1>
    
    <div class="card">
        <h3>Based on your energy usage</h3>
        <p>Here are some personalized recommendations to help you reduce your energy consumption and carbon footprint:</p>
        
        <ul class="recommendations-list">
            <?php foreach ($selected_recommendations as $recommendation): ?>
                <li><?php echo htmlspecialchars($recommendation); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <div class="recommendations-sections">
        <div class="card">
            <h3>Energy Saving Tips</h3>
            <ul>
                <?php 
                shuffle($energy_recommendations);
                foreach (array_slice($energy_recommendations, 0, 5) as $tip): 
                ?>
                    <li><?php echo htmlspecialchars($tip); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="card">
            <h3>Transport Efficiency Tips</h3>
            <ul>
                <?php 
                shuffle($transport_recommendations);
                foreach (array_slice($transport_recommendations, 0, 5) as $tip): 
                ?>
                    <li><?php echo htmlspecialchars($tip); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</main>

<?php require_once(__DIR__ . "/../includes/footer.php"); ?> 