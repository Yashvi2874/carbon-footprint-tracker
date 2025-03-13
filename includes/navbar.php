<?php
?>
<nav style="background-color:#333; color:white; padding:10px 0;">
    <button class="menu-toggle" aria-label="Toggle menu">☰</button>
    <ul style="list-style:none; padding:0; margin:0; display:flex; justify-content:space-around;">
        <li style="display:inline;">
            <a href="<?php echo BASE_URL; ?>/index.php" style="color:white; text-decoration:none; padding:10px 20px; display:block;">Home</a>
        </li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <li style="display:inline;">
                <a href="<?php echo BASE_URL; ?>/views/dashboard.php" style="color:white; text-decoration:none; padding:10px 20px; display:block;">Dashboard</a>
            </li>
            <li style="display:inline;">
                <a href="<?php echo BASE_URL; ?>/views/log_energy.php" style="color:white; text-decoration:none; padding:10px 20px; display:block;">Log Energy</a>
            </li>
            <li style="display:inline;">
                <a href="<?php echo BASE_URL; ?>/views/log_transport.php" style="color:white; text-decoration:none; padding:10px 20px; display:block;">Log Transport</a>
            </li>
            <li style="display:inline;">
                <a href="<?php echo BASE_URL; ?>/views/recommendations.php" style="color:white; text-decoration:none; padding:10px 20px; display:block;">Recommendations</a>
            </li>
            <li style="display:inline;">
                <a href="<?php echo BASE_URL; ?>/views/report.php" style="color:white; text-decoration:none; padding:10px 20px; display:block;">Reports</a>
            </li>
            <li class="user-profile-link">
                <a href="<?php echo BASE_URL; ?>/views/profile.php" style="color:white; text-decoration:none; padding:10px 20px; display:block;">Profile</a>
            </li>
            <li style="display:inline;">
                <a href="<?php echo BASE_URL; ?>/api/logout.php" style="color:white; text-decoration:none; padding:10px 20px; display:block;">Logout</a>
            </li>
        <?php else: ?>
            <li style="display:inline;">
                <a href="<?php echo BASE_URL; ?>/views/login.php" style="color:white; text-decoration:none; padding:10px 20px; display:block;">Login</a>
            </li>
            <li style="display:inline;">
                <a href="<?php echo BASE_URL; ?>/views/register.php" style="color:white; text-decoration:none; padding:10px 20px; display:block;">Register</a>
            </li>
        <?php endif; ?>
    </ul>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.querySelector('.menu-toggle');
        const navMenu = document.querySelector('nav ul');
        
        menuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('show');
        });
    });
    </script>
</nav>