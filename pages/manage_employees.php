<?php
require_once '../backend/auth_check.php';
require_once '../backend/db_config.php';
require_once '../backend/functions.php';

// Ensure only admin can access
if ($user_role !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Handle adding an employee
if (isset($_POST['add_employee'])) {
    try {
        $name = trim($_POST['name']);
        $emp_number = trim($_POST['emp_number']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);

        // Check for duplicates
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE emp_number = :emp_number OR email = :email");
        $stmt->execute([':emp_number' => $emp_number, ':email' => $email]);
        
        if ($stmt->fetch()) {
            $_SESSION['flash_message'] = "Employee with this number or email already exists!";
            $_SESSION['flash_type'] = "error";
        } else {
            // Insert new employee
            $stmt = $pdo->prepare("INSERT INTO employees (name, emp_number, email, phone) VALUES (:name, :emp_number, :email, :phone)");
            $stmt->execute([
                ':name' => $name,
                ':emp_number' => $emp_number,
                ':email' => $email,
                ':phone' => $phone
            ]);
            
            $_SESSION['flash_message'] = "Employee added successfully!";
            $_SESSION['flash_type'] = "success";
        }
        header('Location: manage_employees.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Error adding employee: " . $e->getMessage();
        $_SESSION['flash_type'] = "error";
    }
}

// Handle updating an employee
if (isset($_POST['update_employee'])) {
    try {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $emp_number = trim($_POST['emp_number']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);

        // Check if another employee has this number/email
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE (emp_number = :emp_number OR email = :email) AND id != :id");
        $stmt->execute([':emp_number' => $emp_number, ':email' => $email, ':id' => $id]);
        
        if ($stmt->fetch()) {
            $_SESSION['flash_message'] = "Another employee with this number or email already exists!";
            $_SESSION['flash_type'] = "error";
        } else {
            // Update employee
            $stmt = $pdo->prepare("UPDATE employees SET name = :name, emp_number = :emp_number, email = :email, phone = :phone WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':emp_number' => $emp_number,
                ':email' => $email,
                ':phone' => $phone,
                ':id' => $id
            ]);
            
            $_SESSION['flash_message'] = "Employee updated successfully!";
            $_SESSION['flash_type'] = "success";
        }
        header('Location: manage_employees.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Error updating employee.";
        $_SESSION['flash_type'] = "error";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM employees WHERE id = :id");
        $stmt->execute([':id' => intval($_GET['delete'])]);
        $_SESSION['flash_message'] = "Employee deleted successfully!";
        $_SESSION['flash_type'] = "success";
        header('Location: manage_employees.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Error deleting employee.";
        $_SESSION['flash_type'] = "error";
    }
}

// Fetch employees with booking count and payments
try {
    $stmt = $pdo->query("SELECT e.*, 
                         COUNT(DISTINCT b.id) as total_bookings,
                         COALESCE(SUM(ep.amount), 0) as total_paid
                         FROM employees e
                         LEFT JOIN bookings b ON e.id = b.cleaner_id
                         LEFT JOIN employee_payments ep ON e.id = ep.employee_id
                         GROUP BY e.id
                         ORDER BY e.name ASC");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $employees = [];
    error_log("Error fetching employees: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees - CleanCare</title>
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
        
        /* Modal Styles */
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
                <a href="manage_employees.php" class="active"><i>👷</i> Employees</a>
                <a href="manage_inventory.php"><i>📦</i> Inventory</a>
                <a href="manage_finance.php"><i>💰</i> Finance</a>
                <a href="reports.php"><i>📈</i> Reports</a>
                <a href="settings.php"><i>⚙️</i> Settings</a>
            </nav>
        </aside>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1 style="margin: 0; color: #2c5aa0;">👷 Employees Management</h1>
            </div>
            
            <div class="admin-content">
                <?php display_flash_message(); ?>
                
                <!-- Add Employee Form -->
                <div class="form-card">
                    <h3 style="color: #2c5aa0; margin-bottom: 20px;">Add New Employee</h3>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" name="name" required>
                            </div>
                            <div class="form-group">
                                <label>Employee Number *</label>
                                <input type="text" name="emp_number" required placeholder="e.g., EMP001">
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
                        <button type="submit" name="add_employee" class="btn-submit">Add Employee</button>
                    </form>
                </div>
                
                <!-- Employees Table -->
                <div class="data-table-container">
                    <h3 style="color: #2c5aa0;">All Employees (<?= count($employees) ?>)</h3>
                    <?php if (empty($employees)): ?>
                        <p style="text-align: center; color: #999; padding: 40px;">No employees yet.</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Emp Number</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Bookings</th>
                                        <th>Total Paid</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employees as $employee): ?>
                                    <tr>
                                        <td>#<?= $employee['id'] ?></td>
                                        <td><strong><?= htmlspecialchars($employee['name']) ?></strong></td>
                                        <td><?= htmlspecialchars($employee['emp_number']) ?></td>
                                        <td><?= htmlspecialchars($employee['email']) ?></td>
                                        <td><?= htmlspecialchars($employee['phone']) ?></td>
                                        <td>
                                            <span class="stat-badge"><?= $employee['total_bookings'] ?> jobs</span>
                                        </td>
                                        <td style="font-weight: 600; color: #2c5aa0;">
                                            R<?= number_format($employee['total_paid'], 2) ?>
                                        </td>
                                        <td>
                                            <button class="btn-action btn-edit" onclick="openEditModal(<?= htmlspecialchars(json_encode($employee)) ?>)">Edit</button>
                                            <a href="?delete=<?= $employee['id'] ?>" 
                                               class="btn-action btn-delete" 
                                               onclick="return confirm('Delete this employee?')">Delete</a>
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

    <!-- Edit Employee Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Edit Employee</h3>
                <button class="close-modal" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Full Name *</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Employee Number *</label>
                    <input type="text" name="emp_number" id="edit_emp_number" required>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Email Address *</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Phone Number *</label>
                    <input type="tel" name="phone" id="edit_phone" required>
                </div>
                <button type="submit" name="update_employee" class="btn-submit">Update Employee</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(employee) {
            document.getElementById('edit_id').value = employee.id;
            document.getElementById('edit_name').value = employee.name;
            document.getElementById('edit_emp_number').value = employee.emp_number;
            document.getElementById('edit_email').value = employee.email;
            document.getElementById('edit_phone').value = employee.phone;
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>