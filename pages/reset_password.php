<?php
session_start();
require_once 'backend/db_config.php';

$token = $_GET['token'] ?? '';
$message = '';
$message_type = '';
$token_valid = false;
$user_id = null;

// Verify token
if (empty($token)) {
    $message = "Invalid or missing reset token.";
    $message_type = "error";
} else {
    try {
        // Check if token exists and is valid
        $stmt = $pdo->prepare("SELECT pr.*, u.name, u.email 
                              FROM password_resets pr 
                              JOIN users u ON pr.user_id = u.id 
                              WHERE pr.token = :token 
                              AND pr.used = 0 
                              AND pr.expires_at > NOW()");
        $stmt->execute([':token' => $token]);
        $reset_request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reset_request) {
            $token_valid = true;
            $user_id = $reset_request['user_id'];
        } else {
            // Check if token exists but is expired or used
            $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = :token");
            $stmt->execute([':token' => $token]);
            $expired_token = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($expired_token) {
                if ($expired_token['used'] == 1) {
                    $message = "This reset link has already been used. Please request a new one.";
                } else {
                    $message = "This reset link has expired. Please request a new one.";
                }
            } else {
                $message = "Invalid reset link. Please request a new one.";
            }
            $message_type = "error";
        }
    } catch (PDOException $e) {
        error_log("Token verification error: " . $e->getMessage());
        $message = "An error occurred. Please try again.";
        $message_type = "error";
    }
}

// Handle password reset submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && $token_valid) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($password) || empty($confirm_password)) {
        $message = "Please fill in all fields.";
        $message_type = "error";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters long.";
        $message_type = "error";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "error";
    } else {
        try {
            // Hash the new password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            // Update user's password
            $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->execute([
                ':password' => $hashed_password,
                ':id' => $user_id
            ]);
            
            // Mark token as used
            $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = :token");
            $stmt->execute([':token' => $token]);
            
            // Set success message and redirect after 3 seconds
            $message = "✅ Password reset successful! Redirecting to login...";
            $message_type = "success";
            
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'login.php';
                }, 3000);
            </script>";
            
        } catch (PDOException $e) {
            error_log("Password reset error: " . $e->getMessage());
            $message = "An error occurred while resetting your password. Please try again.";
            $message_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - CleanCare</title>
    <link rel="stylesheet" href="frontend/css/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .password-strength {
            margin-top: 5px;
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            transition: all 0.3s;
            width: 0%;
        }
        .strength-weak { background: #dc3545; width: 33%; }
        .strength-medium { background: #FFC107; width: 66%; }
        .strength-strong { background: #28a745; width: 100%; }
        .password-requirements {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 0.85rem;
        }
        .requirement {
            color: #999;
            margin: 5px 0;
        }
        .requirement.met {
            color: #28a745;
        }
        .requirement:before {
            content: "○ ";
        }
        .requirement.met:before {
            content: "✓ ";
            font-weight: bold;
        }
    </style>
</head>
<body class="auth-body">
    <section class="auth-section-centered">
        <div class="auth-container-centered">
            <!-- Logo/Brand -->
            <div class="auth-brand-compact">
                <div class="logo-icon-small">
                    <svg viewBox="0 0 60 60" width="60" height="60">
                        <circle cx="30" cy="30" r="28" fill="#2c5aa0"/>
                        <path d="M 20 32 L 30 22 L 40 32 L 40 42 L 20 42 Z" fill="white"/>
                    </svg>
                </div>
                <h1 class="brand-title-small">CleanCare</h1>
                <p class="brand-subtitle-small">Create new password</p>
            </div>

            <div class="auth-card-compact">
                <?php if ($message): ?>
                    <div class="<?= $message_type === 'success' ? 'success-message' : 'error-message' ?>" style="display: block; margin-bottom: 15px; padding: 12px; border-radius: 6px;">
                        <?= $message ?>
                    </div>
                <?php endif; ?>

                <?php if ($token_valid && $message_type !== 'success'): ?>
                <p style="text-align: center; color: #666; margin-bottom: 20px; font-size: 0.95rem;">
                    🔒 Enter your new password below.
                </p>

                <form method="POST" action="reset_password.php?token=<?= htmlspecialchars($token) ?>" class="auth-form-compact" id="resetForm">
                    <div class="form-row">
                        <label for="password" style="display: block; margin-bottom: 5px; color: #333; font-weight: 600;">New Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter new password" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <small id="strengthText" style="color: #999; margin-top: 5px; display: block;"></small>
                    </div>

                    <div class="password-requirements">
                        <strong>Password must contain:</strong>
                        <div class="requirement" id="req-length">At least 8 characters</div>
                        <div class="requirement" id="req-uppercase">One uppercase letter</div>
                        <div class="requirement" id="req-lowercase">One lowercase letter</div>
                        <div class="requirement" id="req-number">One number</div>
                    </div>

                    <div class="form-row" style="margin-top: 15px;">
                        <label for="confirm_password" style="display: block; margin-bottom: 5px; color: #333; font-weight: 600;">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                        <small id="matchText" style="margin-top: 5px; display: block;"></small>
                    </div>

                    <button type="submit" class="btn-submit-compact" id="submitBtn" style="width: 100%; margin-top: 20px;">
                        🔒 Reset Password
                    </button>
                </form>

                <div class="divider-compact"><span>or</span></div>

                <?php else: ?>
                <div style="text-align: center; padding: 20px;">
                    <p style="color: #666; margin-bottom: 15px;">Need a new reset link?</p>
                    <a href="forgot_password.php" style="display: inline-block; background: #2c5aa0; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600;">
                        Request New Link
                    </a>
                </div>
                <div class="divider-compact"><span>or</span></div>
                <?php endif; ?>

                <div class="text-center">
                    <a href="login.php" style="color: #2c5aa0; text-decoration: none; font-weight: 600;">
                        ← Back to Login
                    </a>
                </div>
            </div>
        </div>
    </section>

    <script>
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        const matchText = document.getElementById('matchText');
        const submitBtn = document.getElementById('submitBtn');

        // Password strength checker
        passwordInput?.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Check requirements
            const hasLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasLowercase = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            
            // Update requirement indicators
            document.getElementById('req-length').classList.toggle('met', hasLength);
            document.getElementById('req-uppercase').classList.toggle('met', hasUppercase);
            document.getElementById('req-lowercase').classList.toggle('met', hasLowercase);
            document.getElementById('req-number').classList.toggle('met', hasNumber);
            
            // Calculate strength
            if (hasLength) strength++;
            if (hasUppercase) strength++;
            if (hasLowercase) strength++;
            if (hasNumber) strength++;
            if (password.length >= 12) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++; // Special characters
            
            // Update strength bar
            strengthBar.className = 'password-strength-bar';
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Weak password';
                strengthText.style.color = '#dc3545';
            } else if (strength <= 4) {
                strengthBar.classList.add('strength-medium');
                strengthText.textContent = 'Medium strength';
                strengthText.style.color = '#FFC107';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Strong password';
                strengthText.style.color = '#28a745';
            }
        });

        // Password match checker
        confirmInput?.addEventListener('input', function() {
            const password = passwordInput.value;
            const confirm = this.value;
            
            if (confirm.length === 0) {
                matchText.textContent = '';
            } else if (password === confirm) {
                matchText.textContent = '✓ Passwords match';
                matchText.style.color = '#28a745';
            } else {
                matchText.textContent = '✗ Passwords do not match';
                matchText.style.color = '#dc3545';
            }
        });

        // Form validation
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long');
                return;
            }
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match');
                return;
            }
        });
    </script>
</body>
</html>