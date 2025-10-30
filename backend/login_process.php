<?php
// Start the session at the very beginning of the script
session_start();

// Check if the user is already logged in, redirect to index if true
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Include the database connection file.
require_once 'includes/db_config.php';

$email = $password = '';
$error_message = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Collect and sanitize input data
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error_message = "Please enter both email and password.";
    }

    if (empty($error_message)) {
        try {
            // 2. Fetch user data from the database by email
            $sql = "SELECT id, name, password, role FROM users WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 3. Check if user exists and verify password
            if ($user && password_verify($password, $user['password'])) {
                
                // Password is correct, start session and redirect
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role']; // 'admin', 'cleaner', or 'customer'

                // Redirect to the dashboard or home page
                header('Location: index.php'); 
                exit;
            } else {
                // Invalid credentials
                $error_message = "Invalid email or password. Please try again.";
            }

        } catch (PDOException $e) {
            // Log the error and display a generic message
            error_log("Login error: " . $e->getMessage());
            $error_message = "An error occurred during login. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - CleanCare</title>
    <!-- Assuming your auth.css is in a 'css' folder relative to this file's location -->
    <link rel="stylesheet" href="css/auth.css">
    <style>
        /* Placeholder for Inter font if needed, otherwise rely on auth.css */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        .auth-body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="auth-body">
    <!-- Login Section -->
    <section class="auth-section-centered">
        <div class="auth-container-centered">
            <!-- Logo/Brand (using your provided SVG structure) -->
            <div class="auth-brand-compact">
                <div class="logo-icon-small">
                    <svg viewBox="0 0 60 60" width="60" height="60">
                        <circle cx="30" cy="30" r="28" fill="#2c5aa0"/>
                        <path d="M 20 32 L 30 22 L 40 32 L 40 42 L 20 42 Z" fill="white"/>
                        <path d="M 18 32 L 30 20 L 42 32" stroke="white" stroke-width="2" fill="none" stroke-linecap="round"/>
                        <rect x="27" y="36" width="6" height="6" fill="#2c5aa0"/>
                        <circle cx="36" cy="26" r="1.5" fill="#FFC107"/>
                        <path d="M 36 23 L 36 29 M 33 26 L 39 26" stroke="#FFC107" stroke-width="1.5" stroke-linecap="round"/>
                        <circle cx="24" cy="28" r="1" fill="#FFC107"/>
                        <path d="M 24 26 L 24 30 M 22 28 L 26 28" stroke="#FFC107" stroke-width="1" stroke-linecap="round"/>
                    </svg>
                </div>
                <h1 class="brand-title-small">CleanCare</h1>
                <p class="brand-subtitle-small">Sign in to continue</p>
            </div>

            <div class="auth-card-compact">
                <!-- Error Message Display -->
                <?php if ($error_message): ?>
                    <div class="error-message" style="display: block; margin-bottom: 15px; text-align: center; color: #cc0000; font-weight: bold;">
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>
                
                <form id="loginForm" method="POST" action="login.php" class="auth-form-compact">
                    <div class="form-row">
                        <input type="email" id="email" name="email" placeholder="Email address" required value="<?= htmlspecialchars($email) ?>">
                    </div>

                    <div class="form-row">
                        <input type="password" id="password" name="password" placeholder="Password" required>
                    </div>

                    <button type="submit" class="btn-submit-compact">Sign In</button>

                    <div class="text-center" style="margin-top: 12px;">
                        <a href="#" class="forgot-link">Forgot Password?</a>
                    </div>
                </form>

                <div class="divider-compact"><span>or</span></div>

                <div class="text-center">
                    <a href="register.php" class="btn-create-account-compact">Create New Account</a>
                </div>
            </div>
        </div>
    </section>
</body>
</html>
