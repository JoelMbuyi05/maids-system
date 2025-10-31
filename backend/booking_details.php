<?php
session_start();
require_once '../backend/db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$booking_id = intval($_GET['id'] ?? 0);

try {
    $stmt = $pdo->prepare("SELECT b.*, 
                           c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
                           cl.name as cleaner_name, cl.phone as cleaner_phone
                           FROM bookings b
                           LEFT JOIN users c ON b.customer_id = c.id
                           LEFT JOIN users cl ON b.cleaner_id = cl.id
                           WHERE b.id = :id");
    $stmt->execute([':id' => $booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        header('Location: ../dashboards/admin.php');
        exit;
    }
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}

$status_colors = [
    'pending' => '#FFC107',
    'confirmed' => '#28a745',
    'completed' => '#17a2b8',
    'cancelled' => '#dc3545'
];
$status_color = $status_colors[$booking['status']] ?? '#666';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - CleanCare</title>
    <link rel="stylesheet" href="../frontend/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
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
                        <p class="tagline">Booking Details</p>
                    </div>
                </div>
            </div>
            <ul class="nav-menu">
                <li><a href="../dashboards/<?= $_SESSION['user_role'] ?>.php">Dashboard</a></li>
            </ul>
            <div class="nav-buttons">
                <a href="../backend/logout.php" class="btn-primary">Logout</a>
            </div>
        </div>
    </nav>

    <section style="padding: 60px 0; background: #f8f9fa; min-height: calc(100vh - 80px);">
        <div class="container" style="max-width: 800px;">
            <h2 style="color: #2c5aa0; margin-bottom: 30px;">📋 Booking Details #<?= $booking['id'] ?></h2>
            
            <div style="background: white; padding: 40px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                <!-- Status Badge -->
                <div style="text-align: center; margin-bottom: 30px;">
                    <span style="background: <?= $status_color ?>; color: white; padding: 10px 30px; border-radius: 25px; font-size: 1.1rem; font-weight: 600;">
                        <?= ucfirst($booking['status']) ?>
                    </span>
                </div>

                <!-- Service Info -->
                <div style="margin-bottom: 30px; padding-bottom: 30px; border-bottom: 2px solid #f0f0f0;">
                    <h3 style="color: #2c5aa0; margin-bottom: 20px;">Service Information</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <strong style="color: #666;">Service Type:</strong>
                            <p style="margin: 5px 0; font-size: 1.1rem;"><?= htmlspecialchars($booking['service_type']) ?></p>
                        </div>
                        <div>
                            <strong style="color: #666;">Date & Time:</strong>
                            <p style="margin: 5px 0; font-size: 1.1rem;">
                                <?= date('d M Y', strtotime($booking['date'])) ?> at <?= date('H:i', strtotime($booking['time'])) ?>
                            </p>
                        </div>
                        <div>
                            <strong style="color: #666;">Address:</strong>
                            <p style="margin: 5px 0;"><?= htmlspecialchars($booking['address']) ?></p>
                        </div>
                        <div>
                            <strong style="color: #666;">Price:</strong>
                            <p style="margin: 5px 0; font-size: 1.3rem; font-weight: 700; color: #2c5aa0;">R<?= number_format($booking['price'], 2) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Customer Info -->
                <div style="margin-bottom: 30px; padding-bottom: 30px; border-bottom: 2px solid #f0f0f0;">
                    <h3 style="color: #2c5aa0; margin-bottom: 20px;">Customer Information</h3>
                    <p><strong>Name:</strong> <?= htmlspecialchars($booking['customer_name']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($booking['customer_phone']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($booking['customer_email']) ?></p>
                </div>

                <!-- Cleaner Info -->
                <div>
                    <h3 style="color: #2c5aa0; margin-bottom: 20px;">Assigned Cleaner</h3>
                    <?php if ($booking['cleaner_name']): ?>
                        <p><strong>Name:</strong> <?= htmlspecialchars($booking['cleaner_name']) ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($booking['cleaner_phone']) ?></p>
                    <?php else: ?>
                        <p style="color: #999; font-style: italic;">No cleaner assigned yet</p>
                    <?php endif; ?>
                </div>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <a href="../dashboards/<?= $_SESSION['user_role'] ?>.php" class="btn-primary" style="text-decoration: none;">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2025 CleanCare. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>