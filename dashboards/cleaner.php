<?php
require_once '../backend/auth_check.php';
require_once '../backend/db_config.php';
require_once '../backend/functions.php';

// Ensure only cleaners can access
if ($user_role !== 'cleaner') {
    header('Location: ../index.php');
    exit;
}

// Fetch cleaner's bookings
try {
    $stmt = $pdo->prepare("SELECT b.*, u.name as customer_name, u.phone as customer_phone 
                           FROM bookings b 
                           LEFT JOIN users u ON b.customer_id = u.id 
                           WHERE b.cleaner_id = :user_id 
                           ORDER BY b.date DESC, b.time DESC 
                           LIMIT 10");
    $stmt->execute([':user_id' => $user_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stmt = $pdo->prepare("SELECT 
                           COUNT(*) as total,
                           SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                           SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as upcoming,
                           SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                           SUM(CASE WHEN status = 'completed' THEN price ELSE 0 END) as total_earnings
                           FROM bookings WHERE cleaner_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $bookings = [];
    $stats = ['total' => 0, 'pending' => 0, 'upcoming' => 0, 'completed' => 0, 'total_earnings' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleaner Dashboard - CleanCare</title>
    <link rel="stylesheet" href="../frontend/css/style.css">
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
                        <p class="tagline">Cleaner Dashboard</p>
                    </div>
                </div>
            </div>
            <ul class="nav-menu">
                <li><a href="../index.php">Home</a></li>
                <li><a href="#" class="active">Dashboard</a></li>
                <li><a href="#jobs">My Jobs</a></li>
            </ul>
            <div class="nav-buttons">
                <span style="margin-right: 15px; color: #666;">Welcome, <strong><?= htmlspecialchars($user_name) ?></strong></span>
                <a href="../backend/logout.php" class="btn-primary">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <section style="padding: 40px 0; background: #f8f9fa; min-height: calc(100vh - 80px);">
        <div class="container">
            <?php display_flash_message(); ?>
            
            <h2 style="color: #2c5aa0; margin-bottom: 30px;">Cleaner Dashboard</h2>
            
            <!-- Statistics Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">
                <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-left: 4px solid #2c5aa0;">
                    <h3 style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Total Jobs</h3>
                    <p style="font-size: 2rem; color: #2c5aa0; font-weight: 700; margin: 0;"><?= $stats['total'] ?></p>
                </div>
                <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-left: 4px solid #FFC107;">
                    <h3 style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Pending Requests</h3>
                    <p style="font-size: 2rem; color: #FFC107; font-weight: 700; margin: 0;"><?= $stats['pending'] ?></p>
                </div>
                <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-left: 4px solid #28a745;">
                    <h3 style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Upcoming Jobs</h3>
                    <p style="font-size: 2rem; color: #28a745; font-weight: 700; margin: 0;"><?= $stats['upcoming'] ?></p>
                </div>
                <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-left: 4px solid #17a2b8;">
                    <h3 style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Total Earnings</h3>
                    <p style="font-size: 2rem; color: #17a2b8; font-weight: 700; margin: 0;">R<?= number_format($stats['total_earnings'], 2) ?></p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 40px;">
                <h3 style="color: #2c5aa0; margin-bottom: 20px;">Quick Actions</h3>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <a href="#available-jobs" class="btn-primary" style="display: inline-block;">🔍 View Available Jobs</a>
                    <a href="#my-schedule" class="btn-outline">📅 My Schedule</a>
                    <a href="#profile" class="btn-outline">👤 Edit Profile</a>
                </div>
            </div>

            <!-- Assigned Jobs -->
            <div id="jobs" style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                <h3 style="color: #2c5aa0; margin-bottom: 20px;">My Assigned Jobs</h3>
                
                <?php if (empty($bookings)): ?>
                    <div style="text-align: center; padding: 40px; color: #999;">
                        <p style="font-size: 1.2rem; margin-bottom: 20px;">📭 No jobs assigned yet</p>
                        <p>Check back soon for new booking requests!</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                    <th style="padding: 12px; text-align: left;">Booking ID</th>
                                    <th style="padding: 12px; text-align: left;">Customer</th>
                                    <th style="padding: 12px; text-align: left;">Service</th>
                                    <th style="padding: 12px; text-align: left;">Date & Time</th>
                                    <th style="padding: 12px; text-align: left;">Address</th>
                                    <th style="padding: 12px; text-align: left;">Status</th>
                                    <th style="padding: 12px; text-align: left;">Payment</th>
                                    <th style="padding: 12px; text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): 
                                    $status_colors = [
                                        'pending' => '#FFC107',
                                        'confirmed' => '#28a745',
                                        'completed' => '#17a2b8',
                                        'cancelled' => '#dc3545'
                                    ];
                                    $status_color = $status_colors[$booking['status']] ?? '#666';
                                ?>
                                <tr style="border-bottom: 1px solid #dee2e6;">
                                    <td style="padding: 12px;">#<?= $booking['id'] ?></td>
                                    <td style="padding: 12px;">
                                        <?= htmlspecialchars($booking['customer_name']) ?><br>
                                        <small style="color: #666;"><?= htmlspecialchars($booking['customer_phone']) ?></small>
                                    </td>
                                    <td style="padding: 12px;"><?= htmlspecialchars($booking['service_type']) ?></td>
                                    <td style="padding: 12px;">
                                        <?= date('d M Y', strtotime($booking['date'])) ?><br>
                                        <small style="color: #666;"><?= date('H:i', strtotime($booking['time'])) ?></small>
                                    </td>
                                    <td style="padding: 12px; max-width: 150px; overflow: hidden; text-overflow: ellipsis;">
                                        <?= htmlspecialchars($booking['address']) ?>
                                    </td>
                                    <td style="padding: 12px;">
                                        <span style="background: <?= $status_color ?>; color: white; padding: 4px 12px; border-radius: 12px; font-size: 0.85rem; font-weight: 600;">
                                            <?= ucfirst($booking['status']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px; font-weight: 600;">R<?= number_format($booking['price'], 2) ?></td>
                                    <td style="padding: 12px; text-align: center;">
                                        <?php if ($booking['status'] === 'pending'): ?>
                                            <button style="background: #28a745; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; margin: 2px;">
                                                ✓ Accept
                                            </button>
                                            <button style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; margin: 2px;">
                                                ✗ Decline
                                            </button>
                                        <?php elseif ($booking['status'] === 'confirmed'): ?>
                                            <button style="background: #17a2b8; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85rem;">
                                                ✓ Mark Complete
                                            </button>
                                        <?php else: ?>
                                            <button style="background: #2c5aa0; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85rem;">
                                                View Details
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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
</body>
</html>