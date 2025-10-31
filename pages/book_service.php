<?php
session_start();
require_once '../backend/db_config.php';

// Check if logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'customer') {
    header('Location: ../login.php');
    exit;
}

$user_name = $_SESSION['user_name'];

// Get available cleaners
try {
    $stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'cleaner' ORDER BY name");
    $cleaners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $cleaners = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Service - CleanCare</title>
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
                        <p class="tagline">Book Service</p>
                    </div>
                </div>
            </div>
            <ul class="nav-menu">
                <li><a href="../index.php">Home</a></li>
                <li><a href="../dashboards/client.php">Dashboard</a></li>
                <li><a href="#" class="active">Book Service</a></li>
            </ul>
            <div class="nav-buttons">
                <span style="margin-right: 15px; color: #666;">Welcome, <strong><?= htmlspecialchars($user_name) ?></strong></span>
                <a href="../backend/logout.php" class="btn-primary">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Booking Form -->
    <section style="padding: 60px 0; background: #f8f9fa; min-height: calc(100vh - 80px);">
        <div class="container">
            <div style="max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                <h2 style="color: #2c5aa0; margin-bottom: 30px; text-align: center;">📅 Book a Cleaning Service</h2>
                
                <div id="message"></div>
                
                <form id="bookingForm">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Service Type *</label>
                        <select name="service_type" id="service_type" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
                            <option value="">-- Select Service --</option>
                            <option value="Regular Cleaning">Regular Cleaning - R500</option>
                            <option value="Deep Cleaning">Deep Cleaning - R1,200</option>
                            <option value="Move In/Out">Move In/Out - R1,500</option>
                            <option value="Office Cleaning">Office Cleaning - R800</option>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Date *</label>
                            <input type="date" name="date" id="booking_date" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Time *</label>
                            <input type="time" name="time" id="booking_time" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Preferred Cleaner (Optional)</label>
                        <select name="cleaner_id" id="cleaner_id" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
                            <option value="">-- Any Available Cleaner --</option>
                            <?php foreach ($cleaners as $cleaner): ?>
                                <option value="<?= $cleaner['id'] ?>"><?= htmlspecialchars($cleaner['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Service Address *</label>
                        <textarea name="address" id="address" rows="3" required placeholder="Enter full address where service is needed" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; resize: vertical;"></textarea>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Estimated Price</label>
                        <input type="text" id="price_display" readonly value="R0.00" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1.2rem; font-weight: 700; background: #f8f9fa;">
                        <input type="hidden" name="price" id="price" value="0">
                    </div>

                    <button type="submit" style="width: 100%; padding: 15px; background: #2c5aa0; color: white; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer;">
                        Book Service Now
                    </button>
                </form>
            </div>
        </div>
    </section>

    <script src="../frontend/js/main.js"></script>
    <script src="../frontend/js/booking.js"></script>
    <script>
        // Update price when service is selected
        document.getElementById('service_type').addEventListener('change', function() {
            const prices = {
                'Regular Cleaning': 500,
                'Deep Cleaning': 1200,
                'Move In/Out': 1500,
                'Office Cleaning': 800
            };
            
            const price = prices[this.value] || 0;
            document.getElementById('price').value = price;
            document.getElementById('price_display').value = 'R' + price.toFixed(2);
        });
    </script>
</body>
</html>