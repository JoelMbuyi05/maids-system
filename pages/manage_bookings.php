<?php
require_once '../backend/auth_check.php';
require_once '../backend/db_config.php';
require_once '../backend/functions.php';

// Ensure only admin can access
if ($user_role !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Handle Add New Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_booking'])) {
    $customer_id = $_POST['customer_id'];
    $cleaner_id = $_POST['cleaner_id'] ?? null; // Allow unassigned
    
    // Ensure empty string from form (if 'Unassigned' is selected) is treated as null
    $cleaner_id = ($cleaner_id === '') ? null : $cleaner_id; 
    
    $service = $_POST['service'];
    $location = $_POST['location'];
    $booking_date = $_POST['booking_date'];
    $price = $_POST['price'];

    try {
        $stmt = $pdo->prepare("INSERT INTO bookings (customer_id, cleaner_id, package, location, booking_date, price, completed) 
                             VALUES (:customer_id, :cleaner_id, :service, :location, :booking_date, :price, 0)");
        $stmt->execute([
            ':customer_id' => $customer_id,
            ':cleaner_id' => $cleaner_id,
            ':service' => $service,
            ':location' => $location,
            ':booking_date' => $booking_date,
            ':price' => $price,
        ]);
        $_SESSION['flash_message'] = "New booking created and assigned successfully!";
        $_SESSION['flash_type'] = "success";
        header('Location: manage_bookings.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Error creating booking: " . $e->getMessage();
        $_SESSION['flash_type'] = "error";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = :id");
        $stmt->execute([':id' => intval($_GET['delete'])]);
        $_SESSION['flash_message'] = "Booking deleted successfully!";
        $_SESSION['flash_type'] = "success";
        header('Location: manage_bookings.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Error deleting booking.";
        $_SESSION['flash_type'] = "error";
    }
}

// Fetch employees for filter dropdown and display/form
$employees = $pdo->query("SELECT id, name FROM employees ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch customers for the Add Booking form
$customers = $pdo->query("SELECT id, name FROM customers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Filtering
$filterCustomer = $_GET['customer_filter'] ?? '';
$filterEmployee = $_GET['employee_filter'] ?? '';
$filterService = $_GET['service_filter'] ?? '';
$filterStatus = $_GET['status_filter'] ?? ''; 

$where = [];
$params = [];

if ($filterCustomer != '') {
    $where[] = "c.name LIKE :customer";
    $params[':customer'] = "%$filterCustomer%";
}
if ($filterEmployee != '') {
    $where[] = "b.cleaner_id = :employee";
    $params[':employee'] = $filterEmployee;
}
if ($filterService != '') {
    $where[] = "b.service = :service";
    $params[':service'] = "%$filterService%";
}
if ($filterStatus !== '') {
    $statusValue = ($filterStatus === 'completed') ? 1 : (($filterStatus === 'pending') ? 0 : null);
    if ($statusValue !== null) {
        $where[] = "b.completed = :completed";
        $params[':completed'] = $statusValue;
    }
}

$whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT b.*, c.name as customer_name, e.name as employee_name 
                      FROM bookings b
                      LEFT JOIN customers c ON b.customer_id = c.id
                      LEFT JOIN employees e ON b.cleaner_id = e.id
                      $whereSQL
                      ORDER BY b.booking_date DESC, b.id DESC"); 
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - CleanCare</title>
    <link rel="stylesheet" href="../frontend/css/style.css">
    <link rel="stylesheet" href="../frontend/css/dashboard.css">
    <style>
        /* CSS Definitions (omitted for brevity, assume they are correct) */
        body { margin: 0; overflow-x: hidden; }
        .admin-layout { display: flex; min-height: 100vh; }
        
        .admin-sidebar {
            width: 260px;
            background: #1a1a1a;
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid #333;
            background: #000;
        }
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
        .sidebar-nav a.active {
            background: #2c5aa0;
            color: white;
            border-left: 4px solid #FFC107;
        }
        
        .admin-main {
            margin-left: 260px;
            flex: 1;
            background: #f8f9fa;
            min-height: 100vh;
            max-width: calc(100% - 260px);
            overflow-x: hidden;
        }
        .admin-header {
            background: white;
            padding: 20px 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .admin-content { padding: 40px 10px 40px 10px; }
        
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .form-group { display: flex; flex-direction: column; }
        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        .form-group input,
        .form-group select {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2c5aa0;
        }
        .btn-submit {
            background: #2c5aa0;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-submit:hover { background: #1e4079; }
        
        /* NEW: Add Booking Button Style */
        .btn-add {
            background: #007bff; 
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px; /* Keep margin bottom for separation */
        }
        .btn-add:hover { background: #0056b3; }
        
        .data-table-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
        .data-table td { padding: 12px; border-bottom: 1px solid #dee2e6; }
        .data-table td:last-child {
            padding-right: 5px;
            padding-left: 5px;
        }
        .data-table td:nth-child(6) {
            min-width: 100px;
            white-space: nowrap;
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

        /* ACTION BUTTON FIXES */
        .btn-action {
            background: #dc3545; 
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-action:hover { background: #c82333; }
        
        .btn-view {
            background: #2c5aa0;
            margin-right: 5px;
        }
        .btn-view:hover {
            background: #1e4079;
        }
        .action-buttons {
            white-space: nowrap; 
            display: block; 
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
                <a href="../dashboards/admin.php"><i>📊</i> Dashboard</a>
                <a href="manage_bookings.php" class="active"><i>📅</i> Bookings</a>
                <a href="manage_customers.php"><i>👥</i> Customers</a>
                <a href="manage_employees.php"><i>👷</i> Employees</a>
                <a href="manage_inventory.php"><i>📦</i> Inventory</a>
                <a href="manage_finance.php"><i>💰</i> Finance</a>
                <a href="reports.php"><i>📈</i> Reports</a>
                <a href="settings.php"><i>⚙️</i> Settings</a>
            </nav>
        </aside>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1 style="margin: 0; color: #2c5aa0;">📅 Bookings Management</h1>
            </div>
            
            <div class="admin-content">
                <?php display_flash_message(); ?>
                
                <div class="form-card" id="addBookingForm">
                    <h4 style="color: #2c5aa0; margin-bottom: 15px;">Add New Booking</h4>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Customer Name</label>
                                <select name="customer_id" required>
                                    <option value="">Select Customer</option>
                                    <?php foreach ($customers as $cust): ?>
                                            <option value="<?= $cust['id'] ?>"><?= htmlspecialchars($cust['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Employee</label>
                                <select name="cleaner_id">
                                    <option value="">Select Employee</option>
                                    <?php foreach ($employees as $emp): ?>
                                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Services</label>
                                <select name="service" required>
                                    <option value="">Select Service</option>
                                    <option value="Regular Cleaning">Regular Cleaning</option>
                                    <option value="Deep Cleaning">Deep Cleaning</option>
                                    <option value="Move In/Out Cleaning">Move In/Out Cleaning</option>
                                    <option value="Office Cleaning">Office Cleaning</option>
                                    <option value="Carpet Cleaning">Carpet Cleaning</option>
                                    <option value="Window Cleaning">Window Cleaning</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Location</label>
                                <input type="text" name="location" required>
                            </div>
                            <div class="form-group">
                                <label>Booking Date</label>
                                <input type="datetime-local" name="booking_date" required>
                            </div>
                            <div class="form-group">
                                <label>Price (R)</label>
                                <input type="number" name="price" step="0.01" min="0" required>
                            </div>
                        </div>
                        <button type="submit" name="add_booking" class="btn-submit">Create Booking</button>
                    </form>
                </div>

                <div class="form-card">
                    <h4 style="color: #2c5aa0; margin-bottom: 15px;">Filter Bookings</h4>
                    <form method="GET">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Customer</label>
                                <input type="text" name="customer_filter" value="<?= htmlspecialchars($filterCustomer) ?>">
                            </div>
                            <div class="form-group">
                                <label>Employee</label>
                                <select name="employee_filter">
                                    <option value="">All Employees</option>
                                    <?php foreach ($employees as $emp): ?>
                                        <option value="<?= $emp['id'] ?>" <?= $filterEmployee == $emp['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($emp['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Service</label>
                                <select name="service_filter">
                                    <option value="">All Services</option>
                                    <option value="Regular Cleaning" <?= $filterService=='Regular Cleaning'?'selected':'' ?>>Regular Cleaning</option>
                                    <option value="Deep Cleaning" <?= $filterService=='Deep Cleaning'?'selected':'' ?>>Deep Cleaning</option>
                                    <option value="Move In/Out Cleaning" <?= $filterService=='Move In/Out Cleaning'?'selected':'' ?>>Move In/Out Cleaning</option>
                                    <option value="Office Cleaning" <?= $filterService=='Office Cleaning'?'selected':'' ?>>Office Cleaning</option>
                                    <option value="Carpet Cleaning" <?= $filterService=='Carpet Cleaning'?'selected':'' ?>>Carpet Cleaning</option>
                                    <option value="Window Cleaning" <?= $filterService=='Window Cleaning'?'selected':'' ?>>Window Cleaning</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status_filter">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?= $filterStatus=='pending'?'selected':'' ?>>Pending</option>
                                    <option value="completed" <?= $filterStatus=='completed'?'selected':'' ?>>Completed</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn-submit">Apply Filters</button>
                        <?php if ($filterCustomer || $filterEmployee || $filterService || $filterStatus): ?>
                        <a href="manage_bookings.php" class="btn-action" style="background: #6c757d; margin-left: 10px;">Clear Filters</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="data-table-container">
                    <h3 style="color: #2c5aa0;">All Bookings (<?= count($bookings) ?>)</h3>
                    <?php if (empty($bookings)): ?>
                        <p style="text-align: center; color: #999; padding: 40px;">No bookings found matching your criteria.</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Customer</th>
                                        <th>Employee</th>
                                        <th>Service</th>
                                        <th>Location</th>
                                        <th>Date</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td><?= $booking['id'] ?></td>
                                        <td><?= htmlspecialchars($booking['customer_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($booking['employee_name'] ?? 'Unassigned') ?></td>
                                        <td><?= htmlspecialchars($booking['service_id']) ?></td>
                                        <td><?= htmlspecialchars($booking['location']) ?></td>
                                        <td><?= date('d M Y', strtotime($booking['booking_date'])) ?></td>
                                        <td>R<?= number_format($booking['price'], 2) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= $booking['completed'] ? 'completed' : 'pending' ?>">
                                                <?= $booking['completed'] ? 'Completed' : 'Pending' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons"> <a href="booking_details.php?id=<?= $booking['id'] ?>" class="btn-action btn-view">View</a>
                                                <a href="?delete=<?= $booking['id'] ?>" class="btn-action" onclick="return confirm('Are you sure you want to permanently delete booking <?= $booking['id'] ?>?')">Delete</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Auto-refresh every 30 seconds to show real-time updates
        setTimeout(function() {
            location.reload();
        }, 30000);

        // Show last update time
        document.addEventListener('DOMContentLoaded', function() {
            const updateTime = document.createElement('div');
            updateTime.style.cssText = 'position: fixed; bottom: 10px; right: 10px; background: #2c5aa0; color: white; padding: 8px 15px; border-radius: 20px; font-size: 0.85rem; z-index: 999;';
            updateTime.textContent = 'Updated: ' + new Date().toLocaleTimeString();
            document.body.appendChild(updateTime);
        });
    </script>
</body>
</html>