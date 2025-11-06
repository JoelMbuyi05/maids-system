<?php
require_once '../backend/auth_check.php';
require_once '../backend/db_config.php';
require_once '../backend/functions.php';

// Ensure only admin can access
if ($user_role !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Handle adding inventory item
if (isset($_POST['add_item'])) {
    try {
        $item_name = trim($_POST['item_name']);
        $quantity = intval($_POST['quantity']);
        
        $stmt = $pdo->prepare("INSERT INTO inventory (item_name, quantity) VALUES (:item_name, :quantity)");
        $stmt->execute([':item_name' => $item_name, ':quantity' => $quantity]);
        
        $_SESSION['flash_message'] = "Inventory item added successfully!";
        $_SESSION['flash_type'] = "success";
        header('Location: manage_inventory.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Error adding item.";
        $_SESSION['flash_type'] = "error";
    }
}

// Handle updating inventory item
if (isset($_POST['update_item'])) {
    try {
        $id = intval($_POST['id']);
        $item_name = trim($_POST['item_name']);
        $quantity = intval($_POST['quantity']);
        
        $stmt = $pdo->prepare("UPDATE inventory SET item_name = :item_name, quantity = :quantity WHERE id = :id");
        $stmt->execute([':item_name' => $item_name, ':quantity' => $quantity, ':id' => $id]);
        
        $_SESSION['flash_message'] = "Inventory item updated successfully!";
        $_SESSION['flash_type'] = "success";
        header('Location: manage_inventory.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Error updating item.";
        $_SESSION['flash_type'] = "error";
    }
}

// Handle purchase from low stock
if (isset($_POST['purchase'])) {
    try {
        $item_id = intval($_POST['item_id']);
        $purchase_qty = intval($_POST['purchase_qty']);
        $needed_by = $_POST['needed_by'];
        
        // Update inventory quantity
        $stmt = $pdo->prepare("UPDATE inventory SET quantity = quantity + :qty WHERE id = :id");
        $stmt->execute([':qty' => $purchase_qty, ':id' => $item_id]);
        
        // Log purchase
        $stmt = $pdo->prepare("INSERT INTO purchase_history (inventory_id, quantity, needed_by) VALUES (:item_id, :qty, :needed_by)");
        $stmt->execute([':item_id' => $item_id, ':qty' => $purchase_qty, ':needed_by' => $needed_by]);
        
        $_SESSION['flash_message'] = "Purchase order placed successfully!";
        $_SESSION['flash_type'] = "success";
        header('Location: manage_inventory.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Error processing purchase.";
        $_SESSION['flash_type'] = "error";
    }
}

// Fetch inventory items
$stmt = $pdo->query("SELECT * FROM inventory ORDER BY item_name ASC");
$inventory_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch low stock items (quantity <= 5)
$stmt = $pdo->query("SELECT * FROM inventory WHERE quantity <= 5 ORDER BY quantity ASC");
$low_stock_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch inventory logs with filter
$log_filter = "";
$log_params = [];
if (!empty($_GET['log_start']) && !empty($_GET['log_end'])) {
    $log_filter = "WHERE l.created_at BETWEEN :start AND :end";
    $log_params = [':start' => $_GET['log_start'] . ' 00:00:00', ':end' => $_GET['log_end'] . ' 23:59:59'];
}
$stmt = $pdo->prepare("SELECT l.*, i.item_name FROM inventory_log l 
                       LEFT JOIN inventory i ON l.inventory_id = i.id 
                       $log_filter 
                       ORDER BY l.created_at DESC LIMIT 50");
$stmt->execute($log_params);
$inventory_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch purchase history with filter
$purchase_filter = "";
$purchase_params = [];
if (!empty($_GET['purchase_start']) && !empty($_GET['purchase_end'])) {
    $purchase_filter = "WHERE p.purchased_at BETWEEN :start AND :end";
    $purchase_params = [':start' => $_GET['purchase_start'] . ' 00:00:00', ':end' => $_GET['purchase_end'] . ' 23:59:59'];
}
$stmt = $pdo->prepare("SELECT p.*, i.item_name FROM purchase_history p 
                       LEFT JOIN inventory i ON p.inventory_id = i.id 
                       $purchase_filter 
                       ORDER BY p.purchased_at DESC LIMIT 50");
$stmt->execute($purchase_params);
$purchase_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inventory - CleanCare</title>
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
        
        .form-card, .alert-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        .alert-card {
            background: #fff3cd;
            border-left: 4px solid #FFC107;
        }
        .low-stock-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 3px solid #dc3545;
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
        .form-group input {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        .form-group input:focus {
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
            margin-bottom: 30px;
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
        .btn-action {
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            background: #28a745;
        }
        .btn-action:hover { background: #218838; }
        .qty-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .qty-low { background: #fff3cd; color: #856404; }
        .qty-ok { background: #d4edda; color: #155724; }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        .modal.active { display: flex; align-items: center; justify-content: center; }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-header h3 { margin: 0; color: #2c5aa0; }
        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
        }
        .close-modal:hover { color: #333; }
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
                <a href="manage_customers.php"><i>👥</i> Customers</a>
                <a href="manage_employees.php"><i>👷</i> Employees</a>
                <a href="manage_inventory.php" class="active"><i>📦</i> Inventory</a>
                <a href="manage_finance.php"><i>💰</i> Finance</a>
                <a href="reports.php"><i>📈</i> Reports</a>
                <a href="settings.php"><i>⚙️</i> Settings</a>
            </nav>
        </aside>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1 style="margin: 0; color: #2c5aa0;">📦 Inventory Management</h1>
            </div>
            
            <div class="admin-content">
                <?php display_flash_message(); ?>
                
                <!-- Low Stock Alert -->
                <?php if (!empty($low_stock_items)): ?>
                <div class="alert-card">
                    <h3 style="color: #856404; margin: 0 0 20px 0;">⚠️ Low Stock Alert (<?= count($low_stock_items) ?> items)</h3>
                    <?php foreach ($low_stock_items as $item): ?>
                    <div class="low-stock-item">
                        <strong style="color: #dc3545;"><?= htmlspecialchars($item['item_name']) ?></strong>
                        <span style="color: #666;"> - Only <?= $item['quantity'] ?> left in stock</span>
                        <form method="POST" style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;">
                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                            <input type="number" name="purchase_qty" placeholder="Qty" required style="width: 100px; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                            <input type="date" name="needed_by" required style="padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                            <button type="submit" name="purchase" class="btn-submit" style="padding: 8px 20px;">Purchase</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Add Item Form -->
                <div class="form-card">
                    <h3 style="color: #2c5aa0; margin-bottom: 20px;">Add New Inventory Item</h3>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Item Name *</label>
                                <input type="text" name="item_name" required placeholder="e.g., Disinfectant Spray">
                            </div>
                            <div class="form-group">
                                <label>Quantity *</label>
                                <input type="number" name="quantity" required min="0" placeholder="0">
                            </div>
                        </div>
                        <button type="submit" name="add_item" class="btn-submit">Add Item</button>
                    </form>
                </div>
                
                <!-- Inventory List -->
                <div class="data-table-container">
                    <h3 style="color: #2c5aa0;">All Inventory Items (<?= count($inventory_items) ?>)</h3>
                    <?php if (empty($inventory_items)): ?>
                        <p style="text-align: center; color: #999; padding: 40px;">No inventory items yet.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Item Name</th>
                                    <th>Quantity</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inventory_items as $item): ?>
                                <tr>
                                    <td>#<?= $item['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($item['item_name']) ?></strong></td>
                                    <td style="font-size: 1.2rem; font-weight: 600;"><?= $item['quantity'] ?></td>
                                    <td>
                                        <span class="qty-badge <?= $item['quantity'] <= 5 ? 'qty-low' : 'qty-ok' ?>">
                                            <?= $item['quantity'] <= 5 ? 'Low Stock' : 'In Stock' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-action" onclick="openEditModal(<?= htmlspecialchars(json_encode($item)) ?>)">Edit</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <!-- Inventory Logs -->
                <div class="data-table-container">
                    <h3 style="color: #2c5aa0;">Inventory Usage Logs</h3>
                    <form method="GET" style="display: flex; gap: 10px; margin: 15px 0; flex-wrap: wrap;">
                        <input type="date" name="log_start" value="<?= $_GET['log_start'] ?? '' ?>" style="padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <input type="date" name="log_end" value="<?= $_GET['log_end'] ?? '' ?>" style="padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <button type="submit" class="btn-submit" style="padding: 10px 20px;">Filter</button>
                        <a href="manage_inventory.php" style="padding: 10px 20px; background: #6c757d; color: white; border-radius: 6px; text-decoration: none;">Clear</a>
                    </form>
                    <?php if (empty($inventory_logs)): ?>
                        <p style="text-align: center; color: #999; padding: 40px;">No usage logs yet.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Qty Used</th>
                                    <th>Reason</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inventory_logs as $log): ?>
                                <tr>
                                    <td><?= htmlspecialchars($log['item_name']) ?></td>
                                    <td><?= $log['quantity_used'] ?></td>
                                    <td><?= htmlspecialchars($log['reason']) ?></td>
                                    <td><?= date('d M Y H:i', strtotime($log['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <!-- Purchase History -->
                <div class="data-table-container">
                    <h3 style="color: #2c5aa0;">Purchase History</h3>
                    <form method="GET" style="display: flex; gap: 10px; margin: 15px 0; flex-wrap: wrap;">
                        <input type="date" name="purchase_start" value="<?= $_GET['purchase_start'] ?? '' ?>" style="padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <input type="date" name="purchase_end" value="<?= $_GET['purchase_end'] ?? '' ?>" style="padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <button type="submit" class="btn-submit" style="padding: 10px 20px;">Filter</button>
                        <a href="manage_inventory.php" style="padding: 10px 20px; background: #6c757d; color: white; border-radius: 6px; text-decoration: none;">Clear</a>
                    </form>
                    <?php if (empty($purchase_history)): ?>
                        <p style="text-align: center; color: #999; padding: 40px;">No purchase history yet.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Qty Purchased</th>
                                    <th>Needed By</th>
                                    <th>Purchase Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($purchase_history as $purchase): ?>
                                <tr>
                                    <td><?= htmlspecialchars($purchase['item_name']) ?></td>
                                    <td><?= $purchase['quantity'] ?></td>
                                    <td><?= date('d M Y', strtotime($purchase['needed_by'])) ?></td>
                                    <td><?= date('d M Y H:i', strtotime($purchase['purchased_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Edit Inventory Item</h3>
                <button class="close-modal" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Item Name *</label>
                    <input type="text" name="item_name" id="edit_item_name" required>
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Quantity *</label>
                    <input type="number" name="quantity" id="edit_quantity" required min="0">
                </div>
                <button type="submit" name="update_item" class="btn-submit">Update Item</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(item) {
            document.getElementById('edit_id').value = item.id;
            document.getElementById('edit_item_name').value = item.item_name;
            document.getElementById('edit_quantity').value = item.quantity;
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });
    </script>
</body>
</html>