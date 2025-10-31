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

// Handle settings update (placeholder)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = 'Settings updated successfully!';
    $message_type = 'success';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - CleanCare</title>
    <link rel="stylesheet" href="../frontend/css/style.css">
    <link rel="stylesheet" href="../frontend/css/dashboard.css">
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
                        <p class="tagline">System Settings</p>
                    </div>
                </div>
            </div>
            <ul class="nav-menu">
                <li><a href="../dashboards/admin.php">Dashboard</a></li>
                <li><a href="#" class="active">Settings</a></li>
            </ul>
            <div class="nav-buttons">
                <a href="../backend/logout.php" class="btn-primary">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Settings Content -->
    <section style="padding: 60px 0; background: #f8f9fa; min-height: calc(100vh - 80px);">
        <div class="container" style="max-width: 900px;">
            <h2 style="color: #2c5aa0; margin-bottom: 30px;">⚙️ System Settings</h2>
            
            <?php if ($message): ?>
                <div class="<?= $message_type === 'error' ? 'error-message' : 'success-message' ?>" style="display: block; margin-bottom: 20px;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- General Settings -->
            <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 30px;">
                <h3 style="color: #2c5aa0; margin-bottom: 20px;">General Settings</h3>
                <form method="POST">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Company Name</label>
                        <input type="text" value="CleanCare" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Contact Email</label>
                        <input type="email" value="info@cleancare.co.za" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Contact Phone</label>
                        <input type="tel" value="+27 21 123 4567" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;">
                    </div>
                    <button type="submit" class="btn-primary" style="border: none; cursor: pointer;">Save Changes</button>
                </form>
            </div>

            <!-- Service Pricing -->
            <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 30px;">
                <h3 style="color: #2c5aa0; margin-bottom: 20px;">Service Pricing</h3>
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Regular Cleaning</label>
                            <input type="number" value="500" step="0.01" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Deep Cleaning</label>
                            <input type="number" value="1200" step="0.01" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Move In/Out</label>
                            <input type="number" value="1500" step="0.01" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Office Cleaning</label>
                            <input type="number" value="800" step="0.01" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;">
                        </div>
                    </div>
                    <button type="submit" class="btn-primary" style="border: none; cursor: pointer;">Update Pricing</button>
                </form>
            </div>

            <!-- System Maintenance -->
            <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                <h3 style="color: #2c5aa0; margin-bottom: 20px;">System Maintenance</h3>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <button onclick="viewLogs()" class="btn-outline" style="cursor: pointer;">📋 View System Logs</button>
                </div>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <a href="../dashboards/admin.php" style="color: #2c5aa0; text-decoration: none; font-weight: 600;">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2025 CleanCare. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="../frontend/js/main.js"></script>
    <script>
        function viewLogs() {
            alert('System Logs (Demo)\n\n2025-10-31 10:30 - User login: admin@cleancare.com\n2025-10-31 10:45 - New booking created\n2025-10-31 11:00 - Payment processed');
        }
    </script>
</body>
</html>