<?php
// MySQL Database Configuration
$host = "localhost";
$dbname = "energy_tracker";
$username = "root";
$password = "";

// Try to connect to MySQL server first (without selecting a database)
$conn_server = mysqli_connect($host, $username, $password);

// Check server connection
if (!$conn_server) {
    die("Connection to MySQL server failed: " . mysqli_connect_error());
}

// Check if database exists, create if it doesn't
$check_db = mysqli_query($conn_server, "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
if (mysqli_num_rows($check_db) == 0) {
    // Database doesn't exist, create it
    if (mysqli_query($conn_server, "CREATE DATABASE IF NOT EXISTS $dbname")) {
        echo "Database created successfully!<br>";
    } else {
        die("Error creating database: " . mysqli_error($conn_server));
    }
}

// Now connect to the database
$conn = mysqli_connect($host, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8
mysqli_set_charset($conn, "utf8");

// Set timezone
date_default_timezone_set('Asia/Kolkata');
?>