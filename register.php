<?php
// Start session
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$form_message = '';
$message_type = '';

// Process registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'backend/db_config.php';
    
    // Get form data
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $userType = $_POST['userType'] ?? '';

    // Server-side validation
    if (empty($fullName) || empty($email) || empty($password) || empty($userType)) {
        $form_message = 'Error: Please fill out all required fields.';
        $message_type = 'error';
    } 
    elseif ($password !== $confirmPassword) {
        $form_message = 'Error: Passwords do not match.';
        $message_type = 'error';
    }
    elseif (strlen($password) < 6) {
        $form_message = 'Error: Password must be at least 6 characters.';
        $message_type = 'error';
    }
    else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            
            if ($stmt->fetch()) {
                $form_message = 'Error: Email already registered. Please login instead.';
                $message_type = 'error';
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Map userType to role
                $role = ($userType === 'cleaner') ? 'cleaner' : 'customer';
                
                // Insert new user
                $sql = "INSERT INTO users (name, email, phone, address, password, role) 
                        VALUES (:name, :email, :phone, :address, :password, :role)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':name' => $fullName,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':address' => $address,
                    ':password' => $hashedPassword,
                    ':role' => $role
                ]);
                
                // SUCCESS - Redirect to login with success message
                $_SESSION['registration_success'] = "Account created successfully! Please login with your credentials.";
                header('Location: login.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $form_message = 'Error: Unable to create account. Please try again.';
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - CleanCare</title>
    <link rel="stylesheet" href="frontend/css/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="auth-body">
    <section class="auth-section-centered">
        <div class="auth-container-centered" style="max-width: 550px;">
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
                <p class="brand-subtitle-small">Create your account</p>
            </div>

            <div class="auth-card-compact">
                <?php if ($form_message): ?>
                    <div class="<?= $message_type === 'error' ? 'error-message' : 'success-message' ?>" style="display: block; margin-bottom: 15px;">
                        <?= $form_message ?>
                    </div>
                <?php endif; ?>

                <form id="registerForm" method="POST" action="register.php" class="auth-form-compact">
                    <div class="form-row">
                        <input type="text" id="fullName" name="fullName" placeholder="Full name" required 
                               value="<?= htmlspecialchars($fullName ?? '') ?>">
                    </div>

                    <div class="form-row">
                        <input type="email" id="email" name="email" placeholder="Email address" required 
                               value="<?= htmlspecialchars($email ?? '') ?>">
                    </div>

                    <div class="form-row">
                        <input type="tel" id="phone" name="phone" placeholder="Phone number (e.g., 0712345678)" 
                               value="<?= htmlspecialchars($phone ?? '') ?>">
                    </div>

                    <div class="form-row">
                        <textarea id="address" name="address" rows="3" placeholder="Your address"><?= htmlspecialchars($address ?? '') ?></textarea>
                    </div>

                    <div class="form-row">
                        <select id="userType" name="userType" required>
                            <option value="">-- Select role --</option>
                            <option value="client" <?= isset($userType) && $userType === 'client' ? 'selected' : '' ?>>
                                Book Cleaning Services (Client)
                            </option>
                            <option value="cleaner" <?= isset($userType) && $userType === 'cleaner' ? 'selected' : '' ?>>
                                Work as a Cleaner (Cleaner)
                            </option>
                        </select>
                    </div>

                    <div class="form-row-split">
                        <input type="password" id="password" name="password" placeholder="Password" minlength="6" required>
                        <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm password" required>
                    </div>

                    <label class="checkbox-label-compact">
                        <input type="checkbox" id="terms" name="terms" required>
                        <span>I agree to the <a href="pages/terms.php">Terms & Conditions</a></span>
                    </label>

                    <div id="errorMessage" class="error-message" style="display:none;"></div>

                    <button type="submit" class="btn-submit-compact">Create Account</button>

                    <div class="divider-compact"><span>or</span></div>

                    <div class="text-center">
                        <p style="color: #606770;">Already have an account? 
                            <a href="login.php" style="color: #2c5aa0; font-weight: 600; text-decoration: none;">Sign In</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const errorMessage = document.getElementById('errorMessage');
            
            if (password !== confirmPassword) {
                e.preventDefault();
                errorMessage.textContent = 'Passwords do not match!';
                errorMessage.style.display = 'block';
                document.getElementById('confirmPassword').focus();
            }
        });
    </script>
</body>
</html>