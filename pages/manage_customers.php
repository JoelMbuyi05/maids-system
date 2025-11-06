<?php
require_once '../backend/auth_check.php';
require_once '../backend/db_config.php';
require_once '../backend/functions.php';

// Ensure only admin can access
if ($user_role !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Handle adding a customer
if (isset($_POST['add_customer'])) {
    try {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = :email");
        $stmt->execute([':email' => $email]);
        
        if ($stmt->fetch()) {
            $_SESSION['flash_message'] = "Customer with this email already exists!";
            $_SESSION['flash_type'] = "error";
        } else {
            // Insert new customer
            $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, address) VALUES (:name, :email, :phone, :address)");
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':address' => $address
            ]);
            
            $_SESSION['flash_message'] = "Customer added successfully!";
            $_SESSION['flash_type'] = "success";
        }
        header('Location: manage_customers.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Error adding customer: " . $e->getMessage();
        $_SESSION['flash_type'] = "error";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = :id");
        $stmt->execute([':id' => intval($_GET['delete'])]);
        $_SESSION['flash_message'] = "Customer deleted successfully!";
        $_SESSION['flash_type'] = "success";
        header('Location: manage_customers.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Error deleting customer.";
        $_SESSION['flash_type'] = "error";
    }
}

// Fetch customers with booking count
try {
    $stmt = $pdo->query("SELECT c.*, 
                         COUNT(b.id) as total_bookings,
                         SUM(CASE WHEN b.completed = 1 THEN b.price ELSE 0 END) as total_spent
                         FROM customers c
                         LEFT JOIN bookings b ON c.id = b.customer_id
                         GROUP BY c.id
                         ORDER BY c.name ASC");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $customers = [];
    error_log("Error fetching customers: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers - CleanCare</title>
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
        }
        .admin-header {
            background: white;
            padding: 20px 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .admin-content { padding: 40px; }
        
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
        .form-group textarea {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        .form-group input:focus,
        .form-group textarea:focus {
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
        .data-table th { padding: 12px; text-align: left; font-weight: 600; }
        .data-table td { padding: 12px; border-bottom: 1px solid #dee2e6; }
        .data-table td:nth-child(6) {
            min-width: 100px;
            white-space: nowrap;
        }
        .btn-action {
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-block;
            margin-right: 5px;
        }
        .btn-edit { background: #28a745; }
        .btn-edit:hover { background: #218838; }
        .btn-delete { background: #dc3545; }
        .btn-delete:hover { background: #c82333; }
        .stat-badge {
            background: #e3f2fd;
            color: #2c5aa0;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
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
                <a href="manage_bookings.php"><i>📅</i> Bookings</a>
                <a href="manage_customers.php" class="active"><i>👥</i> Customers</a>
                <a href="manage_employees.php"><i>👷</i> Employees</a>
                <a href="manage_inventory.php"><i>📦</i> Inventory</a>
                <a href="manage_finance.php"><i>💰</i> Finance</a>
                <a href="reports.php"><i>📈</i> Reports</a>
                <a href="settings.php"><i>⚙️</i> Settings</a>
            </nav>
        </aside>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1 style="margin: 0; color: #2c5aa0;">👥 Customers Management</h1>
            </div>
            
            <div class="admin-content">
                <?php display_flash_message(); ?>
                
                <!-- Add Customer Form -->
                <div class="form-card">
                    <h3 style="color: #2c5aa0; margin-bottom: 20px;">Add New Customer</h3>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" name="name" required>
                            </div>
                            <div class="form-group">
                                <label>Email Address *</label>
                                <input type="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label>Phone Number *</label>
                                <input type="tel" name="phone" required placeholder="+27 123 456 7890">
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label>Address</label>
                            <textarea name="address" rows="2" placeholder="Full address (optional)"></textarea>
                        </div>
                        <button type="submit" name="add_customer" class="btn-submit">Add Customer</button>
                    </form>
                </div>
                
                <!-- Customers Table -->
                <div class="data-table-container">
                    <h3 style="color: #2c5aa0;">All Customers (<?= count($customers) ?>)</h3>
                    <?php if (empty($customers)): ?>
                        <p style="text-align: center; color: #999; padding: 40px;">No customers yet.</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Address</th>
                                        <th>Bookings</th>
                                        <th>Total Spent</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($customers as $customer): ?>
                                    <tr>
                                        <td>#<?= $customer['id'] ?></td>
                                        <td><strong><?= htmlspecialchars($customer['name']) ?></strong></td>
                                        <td><?= htmlspecialchars($customer['email']) ?></td>
                                        <td><?= htmlspecialchars($customer['phone'] ?: 'N/A') ?></td>
                                        <td><?= htmlspecialchars($customer['address'] ?: 'N/A') ?></td>
                                        <td>
                                            <span class="stat-badge"><?= $customer['total_bookings'] ?> bookings</span>
                                        </td>
                                        <td style="font-weight: 600; color: #2c5aa0;">
                                            R<?= number_format($customer['total_spent'], 2) ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit_customer.php?id=<?= $customer['id'] ?>" class="btn-action btn-edit">Edit</a>
                                                <a href="?delete=<?= $customer['id'] ?>" 
                                                class="btn-action btn-delete" 
                                                onclick="return confirm('Delete this customer? This will also delete their bookings.')">Delete</a>
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
</body>
</html>