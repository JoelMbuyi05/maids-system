<?php
// Start the session at the very beginning
session_start();

// Check if redirected from registration
$registration_success = '';
if (isset($_SESSION['registration_success'])) {
    $registration_success = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}

// Check if the user is already logged in, redirect to index if true
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Include the database connection file - FIXED PATH
require_once 'backend/db_config.php';

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
            $sql = "SELECT id, name, email, password, role FROM users WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 3. Check if user exists and verify password
            if ($user && password_verify($password, $user['password'])) {
                
                // Password is correct, start session and redirect
                session_regenerate_id(true); // Security: regenerate session ID
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role']; // 'admin', 'cleaner', or 'customer'
                $_SESSION['last_activity'] = time();

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header('Location: dashboards/admin.php');
                } elseif ($user['role'] === 'cleaner') {
                    header('Location: dashboards/cleaner.php');
                } else {
                    header('Location: dashboards/client.php');
                }
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
    <link rel="stylesheet" href="frontend/css/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="auth-body">
    <!-- Login Section -->
    <section class="auth-section-centered">
        <div class="auth-container-centered">
            <!-- Logo/Brand -->
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
                <!-- Registration Success Message -->
                <?php if ($registration_success): ?>
                    <div class="success-message" style="display: block; margin-bottom: 15px; background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; border: 1px solid #c3e6cb;">
                        ✅ <?= htmlspecialchars($registration_success) ?>
                    </div>
                <?php endif; ?>

                <!-- Error Message Display -->
                <?php if ($error_message): ?>
                    <div class="error-message" style="display: block; margin-bottom: 15px;">
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>
                
                <!-- Check for timeout parameter -->
                <?php if (isset($_GET['timeout'])): ?>
                    <div class="error-message" style="display: block; margin-bottom: 15px;">
                        Your session has expired. Please log in again.
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
                
                <!-- Demo Credentials Info 
                <div style="margin-top: 20px; padding: 15px; background: #f0f8ff; border-radius: 8px; font-size: 0.85rem; color: #2c5aa0;">
                    <strong>🧪 Demo Accounts:</strong><br>
                    Admin: admin@cleancare.com / admin123<br>
                    Cleaner: sarah@cleancare.com / cleaner123<br>
                    Customer: john@example.com / customer123
                </div>-->
            </div>
        </div>
    </section>
</body>
</html>