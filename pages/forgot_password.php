<?php
session_start();
require_once '../backend/db_config.php';
require_once '../backend/functions.php';

$email = '';
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $message = "Please enter your email address.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "error";
    } else {
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Generate secure reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Create password_resets table if it doesn't exist
                $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token VARCHAR(64) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    used TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_token (token)
                )");
                
                // Delete any existing tokens for this user
                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = :user_id");
                $stmt->execute([':user_id' => $user['id']]);
                
                // Insert new token
                $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires)");
                $stmt->execute([
                    ':user_id' => $user['id'],
                    ':token' => $token,
                    ':expires' => $expires
                ]);
                
                // Create reset link
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $base_path = str_replace('/pages/forgot_password.php', '', $_SERVER['PHP_SELF']);
                $reset_link = $protocol . '://' . $host . $base_path . '/pages/reset_password.php?token=' . $token;
                
                // Send email
                $subject = "Password Reset Request - CleanCare";
                $html_message = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #2c5aa0; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                        .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
                        .btn { background: #2c5aa0; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; display: inline-block; margin: 20px 0; }
                        .warning { background: #fff3cd; border-left: 4px solid #FFC107; padding: 15px; margin: 20px 0; }
                        .footer { text-align: center; padding: 20px; color: #666; font-size: 0.9rem; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1 style='margin: 0;'>🔒 Password Reset Request</h1>
                        </div>
                        <div class='content'>
                            <p>Hi <strong>" . htmlspecialchars($user['name']) . "</strong>,</p>
                            <p>We received a request to reset your password for your CleanCare account.</p>
                            
                            <div style='text-align: center;'>
                                <a href='" . $reset_link . "' class='btn'>Reset Your Password</a>
                            </div>
                            
                            <p>Or copy and paste this link into your browser:</p>
                            <p style='background: white; padding: 10px; border-radius: 6px; word-break: break-all;'>" . $reset_link . "</p>
                            
                            <div class='warning'>
                                <p style='margin: 0;'><strong>⚠️ Important:</strong></p>
                                <ul style='margin: 10px 0 0 0; padding-left: 20px;'>
                                    <li>This link will expire in <strong>1 hour</strong></li>
                                    <li>If you didn't request this, please ignore this email</li>
                                    <li>Your password won't change until you create a new one</li>
                                </ul>
                            </div>
                            
                            <p style='margin-top: 30px;'>If you have any questions, feel free to contact our support team.</p>
                        </div>
                        <div class='footer'>
                            <p>&copy; 2025 CleanCare. All rights reserved.</p>
                            <p>This is an automated message, please do not reply to this email.</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                // Send email using the sendEmail function
                $email_sent = sendEmail($user['email'], $user['name'], $subject, $html_message);
                
                if ($email_sent) {
                    $message = "✅ Password reset instructions have been sent to your email address. Please check your inbox (and spam folder).";
                    $message_type = "success";
                } else {
                    $message = "⚠️ We found your account, but there was an error sending the email. Please try again or contact support.";
                    $message_type = "error";
                }
            } else {
                // For security, show success message even if email doesn't exist
                $message = "✅ If an account exists with this email, you will receive password reset instructions shortly.";
                $message_type = "success";
            }
            
        } catch (PDOException $e) {
            error_log("Forgot password error: " . $e->getMessage());
            $message = "An error occurred. Please try again later.";
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
    <title>Forgot Password - CleanCare</title>
    <link rel="stylesheet" href="../frontend/css/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
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
                <p class="brand-subtitle-small">Reset your password</p>
            </div>

            <div class="auth-card-compact">
                <?php if ($message): ?>
                    <div class="<?= $message_type === 'success' ? 'success-message' : 'error-message' ?>" style="display: block; margin-bottom: 15px; padding: 12px; border-radius: 6px;">
                        <?= $message ?>
                    </div>
                <?php endif; ?>

                <?php if ($message_type !== 'success'): ?>
                <p style="text-align: center; color: #666; margin-bottom: 20px; font-size: 0.95rem;">
                    Enter your email address and we'll send you instructions to reset your password.
                </p>

                <form method="POST" action="forgot_password.php" class="auth-form-compact">
                    <div class="form-row">
                        <label for="email" style="display: block; margin-bottom: 5px; color: #333; font-weight: 600;">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="your.email@example.com" required value="<?= htmlspecialchars($email) ?>" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                    </div>

                    <button type="submit" class="btn-submit-compact" style="width: 100%; margin-top: 10px;">
                        📧 Send Reset Link
                    </button>
                </form>

                <div class="divider-compact"><span>or</span></div>
                <?php endif; ?>

                <div class="text-center">
                    <a href="../login.php" style="color: #2c5aa0; text-decoration: none; font-weight: 600;">
                        ← Back to Login
                    </a>
                </div>
            </div>

            <div style="text-align: center; margin-top: 20px; color: #999; font-size: 0.85rem;">
                <p>💡 <strong>Tips:</strong></p>
                <ul style="list-style: none; padding: 0; margin: 10px 0;">
                    <li>Check your spam folder if you don't see the email</li>
                    <li>The reset link expires in 1 hour</li>
                    <li>Contact support if you need help: support@cleancare.com</li>
                </ul>
            </div>
        </div>
    </section>
</body>
</html>