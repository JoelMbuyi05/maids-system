<?php
session_start();
require_once '../backend/db_config.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Fetch current user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = 'Error loading profile';
    $message_type = 'error';
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    
    try {
        if (!empty($new_password)) {
            // Update with new password
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = :name, phone = :phone, address = :address, password = :password WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':phone' => $phone,
                ':address' => $address,
                ':password' => $hashed,
                ':id' => $user_id
            ]);
        } else {
            // Update without changing password
            $stmt = $pdo->prepare("UPDATE users SET name = :name, phone = :phone, address = :address WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':phone' => $phone,
                ':address' => $address,
                ':id' => $user_id
            ]);
        }
        
        $_SESSION['user_name'] = $name;
        $message = 'Profile updated successfully!';
        $message_type = 'success';
        
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $message = 'Error updating profile: ' . $e->getMessage();
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - CleanCare</title>
    <link rel="stylesheet" href="../frontend/css/style.css">
    <link rel="stylesheet" href="../frontend/css/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <div class="logo-container">
                    <div class="logo-icon">
                        <svg viewBox="0 0 60 60" width="45" height="45">
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
                    <div class="logo-text">
                        <h1>CleanCare</h1>
                        <p class="tagline">My Profile</p>
                    </div>
                </div>
            </div>
            <ul class="nav-menu">
                <li><a href="../index.php">Home</a></li>
                <li><a href="<?= $_SESSION['user_role'] === 'admin' ? '../dashboards/admin.php' : ($_SESSION['user_role'] === 'cleaner' ? '../dashboards/cleaner.php' : '../dashboards/client.php') ?>">Dashboard</a></li>
            </ul>
            <div class="nav-buttons">
                <a href="../backend/logout.php" class="btn-primary">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Profile Form -->
    <section class="auth-section-centered" style="padding: 60px 20px;">
        <div class="auth-container-centered" style="max-width: 600px;">
            <h2 style="text-align: center; color: #2c5aa0; margin-bottom: 30px;">Edit Profile</h2>
            
            <?php if ($message): ?>
                <div class="<?= $message_type === 'error' ? 'error-message' : 'success-message' ?>" style="display: block; margin-bottom: 20px;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="auth-card-compact">
                <form method="POST" action="profile.php" class="auth-form-compact">
                    <div class="form-row">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Full Name</label>
                        <input type="text" name="name" required value="<?= htmlspecialchars($user['name']) ?>">
                    </div>

                    <div class="form-row">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Email</label>
                        <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background: #f5f5f5; cursor: not-allowed;">
                        <small style="color: #666; font-size: 0.85rem;">Email cannot be changed</small>
                    </div>

                    <div class="form-row">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Phone</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">
                    </div>

                    <div class="form-row">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Address</label>
                        <textarea name="address" rows="3"><?= htmlspecialchars($user['address']) ?></textarea>
                    </div>

                    <div class="form-row">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Role</label>
                        <input type="text" value="<?= ucfirst($user['role']) ?>" disabled style="background: #f5f5f5; cursor: not-allowed;">
                    </div>

                    <hr style="margin: 25px 0; border: none; border-top: 1px solid #ddd;">

                    <div class="form-row">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">New Password (leave blank to keep current)</label>
                        <input type="password" name="new_password" placeholder="Enter new password if changing" minlength="6">
                        <small style="color: #666; font-size: 0.85rem;">Minimum 6 characters</small>
                    </div>

                    <button type="submit" class="btn-submit-compact">Update Profile</button>
                </form>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="<?= $_SESSION['user_role'] === 'admin' ? '../dashboards/admin.php' : ($_SESSION['user_role'] === 'cleaner' ? '../dashboards/cleaner.php' : '../dashboards/client.php') ?>" style="color: #2c5aa0; text-decoration: none;">
                        ← Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </section>

    <script src="../frontend/js/main.js"></script>
</body>
</html>