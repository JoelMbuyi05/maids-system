<?php
require_once '../backend/auth_check.php';
require_once '../backend/db_config.php';
require_once '../backend/functions.php';

// Ensure only admin can access
if ($user_role !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Handle mark notification as read
if (isset($_GET['mark_read']) && isset($_GET['id'])) {
    $notif_id = intval($_GET['id']);
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id");
        $stmt->execute([':id' => $notif_id]);
        echo json_encode(['success' => true]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false]);
        exit;
    }
}

// AJAX endpoint for real-time data
if (isset($_GET['ajax']) && $_GET['ajax'] === 'refresh') {
    header('Content-Type: application/json');
    
    try {
        // Get stats
        $stmt = $pdo->query("SELECT 
                            COUNT(*) as total_bookings,
                            SUM(CASE WHEN status IN ('pending', 'assigned', 'in_progress') AND completed = 0 THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN status = 'completed' OR completed = 1 THEN 1 ELSE 0 END) as completed,
                            SUM(CASE WHEN status = 'completed_by_cleaner' THEN 1 ELSE 0 END) as awaiting_review,
                            SUM(price) as total_revenue
                            FROM bookings");
        $booking_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM customers");
        $customer_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM employees");
        $employee_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM inventory");
        $inventory_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get recent bookings
        $stmt = $pdo->query("SELECT b.*, 
                            c.name as customer_name, 
                            e.name as employee_name 
                            FROM bookings b
                            LEFT JOIN customers c ON b.customer_id = c.id
                            LEFT JOIN employees e ON b.cleaner_id = e.id
                            ORDER BY b.id DESC LIMIT 10");
        $recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get notifications
        $stmt = $pdo->prepare("SELECT * FROM notifications 
                              WHERE user_role = 'admin' 
                              AND is_read = 0 
                              ORDER BY created_at DESC 
                              LIMIT 10");
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'total_bookings' => $booking_stats['total_bookings'],
                'pending' => $booking_stats['pending'],
                'completed' => $booking_stats['completed'],
                'awaiting_review' => $booking_stats['awaiting_review'],
                'revenue' => number_format($booking_stats['total_revenue'], 2),
                'customers' => $customer_count,
                'employees' => $employee_count,
                'inventory' => $inventory_count
            ],
            'bookings' => $recent_bookings,
            'notifications' => $notifications,
            'notification_count' => count($notifications),
            'timestamp' => date('H:i:s')
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Initial page load - fetch data
try {
    $stmt = $pdo->query("SELECT 
                        COUNT(*) as total_bookings,
                        SUM(CASE WHEN status IN ('pending', 'assigned', 'in_progress') AND completed = 0 THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'completed' OR completed = 1 THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN status = 'completed_by_cleaner' THEN 1 ELSE 0 END) as awaiting_review,
                        SUM(price) as total_revenue
                        FROM bookings");
    $booking_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM customers");
    $customer_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM employees");
    $employee_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inventory");
    $inventory_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT b.*, 
                        c.name as customer_name, 
                        e.name as employee_name 
                        FROM bookings b
                        LEFT JOIN customers c ON b.customer_id = c.id
                        LEFT JOIN employees e ON b.cleaner_id = e.id
                        ORDER BY b.id DESC LIMIT 10");
    $recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT * FROM notifications 
                          WHERE user_role = 'admin' 
                          AND is_read = 0 
                          ORDER BY created_at DESC 
                          LIMIT 10");
    $stmt->execute();
    $admin_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
    $booking_stats = ['total_bookings' => 0, 'pending' => 0, 'completed' => 0, 'awaiting_review' => 0, 'total_revenue' => 0];
    $recent_bookings = [];
    $customer_count = 0;
    $employee_count = 0;
    $inventory_count = 0;
    $admin_notifications = [];
}

// Service name mapping
$service_names = [
    1 => 'Regular Cleaning',
    2 => 'Deep Cleaning',
    3 => 'Move In/Out',
    4 => 'Office Cleaning',
    5 => 'Carpet Cleaning',
    6 => 'Window Cleaning'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CleanCare</title>
    <link rel="stylesheet" href="../frontend/css/style.css">
    <link rel="stylesheet" href="../frontend/css/dashboard.css">
    <style>
        body { margin: 0; overflow-x: hidden; }
        .admin-layout { display: flex; min-height: 100vh; }
        
        .admin-sidebar {
            width: 260px;
            background: #1a1a1a;
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid #333; background: #000; }
        .sidebar-logo { display: flex; align-items: center; gap: 12px; }
        .sidebar-logo svg { width: 40px; height: 40px; }
        .sidebar-logo h2 { font-size: 1.5rem; margin: 0; color: #2c5aa0; }
        .sidebar-logo p { font-size: 0.75rem; color: #999; margin: 0; }
        .sidebar-nav { padding: 20px 0; }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #aaa;
            text-decoration: none;
            transition: all 0.3s;
            gap: 12px;
        }
        .sidebar-nav a:hover { background: #2c5aa0; color: white; }
        .sidebar-nav a.active { background: #2c5aa0; color: white; border-left: 4px solid #FFC107; }
        
        .admin-main { margin-left: 260px; flex: 1; background: #f8f9fa; min-height: 100vh; }
        .admin-header {
            background: white;
            padding: 20px 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-content { padding: 40px; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-left: 4px solid #2c5aa0;
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card h3 { color: #666; font-size: 0.9rem; margin-bottom: 10px; }
        .stat-card .stat-value { font-size: 2rem; color: #2c5aa0; font-weight: 700; margin: 0; }
        
        .data-table-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-top: 30px;
        }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .data-table thead { background: #f8f9fa; border-bottom: 2px solid #dee2e6; }
        .data-table th { padding: 12px; text-align: left; font-weight: 600; }
        .data-table td { padding: 12px; border-bottom: 1px solid #dee2e6; }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
            white-space: nowrap;
        }
        .status-pending { background: #FFC107; color: #000; }
        .status-assigned { background: #007bff; }
        .status-in_progress { background: #17a2b8; }
        .status-completed_by_cleaner { background: #fd7e14; }
        .status-completed_by_customer { background: #20c997; }
        .status-completed { background: #28a745; }
        .status-cancelled { background: #dc3545; }
        
        .btn-export {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            margin-right: 10px;
        }
        .btn-export:hover { background: #218838; }
        
        .update-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #2c5aa0;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 999;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .pulse { animation: pulse 2s infinite; }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        @media (max-width: 968px) {
            .admin-sidebar { transform: translateX(-100%); }
            .admin-main { margin-left: 0; }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <svg viewBox="0 0 60 60" width="40" height="40">
                        <circle cx="30" cy="30" r="28" fill="#2c5aa0"/>
                        <path d="M 20 32 L 30 22 L 40 32 L 40 42 L 20 42 Z" fill="white"/>
                    </svg>
                    <div>
                        <h2>CleanCare</h2>
                        <p>Admin Panel</p>
                    </div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="admin.php" class="active"><i>📊</i> Dashboard</a>
                <a href="../pages/manage_bookings.php"><i>📅</i> Bookings</a>
                <a href="../pages/manage_customers.php"><i>👥</i> Customers</a>
                <a href="../pages/manage_employees.php"><i>👷</i> Employees</a>
                <a href="../pages/manage_inventory.php"><i>📦</i> Inventory</a>
                <a href="../pages/manage_finance.php"><i>💰</i> Finance</a>
                <a href="../pages/reports.php"><i>📊</i> Reports</a>
                <a href="../pages/settings.php"><i>⚙️</i> Settings</a>
            </nav>
        </aside>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1 style="margin: 0; color: #2c5aa0;">Dashboard Overview</h1>
                <div>
                    <a href="../pages/export_data.php?type=bookings" class="btn-export">📥 Export Bookings</a>
                    <a href="../backend/logout.php" style="background: #dc3545; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600;">Logout</a>
                </div>
            </div>

            <div class="admin-content">
                <?php if (!empty($admin_notifications)): ?>
                <div id="notifications-container" style="background: #fff3cd; border-left: 4px solid #FFC107; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                    <h3 style="margin-top: 0; color: #856404;">🔔 New Notifications (<span id="notif-count"><?= count($admin_notifications) ?></span>)</h3>
                    <div id="notifications-list">
                        <?php foreach ($admin_notifications as $notif): ?>
                            <div style="background: white; padding: 15px; border-radius: 6px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;" data-notif-id="<?= $notif['id'] ?>">
                                <div>
                                    <p style="margin: 0; color: #333;"><?= nl2br(htmlspecialchars($notif['message'])) ?></p>
                                    <small style="color: #999;"><?= date('M j, g:i a', strtotime($notif['created_at'])) ?></small>
                                </div>
                                <button onclick="markRead(<?= $notif['id'] ?>)" style="background: #28a745; color: white; padding: 6px 12px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.85rem;">Mark Read</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Bookings</h3>
                        <p class="stat-value" id="stat-total"><?= $booking_stats['total_bookings'] ?></p>
                    </div>
                    <div class="stat-card" style="border-left-color: #FFC107;">
                        <h3>Active/Pending</h3>
                        <p class="stat-value" style="color: #FFC107;" id="stat-pending"><?= $booking_stats['pending'] ?></p>
                    </div>
                    <div class="stat-card" style="border-left-color: #fd7e14;">
                        <h3>⏳ Awaiting Review</h3>
                        <p class="stat-value" style="color: #fd7e14;" id="stat-awaiting"><?= $booking_stats['awaiting_review'] ?></p>
                    </div>
                    <div class="stat-card" style="border-left-color: #28a745;">
                        <h3>Completed</h3>
                        <p class="stat-value" style="color: #28a745;" id="stat-completed"><?= $booking_stats['completed'] ?></p>
                    </div>
                    <div class="stat-card" style="border-left-color: #6610f2;">
                        <h3>Revenue</h3>
                        <p class="stat-value" style="color: #6610f2;" id="stat-revenue">R<?= number_format($booking_stats['total_revenue'], 2) ?></p>
                    </div>
                    <div class="stat-card" style="border-left-color: #17a2b8;">
                        <h3>Customers</h3>
                        <p class="stat-value" style="color: #17a2b8;" id="stat-customers"><?= $customer_count ?></p>
                    </div>
                    <div class="stat-card" style="border-left-color: #FFC107;">
                        <h3>Employees</h3>
                        <p class="stat-value" style="color: #FFC107;" id="stat-employees"><?= $employee_count ?></p>
                    </div>
                </div>
                
                <div class="data-table-container">
                    <h3 style="color: #2c5aa0; margin-bottom: 10px;">Recent Bookings</h3>
                    <div id="bookings-table">
                        <?php if (empty($recent_bookings)): ?>
                            <p style="text-align: center; color: #999; padding: 40px;">No bookings yet.</p>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Customer</th>
                                        <th>Service</th>
                                        <th>Date</th>
                                        <th>Cleaner</th>
                                        <th>Status</th>
                                        <th>Price</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="bookings-tbody">
                                    <?php foreach ($recent_bookings as $booking): 
                                        $status = $booking['status'] ?? ($booking['completed'] ? 'completed' : 'pending');
                                        $service_name = $service_names[$booking['service_id']] ?? $booking['service_id'];
                                        
                                        $status_labels = [
                                            'pending' => 'Pending',
                                            'assigned' => 'Assigned',
                                            'in_progress' => 'In Progress',
                                            'completed_by_cleaner' => '⏳ Needs Review',
                                            'completed_by_customer' => 'Customer OK',
                                            'completed' => 'Completed',
                                            'cancelled' => 'Cancelled'
                                        ];
                                        $status_display = $status_labels[$status] ?? ucwords($status);
                                    ?>
                                    <tr>
                                        <td>#<?= $booking['id'] ?></td>
                                        <td><?= htmlspecialchars($booking['customer_name']) ?></td>
                                        <td><?= htmlspecialchars($service_name) ?></td>
                                        <td><?= date('d M Y', strtotime($booking['booking_date'])) ?></td>
                                        <td><?= htmlspecialchars($booking['employee_name'] ?? 'Not assigned') ?></td>
                                        <td><span class="status-badge status-<?= $status ?>"><?= $status_display ?></span></td>
                                        <td>R<?= number_format($booking['price'], 2) ?></td>
                                        <td>
                                            <a href="../pages/booking_details.php?id=<?= $booking['id'] ?>" style="background: #2c5aa0; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85rem;">
                                                <?= $status === 'completed_by_cleaner' ? '⚠️ Review' : 'View' ?>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="update-indicator">
        <span class="pulse">●</span>
        <span>Last update: <span id="update-time"><?= date('H:i:s') ?></span></span>
    </div>

    <script>
        const serviceNames = <?= json_encode($service_names) ?>;
        
        const statusLabels = {
            'pending': 'Pending',
            'assigned': 'Assigned',
            'in_progress': 'In Progress',
            'completed_by_cleaner': '⏳ Needs Review',
            'completed_by_customer': 'Customer OK',
            'completed': 'Completed',
            'cancelled': 'Cancelled'
        };
        
        function markRead(notifId) {
            fetch('?mark_read=1&id=' + notifId)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector(`[data-notif-id="${notifId}"]`).remove();
                        const count = parseInt(document.getElementById('notif-count').textContent) - 1;
                        document.getElementById('notif-count').textContent = count;
                        if (count === 0) {
                            document.getElementById('notifications-container').remove();
                        }
                    }
                });
        }
        
        function refreshData() {
            fetch('?ajax=refresh')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update stats
                        document.getElementById('stat-total').textContent = data.stats.total_bookings;
                        document.getElementById('stat-pending').textContent = data.stats.pending;
                        document.getElementById('stat-awaiting').textContent = data.stats.awaiting_review;
                        document.getElementById('stat-completed').textContent = data.stats.completed;
                        document.getElementById('stat-revenue').textContent = 'R' + data.stats.revenue;
                        document.getElementById('stat-customers').textContent = data.stats.customers;
                        document.getElementById('stat-employees').textContent = data.stats.employees;
                        
                        // Update bookings table
                        if (data.bookings.length > 0) {
                            let tableHtml = '<table class="data-table"><thead><tr><th>ID</th><th>Customer</th><th>Service</th><th>Date</th><th>Cleaner</th><th>Status</th><th>Price</th><th>Actions</th></tr></thead><tbody>';
                            
                            data.bookings.forEach(booking => {
                                const status = booking.status || (booking.completed ? 'completed' : 'pending');
                                const statusDisplay = statusLabels[status] || status.charAt(0).toUpperCase() + status.slice(1);
                                const serviceName = serviceNames[booking.service_id] || booking.service_id;
                                const bookingDate = new Date(booking.booking_date);
                                const formattedDate = bookingDate.toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'});
                                const actionText = status === 'completed_by_cleaner' ? '⚠️ Review' : 'View';
                                
                                tableHtml += `
                                    <tr>
                                        <td>#${booking.id}</td>
                                        <td>${booking.customer_name}</td>
                                        <td>${serviceName}</td>
                                        <td>${formattedDate}</td>
                                        <td>${booking.employee_name || 'Not assigned'}</td>
                                        <td><span class="status-badge status-${status}">${statusDisplay}</span></td>
                                        <td>R${parseFloat(booking.price).toFixed(2)}</td>
                                        <td><a href="../pages/booking_details.php?id=${booking.id}" style="background: #2c5aa0; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85rem;">${actionText}</a></td>
                                    </tr>
                                `;
                            });
                            
                            tableHtml += '</tbody></table>';
                            document.getElementById('bookings-table').innerHTML = tableHtml;
                        }
                        
                        // Update notifications
                        if (data.notification_count > 0 && !document.getElementById('notifications-container')) {
                            location.reload(); // Reload to show new notifications
                        }
                        
                        // Update timestamp
                        document.getElementById('update-time').textContent = data.timestamp;
                    }
                })
                .catch(error => console.error('Refresh error:', error));
        }
        
        // Refresh every 5 seconds
        setInterval(refreshData, 5000);
    </script>
</body>
</html>