<?php
session_start();
require_once '../backend/db_config.php';

// Check if logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $message = 'All fields are required';
        $message_type = 'error';
    } else {
        try {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            
            if ($stmt->fetch()) {
                $message = 'Email already exists';
                $message_type = 'error';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, address, password, role) 
                                       VALUES (:name, :email, :phone, :address, :password, :role)");
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':address' => $address,
                    ':password' => $hashed_password,
                    ':role' => $role
                ]);
                
                $message = 'User added successfully!';
                $message_type = 'success';
                
                // Clear form
                $name = $email = $phone = $address = '';
            }
        } catch (PDOException $e) {
            $message = 'Error: ' . $e->getMessage();
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
    <title>Add User - CleanCare</title>
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
                        <p class="tagline">Add User</p>
                    </div>
                </div>
            </div>
            <ul class="nav-menu">
                <li><a href="../dashboards/admin.php">Dashboard</a></li>
                <li><a href="#" class="active">Add User</a></li>
            </ul>
            <div class="nav-buttons">
                <a href="../backend/logout.php" class="btn-primary">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Add User Form -->
    <section class="auth-section-centered" style="padding: 60px 20px;">
        <div class="auth-container-centered" style="max-width: 600px;">
            <h2 style="text-align: center; color: #2c5aa0; margin-bottom: 30px;">➕ Add New User</h2>
            
            <?php if ($message): ?>
                <div class="<?= $message_type === 'error' ? 'error-message' : 'success-message' ?>" style="display: block; margin-bottom: 20px;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="auth-card-compact">
                <form method="POST" action="add_user.php" class="auth-form-compact">
                    <div class="form-row">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Full Name *</label>
                        <input type="text" name="name" required value="<?= htmlspecialchars($name ?? '') ?>">
                    </div>

                    <div class="form-row">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Email *</label>
                        <input type="email" name="email" required value="<?= htmlspecialchars($email ?? '') ?>">
                    </div>

                    <div class="form-row">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Phone</label>
                        <input type="tel" name="phone" placeholder="0712345678" value="<?= htmlspecialchars($phone ?? '') ?>">
                    </div>

                    <div class="form-row">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Address</label>
                        <textarea name="address" rows="3" placeholder="Enter address"><?= htmlspecialchars($address ?? '') ?></textarea>
                    </div>

                    <div class="form-row">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Role *</label>
                        <select name="role" required>
                            <option value="">-- Select Role --</option>
                            <option value="customer">Customer</option>
                            <option value="cleaner">Cleaner</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Password *</label>
                        <input type="password" name="password" required minlength="6" placeholder="Minimum 6 characters">
                    </div>

                    <button type="submit" class="btn-submit-compact">Add User</button>
                </form>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="../dashboards/admin.php" style="color: #2c5aa0; text-decoration: none;">
                        ← Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </section>

    <script src="../frontend/js/main.js"></script>
</body>
</html>