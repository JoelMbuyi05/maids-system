<?php
require_once '../backend/auth_check.php';
require_once '../backend/db_config.php';
require_once '../backend/functions.php';

// Ensure only admin can access
if ($user_role !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Handle user deletion (Existing logic - kept for completeness)
if (isset($_GET['delete_user']) && isset($_GET['user_id'])) {
    $user_id_to_delete = intval($_GET['user_id']);
    try {
        // Don't allow deleting yourself
        if ($user_id_to_delete != $user_id) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $user_id_to_delete]);
            $_SESSION['flash_message'] = "User deleted successfully.";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "You cannot delete your own account.";
            $_SESSION['flash_type'] = "error";
        }
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Error deleting user: " . $e->getMessage();
        $_SESSION['flash_type'] = "error";
    }
    header('Location: admin.php');
    exit;
}

// ⚠️ NEW: Handle Booking Deletion
if (isset($_GET['delete_booking']) && isset($_GET['booking_id'])) {
    $booking_id_to_delete = intval($_GET['booking_id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = :id");
        $stmt->execute([':id' => $booking_id_to_delete]);
        
        // Check if a row was actually deleted
        if ($stmt->rowCount() > 0) {
            $_SESSION['flash_message'] = "Booking #{$booking_id_to_delete} deleted successfully.";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "Booking #{$booking_id_to_delete} not found.";
            $_SESSION['flash_type'] = "error";
        }
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Error deleting booking: " . $e->getMessage();
        $_SESSION['flash_type'] = "error";
    }
    header('Location: admin.php');
    exit;
}
// END NEW Booking Deletion

// Fetch system statistics
try {
    // Total users by role
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $user_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Booking statistics from imported tables
    $stmt = $pdo->query("SELECT 
                            COUNT(*) as total_bookings,
                            SUM(CASE WHEN completed = 0 THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed,
                            SUM(price) as total_revenue
                            FROM bookings");
    $booking_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Customer count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM customers");
    $customer_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Employee count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM employees");
    $employee_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Inventory count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inventory");
    $inventory_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Recent bookings
    $stmt = $pdo->query("SELECT b.*, 
                            c.name as customer_name, 
                            e.name as employee_name 
                            FROM bookings b
                            LEFT JOIN customers c ON b.customer_id = c.id
                            LEFT JOIN employees e ON b.cleaner_id = e.id
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
    $booking_stats = ['total_bookings' => 0, 'pending' => 0, 'completed' => 0, 'total_revenue' => 0];
    $recent_bookings = [];
    $customer_count = 0;
    $employee_count = 0;
    $inventory_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CleanCare</title>
    <link rel="stylesheet" href="../frontend/css/style.css">
    <link rel="stylesheet" href="../frontend/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; overflow-x: hidden; }
        .admin-layout { display: flex; min-height: 100vh; }
        
        /* Sidebar Styles */
        .admin-sidebar {
            width: 260px;
            background: #1a1a1a;
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid #333;
            background: #000;
        }
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sidebar-logo svg {
            width: 40px;
            height: 40px;
        }
        .sidebar-logo h2 {
            font-size: 1.5rem;
            margin: 0;
            color: #2c5aa0;
        }
        .sidebar-logo p {
            font-size: 0.75rem;
            color: #999;
            margin: 0;
        }
        .sidebar-nav {
            padding: 20px 0;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #aaa;
            text-decoration: none;
            transition: all 0.3s;
            gap: 12px;
        }
        .sidebar-nav a:hover {
            background: #2c5aa0;
            color: white;
        }
        .sidebar-nav a.active {
            background: #2c5aa0;
            color: white;
            border-left: 4px solid #FFC107;
        }
        .sidebar-nav a i {
            font-size: 1.2rem;
            width: 25px;
        }
        
        /* Main Content */
        .admin-main {
            margin-left: 260px;
            flex: 1;
            background: #f8f9fa;
            min-height: 100vh;
        }

        /* UPDATED: Admin Header Styles */
        .admin-header {
            background: white;
            padding: 20px 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .header-left h1 {
            margin: 0; 
            color: #2c5aa0;
            font-size: 1.75rem;
        }
        
        /* NEW: Header Right-Side Styles */
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .header-date {
            color: #666;
            white-space: nowrap; /* Prevent date from wrapping */
        }

        .header-user-menu {
            position: relative;
        }

        .header-user-avatar {
            width: 40px;
            height: 40px;
            background: #2c5aa0;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            cursor: pointer;
        }

        .header-user-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 50px; /* Below the avatar */
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 200px;
            padding: 15px;
            z-index: 1001;
        }

        .header-user-menu:hover .header-user-dropdown {
            display: block;
        }

        .header-user-dropdown h4 {
            margin: 0 0 5px 0;
            font-size: 0.9rem;
        }
        
        .header-user-dropdown p {
            margin: 0;
            font-size: 0.75rem;
            color: #999;
        }

        .header-user-dropdown hr {
            border: 0;
            border-top: 1px solid #eee;
            margin: 10px 0;
        }

        .logout-link {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.3s;
        }

        .logout-link:hover {
            background: #c82333;
        }

        .admin-content {
            padding: 40px;
        }
        
        /* Stats Grid */
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
        }
        .stat-card h3 {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .stat-card .stat-value {
            font-size: 2rem;
            color: #2c5aa0;
            font-weight: 700;
            margin: 0;
        }
        
        /* Recent Table */
        .data-table-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-top: 30px;
            overflow-x: auto; /* NEW: Makes table scroll horizontally on mobile */
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            min-width: 800px; /* NEW: Ensures table has a min-width to trigger scroll */
        }
        .data-table thead {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        .data-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        .data-table td:nth-child(6) {
            min-width: 100px;
            white-space: nowrap;
        }
        .data-table td:nth-child(2) {
            min-width: 100px;
            white-space: nowrap;
        }
        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
        }
        .status-completed { background: #28a745; }
        .status-pending { background: #FFC107; }
        
        /* NEW: Delete Button Styling */
        .delete-button {
            background: #dc3545; 
            color: white; 
            border: none; 
            padding: 6px 12px; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 0.85rem;
        }
        
        .sidebar-toggle {
            display: none; /* Hidden on desktop */
            font-size: 1.5rem;
            background: none;
            border: none;
            color: #2c5aa0;
            cursor: pointer;
        }

        /* UPDATED: Media Queries for Responsiveness */
        @media (max-width: 968px) {
            .admin-sidebar { 
                width: 260px;
                transform: translateX(-100%);
                z-index: 1002; /* Ensure it's above the main content */
            }
            .admin-sidebar.open {
                transform: translateX(0);
            }
            .admin-main { margin-left: 0; }
            
            .sidebar-toggle {
                display: block; /* Show hamburger button */
            }
            
            .header-left h1 {
                font-size: 1.25rem; /* Make title smaller */
            }

            .header-date {
                display: none; /* Hide date on small screens to save space */
            }
            
            .admin-header {
                padding: 20px; /* Reduce header padding */
            }
        }
        
        /* NEW: Media Query for smaller phones */
        @media (max-width: 480px) {
            .header-left h1 {
                display: none; /* Hide "Dashboard Overview" on very small screens */
            }
            .admin-content {
                padding: 20px; /* Reduce padding on mobile */
            }
            .stats-grid {
                grid-template-columns: 1fr; /* Stack stat cards */
            }
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
                        <rect x="27" y="36" width="6" height="6" fill="#2c5aa0"/>
                    </svg>
                    <div>
                        <h2>CleanCare</h2>
                        <p>Admin Panel</p>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="admin.php" class="active">
                    <i>📊</i> Dashboard
                </a>
                <a href="../pages/manage_bookings.php">
                    <i>📅</i> Bookings
                </a>
                <a href="../pages/manage_customers.php">
                    <i>👥</i> Customers
                </a>
                <a href="../pages/manage_employees.php">
                    <i>👷</i> Employees
                </a>
                <a href="../pages/manage_inventory.php">
                    <i>📦</i> Inventory
                </a>
                <a href="../pages/manage_finance.php">
                    <i>💰</i> Finance
                </a>
                <a href="../pages/reports.php">
                    <i>📊</i> Reports
                </a>
                <a href="../pages/settings.php">
                    <i>⚙️</i> Settings
                </a>
            </nav>
            
            </aside>
        
        <main class="admin-main">
            
            <div class="admin-header">
              <div class="header-left">
                  <button class="sidebar-toggle" onclick="toggleSidebar()">
                      &#9776;
                  </button>
                  <h1>Dashboard Overview</h1>
              </div>

              <div class="header-right">
                  <span class="header-date"><?= date('l, F j, Y') ?></span>

                  <div class="header-user-menu">
                      <div class="header-user-avatar">
                          <?= strtoupper(substr($user_name, 0, 1)) ?>
                      </div>
                      <div class="header-user-dropdown">
                          <h4><?= htmlspecialchars($user_name) ?></h4>
                          <p>Administrator</p>
                      </div>
                  </div>

                  <!-- 🔒 LOGOUT BUTTON NOW OUTSIDE THE DROPDOWN -->
                  <a href="../backend/logout.php" class="logout-link">Logout</a>
              </div>
            </div>

            
            <div class="admin-content">
                <?php display_flash_message(); ?>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Bookings</h3>
                        <p class="stat-value"><?= $booking_stats['total_bookings'] ?></p>
                    </div>
                    <div class="stat-card" style="border-left-color: #FFC107;">
                        <h3>Pending</h3>
                        <p class="stat-value" style="color: #FFC107;"><?= $booking_stats['pending'] ?></p>
                    </div>
                    <div class="stat-card" style="border-left-color: #28a745;">
                        <h3>Completed</h3>
                        <p class="stat-value" style="color: #28a745;"><?= $booking_stats['completed'] ?></p>
                    </div>
                    <div class="stat-card" style="border-left-color: #6610f2;">
                        <h3>Revenue</h3>
                        <p class="stat-value" style="color: #6610f2;">R<?= number_format($booking_stats['total_revenue'], 2) ?></p>
                    </div>
                    <div class="stat-card" style="border-left-color: #17a2b8;">
                        <h3>Customers</h3>
                        <p class="stat-value" style="color: #17a2b8;"><?= $customer_count ?></p>
                    </div>
                    <div class="stat-card" style="border-left-color: #FFC107;">
                        <h3>Employees</h3>
                        <p class="stat-value" style="color: #FFC107;"><?= $employee_count ?></p>
                    </div>
                    <div class="stat-card" style="border-left-color: #dc3545;">
                        <h3>Inventory Items</h3>
                        <p class="stat-value" style="color: #dc3545;"><?= $inventory_count ?></p>
                    </div>
                </div>
                
                <div class="data-table-container">
                    <h3 style="color: #2c5aa0; margin-bottom: 10px;">Recent Bookings</h3>
                    
                    <?php if (empty($recent_bookings)): ?>
                        <p style="text-align: center; color: #999; padding: 40px;">No bookings yet.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Employee</th>
                                    <th>Package</th>
                                    <th>Location</th>
                                    <th>Date</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bookings as $booking): ?>
                                <tr>
                                    <td>#<?= $booking['id'] ?></td>
                                    <td><?= htmlspecialchars($booking['customer_name']) ?></td>
                                    <td><?= htmlspecialchars($booking['employee_name']) ?></td>
                                    <td><?= htmlspecialchars($booking['package']) ?></td>
                                    <td><?= htmlspecialchars($booking['location']) ?></td>
                                    <td>
                                        <?php
                                            $rawDate = $booking['booking_date'] ?? $booking['date'] ?? null;
                                            
                                            if (!empty($rawDate) && $rawDate !== '0000-00-00') {
                                                $formattedDate = date('d M Y', strtotime($rawDate));
                                            echo $formattedDate;
                                            } else {
                                                echo '<span style="color:#999;">No Date</span>';
                                            }
                                        ?>
                                    </td>
                                    
                                    <td>R<?= number_format($booking['price'], 2) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $booking['completed'] ? 'completed' : 'pending' ?>">
                                            <?= $booking['completed'] ? 'Completed' : 'Pending' ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <button onclick="deleteBooking(<?= $booking['id'] ?>)" class="delete-button">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
    // ⚠️ CHANGED: JavaScript function to handle deletion with confirmation
    function deleteBooking(bookingId) {
        if (confirm(`Are you sure you want to delete Booking #${bookingId}? This action cannot be undone.`)) {
            // Redirects to admin.php with delete_booking and booking_id parameters
            window.location.href = `admin.php?delete_booking=1&booking_id=${bookingId}`;
        }
    }

    function toggleSidebar() {
        document.querySelector('.admin-sidebar').classList.toggle('open');
    }
    </script>
</body>
</html>