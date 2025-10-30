<?php
require_once '../backend/auth_check.php';
require_once '../backend/db_config.php';
require_once '../backend/functions.php';

// Ensure only admin can access
if ($user_role !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Fetch system statistics
try {
    // Total users by role
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $user_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Booking statistics
    $stmt = $pdo->query("SELECT 
                         COUNT(*) as total_bookings,
                         SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                         SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                         SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                         SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                         SUM(CASE WHEN status = 'completed' THEN price ELSE 0 END) as total_revenue
                         FROM bookings");
    $booking_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Recent bookings
    $stmt = $pdo->query("SELECT b.*, 
                         c.name as customer_name, 
                         cl.name as cleaner_name 
                         FROM bookings b
                         LEFT JOIN users c ON b.customer_id = c.id
                         LEFT JOIN users cl ON b.cleaner_id = cl.id
                         ORDER BY b.id DESC LIMIT 10");
    $recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent users
    $stmt = $pdo->query("SELECT id, name, email, role, created_at 
                         FROM users 
                         ORDER BY created_at DESC LIMIT 10");
    $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
    $user_stats = [];
    $booking_stats = ['total_bookings' => 0, 'pending' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0, 'total_revenue' => 0];
    $recent_bookings = [];
    $recent_users = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CleanCare</title>
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
                        <p class="tagline">Admin Panel</p>
                    </div>
                </div>
            </div>
            <ul class="nav-menu">
                <li><a href="../index.php">Home</a></li>
                <li><a href="#" class="active">Dashboard</a></li>
                <li><a href="#users">Users</a></li>
                <li><a href="#bookings">Bookings</a></li>
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
            
            <h2 style="color: #2c5aa0; margin-bottom: 30px;">Admin Dashboard - System Overview</h2>
            
            <!-- User Statistics -->
            <h3 style="color: #2c5aa0; margin-bottom: 20px; margin-top: 40px;">User Statistics</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">
                <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-left: 4px solid #2c5aa0;">
                    <h3 style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Total Users</h3>
                    <p style="font-size: 2rem; color: #2c5aa0; font-weight: 700; margin: 0;">
                        <?= array_sum($user_stats) ?>
                    </p>
                </div>
                <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-left: 4px solid #28a745;">
                    <h3 style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Customers</h3>
                    <p style="font-size: 2rem; color: #28a745; font-weight: 700; margin: 0;">
                        <?= $user_stats['customer'] ?? 0 ?>
                    </p>
                </div>
                <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-left: 4px solid #FFC107;">
                    <h3 style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Cleaners</h3>
                    <p style="font-size: 2rem; color: #FFC107; font-weight: 700; margin: 0;">
                        <?= $user_stats['cleaner'] ?? 0 ?>
                    </p>
                </div>
                <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-left: 4px solid #dc3545;">
                    <h3 style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Admins</h3>
                    <p style="font-size: 2rem; color: #dc3545; font-weight: 700; margin: 0;">
                        <?= $user_stats['admin'] ?? 0 ?>
                    </p>
                </div>
            </div>

            <!-- Booking Statistics -->
            <h3 style="color: #2c5aa0; margin-bottom: 20px;">Booking Statistics</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">
                <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-left: 4px solid #2c5aa0;">
                    <h3 style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Total Bookings</h3>
                    <p style="font-size: 2rem; color: #2c5aa0; font-weight: 700; margin: 0;"><?= $booking_stats['total_bookings'] ?></p>
                </div>
                <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-left: 4px solid #FFC107;">
                    <h3 style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Pending</h3>
                    <p style="font-size: 2rem; color: #FFC107; font-weight: 700; margin: 0;"><?= $booking_stats['pending'] ?></p>
                </div>
                <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-left: 4px solid #28a745;">
                    <h3 style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Confirmed</h3>
                    <p style="font-size: 2rem; color: #28a745; font-weight: 700; margin: 0;"><?= $booking_stats['confirmed'] ?></p>
                </div>
                <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-left: 4px solid #17a2b8;">
                    <h3 style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Completed</h3>
                    <p style="font-size: 2rem; color: #17a2b8; font-weight: 700; margin: 0;"><?= $booking_stats['completed'] ?></p>
                </div>
                <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-left: 4px solid #dc3545;">
                    <h3 style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Cancelled</h3>
                    <p style="font-size: 2rem; color: #dc3545; font-weight: 700; margin: 0;"><?= $booking_stats['cancelled'] ?></p>
                </div>
                <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-left: 4px solid #6610f2;">
                    <h3 style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Total Revenue</h3>
                    <p style="font-size: 2rem; color: #6610f2; font-weight: 700; margin: 0;">R<?= number_format($booking_stats['total_revenue'], 2) ?></p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 40px;">
                <h3 style="color: #2c5aa0; margin-bottom: 20px;">Admin Actions</h3>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <a href="#add-user" class="btn-primary" style="display: inline-block;">➕ Add New User</a>
                    <a href="#manage-bookings" class="btn-outline">📋 Manage Bookings</a>
                    <a href="#reports" class="btn-outline">📊 Generate Reports</a>
                    <a href="#settings" class="btn-outline">⚙️ System Settings</a>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div id="bookings" style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 40px;">
                <h3 style="color: #2c5aa0; margin-bottom: 20px;">Recent Bookings</h3>
                
                <?php if (empty($recent_bookings)): ?>
                    <p style="text-align: center; color: #999; padding: 20px;">No bookings yet.</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                    <th style="padding: 12px; text-align: left;">ID</th>
                                    <th style="padding: 12px; text-align: left;">Customer</th>
                                    <th style="padding: 12px; text-align: left;">Cleaner</th>
                                    <th style="padding: 12px; text-align: left;">Service</th>
                                    <th style="padding: 12px; text-align: left;">Date</th>
                                    <th style="padding: 12px; text-align: left;">Status</th>
                                    <th style="padding: 12px; text-align: left;">Price</th>
                                    <th style="padding: 12px; text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bookings as $booking): 
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
                                    <td style="padding: 12px;"><?= htmlspecialchars($booking['customer_name']) ?></td>
                                    <td style="padding: 12px;"><?= htmlspecialchars($booking['cleaner_name'] ?? 'Not assigned') ?></td>
                                    <td style="padding: 12px;"><?= htmlspecialchars($booking['service_type']) ?></td>
                                    <td style="padding: 12px;"><?= date('d M Y', strtotime($booking['date'])) ?></td>
                                    <td style="padding: 12px;">
                                        <span style="background: <?= $status_color ?>; color: white; padding: 4px 12px; border-radius: 12px; font-size: 0.85rem; font-weight: 600;">
                                            <?= ucfirst($booking['status']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px; font-weight: 600;">R<?= number_format($booking['price'], 2) ?></td>
                                    <td style="padding: 12px; text-align: center;">
                                        <button style="background: #2c5aa0; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85rem;">
                                            Manage
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Users -->
            <div id="users" style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                <h3 style="color: #2c5aa0; margin-bottom: 20px;">Recent Users</h3>
                
                <?php if (empty($recent_users)): ?>
                    <p style="text-align: center; color: #999; padding: 20px;">No users yet.</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                    <th style="padding: 12px; text-align: left;">ID</th>
                                    <th style="padding: 12px; text-align: left;">Name</th>
                                    <th style="padding: 12px; text-align: left;">Email</th>
                                    <th style="padding: 12px; text-align: left;">Role</th>
                                    <th style="padding: 12px; text-align: left;">Joined</th>
                                    <th style="padding: 12px; text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_users as $user): 
                                    $role_colors = [
                                        'admin' => '#dc3545',
                                        'cleaner' => '#FFC107',
                                        'customer' => '#28a745'
                                    ];
                                    $role_color = $role_colors[$user['role']] ?? '#666';
                                ?>
                                <tr style="border-bottom: 1px solid #dee2e6;">
                                    <td style="padding: 12px;">#<?= $user['id'] ?></td>
                                    <td style="padding: 12px;"><?= htmlspecialchars($user['name']) ?></td>
                                    <td style="padding: 12px;"><?= htmlspecialchars($user['email']) ?></td>
                                    <td style="padding: 12px;">
                                        <span style="background: <?= $role_color ?>; color: white; padding: 4px 12px; border-radius: 12px; font-size: 0.85rem; font-weight: 600;">
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px;"><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                                    <td style="padding: 12px; text-align: center;">
                                        <button style="background: #2c5aa0; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; margin: 2px;">
                                            Edit
                                        </button>
                                        <button style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; margin: 2px;">
                                            Delete
                                        </button>
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