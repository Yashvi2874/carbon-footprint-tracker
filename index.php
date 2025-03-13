<?php
require_once(__DIR__ . "/includes/header.php");
require_once(__DIR__ . "/includes/navbar.php");
?>

<main class="home-main">
    <section class="hero">
        <div class="container">
            <h1>Track Your Carbon Footprint</h1>
            <p>Monitor your energy usage and transportation habits to reduce your environmental impact.</p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="cta-buttons">
                    <a href="<?php echo BASE_URL; ?>/views/register.php" class="btn btn-primary">Get Started</a>
                    <a href="<?php echo BASE_URL; ?>/views/login.php" class="btn btn-secondary">Login</a>
                </div>
            <?php else: ?>
                <div class="cta-buttons">
                    <a href="<?php echo BASE_URL; ?>/views/dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="features">
        <div class="container">
            <h2>Features</h2>
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Track Energy Usage</h3>
                    <p>Log your daily energy consumption from various appliances and see your impact over time.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🚗</div>
                    <h3>Monitor Transportation</h3>
                    <p>Record your travel methods and distances to understand your transportation carbon footprint.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📈</div>
                    <h3>Visualize Progress</h3>
                    <p>View detailed charts and reports to track your improvement over time.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💡</div>
                    <h3>Get Recommendations</h3>
                    <p>Receive personalized suggestions to reduce your carbon footprint based on your habits.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="how-it-works">
        <div class="container">
            <h2>How It Works</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Create an Account</h3>
                    <p>Sign up and set up your household profile to get started.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Log Your Usage</h3>
                    <p>Record your energy consumption and transportation habits.</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>View Your Impact</h3>
                    <p>See your carbon footprint and track changes over time.</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h3>Make Improvements</h3>
                    <p>Follow recommendations to reduce your environmental impact.</p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once(__DIR__ . "/includes/footer.php"); ?>
