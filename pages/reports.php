<?php
session_start();
require_once '../backend/db_config.php';

// Check if logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Generate statistics
try {
    // User stats
    $user_stats = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Booking stats
    $booking_stats = $pdo->query("SELECT 
                                  COUNT(*) as total,
                                  SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed,
                                  SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
                                  SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled,
                                  SUM(CASE WHEN status='completed' THEN price ELSE 0 END) as revenue
                                  FROM bookings")->fetch(PDO::FETCH_ASSOC);
    
    // Monthly revenue
    $monthly_revenue = $pdo->query("SELECT 
                                    DATE_FORMAT(date, '%Y-%m') as month,
                                    SUM(price) as revenue,
                                    COUNT(*) as bookings
                                    FROM bookings 
                                    WHERE status='completed'
                                    GROUP BY DATE_FORMAT(date, '%Y-%m')
                                    ORDER BY month DESC
                                    LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
    
    // Top cleaners
    $top_cleaners = $pdo->query("SELECT u.name, COUNT(b.id) as jobs, SUM(b.price) as earnings
                                FROM users u
                                LEFT JOIN bookings b ON u.id = b.cleaner_id AND b.status='completed'
                                WHERE u.role='cleaner'
                                GROUP BY u.id, u.name
                                ORDER BY jobs DESC
                                LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    // Service popularity
    $service_stats = $pdo->query("SELECT service_type, COUNT(*) as count, SUM(price) as revenue
                                  FROM bookings
                                  WHERE status='completed'
                                  GROUP BY service_type
                                  ORDER BY count DESC")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - CleanCare</title>
    <link rel="stylesheet" href="../frontend/css/style.css">
    <link rel="stylesheet" href="../frontend/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        @media print {
            .navbar, .no-print { display: none; }
            body { background: white; }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar no-print">
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
                        <p class="tagline">System Reports</p>
                    </div>
                </div>
            </div>
            <ul class="nav-menu">
                <li><a href="../dashboards/admin.php">Dashboard</a></li>
                <li><a href="#" class="active">Reports</a></li>
            </ul>
            <div class="nav-buttons">
                <a href="../backend/logout.php" class="btn-primary">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Reports Content -->
    <section style="padding: 60px 0; background: #f8f9fa; min-height: calc(100vh - 80px);">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;" class="no-print">
                <h2 style="color: #2c5aa0;">📊 System Reports</h2>
                <button onclick="window.print()" class="btn-primary" style="border: none; cursor: pointer;">🖨️ Print Report</button>
            </div>

            <!-- Print Header (only shows when printing) -->
            <div style="display: none; text-align: center; margin-bottom: 30px;">
                <h1 style="color: #2c5aa0; margin: 0;">CleanCare System Report</h1>
                <p style="color: #666;">Generated: <?= date('d F Y, H:i') ?></p>
            </div>

            <!-- Overview Stats -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">
                <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-left: 4px solid #2c5aa0;">
                    <h4 style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Total Users</h4>
                    <p style="font-size: 2rem; color: #2c5aa0; font-weight: 700; margin: 0;"><?= array_sum($user_stats) ?></p>
                    <small style="color: #999;">
                        Customers: <?= $user_stats['customer'] ?? 0 ?> | 
                        Cleaners: <?= $user_stats['cleaner'] ?? 0 ?> | 
                        Admins: <?= $user_stats['admin'] ?? 0 ?>
                    </small>
                </div>
                <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-left: 4px solid #2c5aa0;">
                    <h4 style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Total Bookings</h4>
                    <p style="font-size: 2rem; color: #2c5aa0; font-weight: 700; margin: 0;"><?= $booking_stats['total'] ?></p>
                    <small style="color: #999;">All time bookings</small>
                </div>
                <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-left: 4px solid #28a745;">
                    <h4 style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Completed Jobs</h4>
                    <p style="font-size: 2rem; color: #28a745; font-weight: 700; margin: 0;"><?= $booking_stats['completed'] ?></p>
                    <small style="color: #999;">Successfully finished</small>
                </div>
                <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); border-left: 4px solid #6610f2;">
                    <h4 style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Total Revenue</h4>
                    <p style="font-size: 2rem; color: #6610f2; font-weight: 700; margin: 0;">R<?= number_format($booking_stats['revenue'], 2) ?></p>
                    <small style="color: #999;">From completed jobs</small>
                </div>
            </div>

            <!-- Monthly Revenue -->
            <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 40px; page-break-inside: avoid;">
                <h3 style="color: #2c5aa0; margin-bottom: 20px;">📈 Monthly Revenue (Last 12 Months)</h3>
                <?php if (empty($monthly_revenue)): ?>
                    <p style="text-align: center; color: #999; padding: 40px;">No revenue data available yet.</p>
                <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                <th style="padding: 12px; text-align: left;">Month</th>
                                <th style="padding: 12px; text-align: right;">Bookings</th>
                                <th style="padding: 12px; text-align: right;">Revenue</th>
                                <th style="padding: 12px; text-align: right;">Avg per Booking</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_bookings = 0;
                            $total_revenue = 0;
                            foreach ($monthly_revenue as $row): 
                                $avg = $row['bookings'] > 0 ? $row['revenue'] / $row['bookings'] : 0;
                                $total_bookings += $row['bookings'];
                                $total_revenue += $row['revenue'];
                            ?>
                            <tr style="border-bottom: 1px solid #dee2e6;">
                                <td style="padding: 12px; font-weight: 600;"><?= date('F Y', strtotime($row['month'] . '-01')) ?></td>
                                <td style="padding: 12px; text-align: right;"><?= $row['bookings'] ?></td>
                                <td style="padding: 12px; text-align: right; font-weight: 600; color: #2c5aa0;">R<?= number_format($row['revenue'], 2) ?></td>
                                <td style="padding: 12px; text-align: right; color: #666;">R<?= number_format($avg, 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr style="background: #f8f9fa; font-weight: 700; border-top: 2px solid #2c5aa0;">
                                <td style="padding: 12px;">TOTAL</td>
                                <td style="padding: 12px; text-align: right;"><?= $total_bookings ?></td>
                                <td style="padding: 12px; text-align: right; color: #2c5aa0;">R<?= number_format($total_revenue, 2) ?></td>
                                <td style="padding: 12px; text-align: right; color: #666;">R<?= $total_bookings > 0 ? number_format($total_revenue / $total_bookings, 2) : '0.00' ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Service Popularity -->
            <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 40px; page-break-inside: avoid;">
                <h3 style="color: #2c5aa0; margin-bottom: 20px;">🏆 Most Popular Services</h3>
                <?php if (empty($service_stats)): ?>
                    <p style="text-align: center; color: #999; padding: 40px;">No service data available yet.</p>
                <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                <th style="padding: 12px; text-align: left;">Rank</th>
                                <th style="padding: 12px; text-align: left;">Service Type</th>
                                <th style="padding: 12px; text-align: right;">Bookings</th>
                                <th style="padding: 12px; text-align: right;">Revenue</th>
                                <th style="padding: 12px; text-align: right;">Market Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            $total_service_bookings = array_sum(array_column($service_stats, 'count'));
                            foreach ($service_stats as $service): 
                                $market_share = $total_service_bookings > 0 ? ($service['count'] / $total_service_bookings) * 100 : 0;
                            ?>
                            <tr style="border-bottom: 1px solid #dee2e6;">
                                <td style="padding: 12px; font-weight: 700; color: <?= $rank === 1 ? '#FFD700' : ($rank === 2 ? '#C0C0C0' : ($rank === 3 ? '#CD7F32' : '#666')) ?>;">
                                    <?= $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : '#' . $rank)) ?>
                                </td>
                                <td style="padding: 12px; font-weight: 600;"><?= htmlspecialchars($service['service_type']) ?></td>
                                <td style="padding: 12px; text-align: right;"><?= $service['count'] ?></td>
                                <td style="padding: 12px; text-align: right; font-weight: 600; color: #2c5aa0;">R<?= number_format($service['revenue'], 2) ?></td>
                                <td style="padding: 12px; text-align: right;">
                                    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px;">
                                        <div style="width: 100px; height: 8px; background: #e0e0e0; border-radius: 4px; overflow: hidden;">
                                            <div style="width: <?= $market_share ?>%; height: 100%; background: linear-gradient(90deg, #2c5aa0, #1e4079);"></div>
                                        </div>
                                        <span style="font-weight: 600; color: #2c5aa0;"><?= number_format($market_share, 1) ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php $rank++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Top Cleaners -->
            <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); page-break-inside: avoid;">
                <h3 style="color: #2c5aa0; margin-bottom: 20px;">⭐ Top Performing Cleaners</h3>
                <?php if (empty($top_cleaners)): ?>
                    <p style="text-align: center; color: #999; padding: 40px;">No cleaner data available yet.</p>
                <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                <th style="padding: 12px; text-align: left;">Rank</th>
                                <th style="padding: 12px; text-align: left;">Name</th>
                                <th style="padding: 12px; text-align: right;">Completed Jobs</th>
                                <th style="padding: 12px; text-align: right;">Total Earnings</th>
                                <th style="padding: 12px; text-align: right;">Avg per Job</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1; 
                            foreach ($top_cleaners as $cleaner): 
                                $avg_earning = $cleaner['jobs'] > 0 ? $cleaner['earnings'] / $cleaner['jobs'] : 0;
                            ?>
                            <tr style="border-bottom: 1px solid #dee2e6;">
                                <td style="padding: 12px; font-weight: 700; font-size: 1.2rem;">
                                    <?= $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : '#' . $rank)) ?>
                                </td>
                                <td style="padding: 12px; font-weight: 600;"><?= htmlspecialchars($cleaner['name']) ?></td>
                                <td style="padding: 12px; text-align: right;"><?= $cleaner['jobs'] ?></td>
                                <td style="padding: 12px; text-align: right; font-weight: 600; color: #28a745;">R<?= number_format($cleaner['earnings'], 2) ?></td>
                                <td style="padding: 12px; text-align: right; color: #666;">R<?= number_format($avg_earning, 2) ?></td>
                            </tr>
                            <?php $rank++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Back Button -->
            <div style="text-align: center; margin-top: 40px;" class="no-print">
                <a href="../dashboards/admin.php" style="color: #2c5aa0; text-decoration: none; font-weight: 600; font-size: 1.1rem;">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer no-print">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2025 CleanCare. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="../frontend/js/main.js"></script>
</body>
</html>