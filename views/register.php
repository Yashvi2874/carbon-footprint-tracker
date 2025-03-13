<?php
require_once(__DIR__ . "/../includes/header.php");
require_once(__DIR__ . "/../includes/navbar.php");
require_once(__DIR__ . "/../db/config.php");

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/views/dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $household_size = $_POST['household_size'];
    
    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
    } else {
        // Check if email already exists
        $check_query = "SELECT id FROM users WHERE email = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $_SESSION['error'] = "Email already in use.";
            mysqli_stmt_close($check_stmt);
        } else {
            mysqli_stmt_close($check_stmt);
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $insert_query = "INSERT INTO users (name, email, password, household_size) VALUES (?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "sssi", $name, $email, $hashed_password, $household_size);
            $insert_result = mysqli_stmt_execute($insert_stmt);
            
            if ($insert_result) {
                $user_id = mysqli_insert_id($conn);
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                
                $_SESSION['success'] = "Registration successful!";
                mysqli_stmt_close($insert_stmt);
                header("Location: " . BASE_URL . "/views/dashboard.php");
                exit();
            } else {
                $_SESSION['error'] = "Registration failed. Please try again.";
                mysqli_stmt_close($insert_stmt);
            }
        }
    }
}
?>

<main>
    <h1>Register</h1>
    
    <div class="card">
        <form method="POST">
            <label for="name">Name:</label>
            <input type="text" name="name" required>
            
            <label for="email">Email:</label>
            <input type="email" name="email" required>
            
            <label for="password">Password:</label>
            <input type="password" name="password" required>
            
            <label for="confirm_password">Confirm Password:</label>
            <input type="password" name="confirm_password" required>
            
            <label for="household_size">Household Size (Household size refers to the number of people living in your home who share energy usage and appliances.):</label>
            <input type="number" name="household_size" min="1" value="1">
            
            <button type="submit">Register</button>
        </form>
        
        <p class="text-center">Already have an account? <a href="<?php echo BASE_URL; ?>/views/login.php">Login here</a></p>
    </div>
</main>

<?php require_once(__DIR__ . "/../includes/footer.php"); ?>