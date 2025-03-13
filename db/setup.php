<?php
require_once("config.php");

// Create tables
$tables = [
    // Users table
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        household_size INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Appliances table
    "CREATE TABLE IF NOT EXISTS appliances (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        power_rating INT NOT NULL, -- in watts
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    // User appliances table
    "CREATE TABLE IF NOT EXISTS user_appliances (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        appliance_id INT,
        usage_hours FLOAT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, appliance_id),
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (appliance_id) REFERENCES appliances(id)
    )",
    
    // Energy usage table
    "CREATE TABLE IF NOT EXISTS energy_usage (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        date DATE NOT NULL,
        kwh_consumed FLOAT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, date),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )",
    
    // Transport modes table
    "CREATE TABLE IF NOT EXISTS transport_modes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        carbon_emission FLOAT NOT NULL, -- kg CO2 per km
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    // User transport table
    "CREATE TABLE IF NOT EXISTS user_transport (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        transport_id INT,
        distance_km FLOAT NOT NULL,
        date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, transport_id, date),
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (transport_id) REFERENCES transport_modes(id)
    )"
];

// Execute table creation queries
foreach ($tables as $query) {
    $result = mysqli_query($conn, $query);
    if (!$result) {
        echo "Error creating table: " . mysqli_error($conn) . "<br>";
    }
}

// Insert sample data for appliances
$appliances = [
    ["Refrigerator", 150],
    ["Air Conditioner", 1500],
    ["Washing Machine", 500],
    ["Television", 100],
    ["Computer", 300],
    ["Microwave", 1000],
    ["Electric Kettle", 1500],
    ["Dishwasher", 1200],
    ["Ceiling Fan", 75],
    ["Light Bulb (LED)", 10],
    ["Light Bulb (Incandescent)", 60],
    ["Water Heater", 4000],
    ["Hair Dryer", 1500],
    ["Vacuum Cleaner", 1400],
    ["Iron", 1000]
];

foreach ($appliances as $appliance) {
    $check_query = "SELECT id FROM appliances WHERE name = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "s", $appliance[0]);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) == 0) {
        $insert_query = "INSERT INTO appliances (name, power_rating) VALUES (?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "si", $appliance[0], $appliance[1]);
        $insert_result = mysqli_stmt_execute($insert_stmt);
        
        if (!$insert_result) {
            echo "Error inserting appliance: " . mysqli_error($conn) . "<br>";
        }
        mysqli_stmt_close($insert_stmt);
    }
    mysqli_stmt_close($stmt);
}

// Insert sample data for transport modes
$transport_modes = [
    ["Car (Petrol)", 0.192],
    ["Car (Diesel)", 0.171],
    ["Car (Electric)", 0.053],
    ["Bus", 0.105],
    ["Train", 0.041],
    ["Bicycle", 0],
    ["Walking", 0],
    ["Motorcycle", 0.103],
    ["Plane", 0.255]
];

foreach ($transport_modes as $mode) {
    $check_query = "SELECT id FROM transport_modes WHERE name = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "s", $mode[0]);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) == 0) {
        $insert_query = "INSERT INTO transport_modes (name, carbon_emission) VALUES (?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "sd", $mode[0], $mode[1]);
        $insert_result = mysqli_stmt_execute($insert_stmt);
        
        if (!$insert_result) {
            echo "Error inserting transport mode: " . mysqli_error($conn) . "<br>";
        }
        mysqli_stmt_close($insert_stmt);
    }
    mysqli_stmt_close($stmt);
}

echo "Database setup completed successfully!";
?>
