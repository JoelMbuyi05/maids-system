<?php
require_once '../backend/auth_check.php';
require_once '../backend/db_config.php';
require_once '../backend/functions.php';

if ($user_role !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// AJAX endpoint for real-time data
if (isset($_GET['ajax']) && $_GET['ajax'] === 'refresh') {
    header('Content-Type: application/json');
    
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    $date_filter = "";
    $params = [];
    
    if ($start_date && $end_date) {
        $date_filter = "WHERE booking_date BETWEEN :start AND :end";
        $params = [':start' => $start_date, ':end' => $end_date];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(price), 0) as total FROM bookings $date_filter");
        $stmt->execute($params);
        $total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        $payment_filter = $date_filter ? str_replace('booking_date', 'pay_date', $date_filter) : "";
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM employee_payments $payment_filter");
        $stmt->execute($params);
        $total_employee_paid = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        $expense_filter = $date_filter ? str_replace('booking_date', 'expense_date', $date_filter) : "";
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses $expense_filter");
        $stmt->execute($params);
        $total_expenses = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        $profit = $total_revenue - ($total_employee_paid + $total_expenses);
        
        $stmt = $pdo->query("SELECT * FROM employee_payments ORDER BY pay_date DESC LIMIT 20");
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'revenue' => number_format($total_revenue, 2),
            'employee_paid' => number_format($total_employee_paid, 2),
            'expenses' => number_format($total_expenses, 2),
            'profit' => number_format($profit, 2),
            'payments' => $payments,
            'timestamp' => date('H:i:s')
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Handle employee payment
if (isset($_POST['pay_employee'])) {
    try {
        $employee_id = intval($_POST['employee_id']);
        $amount = floatval($_POST['amount']);
        $pay_date = date('Y-m-d');
        
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = :id");
        $stmt->execute([':id' => $employee_id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($employee) {
            $stmt = $pdo->prepare("INSERT INTO employee_payments (employee_id, employee_name, employee_email, amount, pay_date, date) 
                                   VALUES (:emp_id, :emp_name, :emp_email, :amount, :pay_date, :pay_date)");
            $stmt->execute([
                ':emp_id' => $employee_id,
                ':emp_name' => $employee['name'],
                ':emp_email' => $employee['email'],
                ':amount' => $amount,
                ':pay_date' => $pay_date
            ]);
            
            $_SESSION['flash_message'] = "Payment of R" . number_format($amount, 2) . " recorded for " . $employee['name'];
            $_SESSION['flash_type'] = "success";
        }
        header('Location: manage_finance.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Error processing payment.";
        $_SESSION['flash_type'] = "error";
    }
}

// Check if tables exist, create if needed
try {
    $pdo->query("SELECT 1 FROM employee_payments LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS employee_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        employee_name VARCHAR(255) NOT NULL,
        employee_email VARCHAR(255) NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        pay_date DATE NOT NULL,
        date DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

try {
    $pdo->query("SELECT 1 FROM expenses LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        description VARCHAR(255) NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        expense_date DATE NOT NULL,
        category VARCHAR(100) DEFAULT 'Other',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

// Initial load
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$date_filter = "";
$params = [];

if ($start_date && $end_date) {
    $date_filter = "WHERE booking_date BETWEEN :start AND :end";
    $params = [':start' => $start_date, ':end' => $end_date];
}

$stmt = $pdo->prepare("SELECT COALESCE(SUM(price), 0) as total FROM bookings $date_filter");
$stmt->execute($params);
$total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$payment_filter = $date_filter ? str_replace('booking_date', 'pay_date', $date_filter) : "";
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM employee_payments $payment_filter");
$stmt->execute($params);
$total_employee_paid = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$expense_filter = $date_filter ? str_replace('booking_date', 'expense_date', $date_filter) : "";
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses $expense_filter");
$stmt->execute($params);
$total_expenses = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$profit = $total_revenue - ($total_employee_paid + $total_expenses);

$stmt = $pdo->query("SELECT * FROM employee_payments ORDER BY pay_date DESC LIMIT 20");
$employee_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT id, name FROM employees ORDER BY name");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT DATE_FORMAT(booking_date, '%Y-%m') as month, COALESCE(SUM(price), 0) as total 
                     FROM bookings 
                     WHERE booking_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                     GROUP BY month 
                     ORDER BY month");
$monthly_revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Management - CleanCare</title>
    <link rel="stylesheet" href="../frontend/css/style.css">
    <link rel="stylesheet" href="../frontend/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { margin: 0; overflow-x: hidden; }
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 260px; background: #1a1a1a; color: white; position: fixed; height: 100vh; overflow-y: auto; }
        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid #333; background: #000; }
        .sidebar-logo { display: flex; align-items: center; gap: 12px; }
        .sidebar-logo svg { width: 40px; height: 40px; }
        .sidebar-logo h2 { font-size: 1.5rem; margin: 0; color: #2c5aa0; }
        .sidebar-logo p { font-size: 0.75rem; color: #999; margin: 0; }
        .sidebar-nav { padding: 20px 0; }
        .sidebar-nav a { display: flex; align-items: center; padding: 12px 20px; color: #aaa; text-decoration: none; transition: all 0.3s; gap: 12px; }
        .sidebar-nav a:hover { background: #2c5aa0; color: white; }
        .sidebar-nav a.active { background: #2c5aa0; color: white; border-left: 4px solid #FFC107; }
        .admin-main { margin-left: 260px; flex: 1; background: #f8f9fa; min-height: 100vh; }
        .admin-header { background: white; padding: 20px 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); display: flex; justify-content: space-between; align-items: center; }
        .admin-content { padding: 40px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card h3 { color: #666; font-size: 0.9rem; margin: 0 0 10px 0; }
        .stat-card .value { font-size: 2rem; font-weight: 700; margin: 0; }
        .stat-card.revenue { border-left: 4px solid #28a745; }
        .stat-card.revenue .value { color: #28a745; }
        .stat-card.expenses { border-left: 4px solid #dc3545; }
        .stat-card.expenses .value { color: #dc3545; }
        .stat-card.profit { border-left: 4px solid #2c5aa0; }
        .stat-card.profit .value { color: #2c5aa0; }
        .form-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; margin-bottom: 8px; color: #333; }
        .form-group input, .form-group select { padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; }
        .btn-submit { background: #2c5aa0; color: white; padding: 12px 30px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .btn-export { background: #28a745; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; display: inline-block; }
        .data-table-container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .data-table thead { background: #f8f9fa; border-bottom: 2px solid #dee2e6; }
        .data-table th { padding: 12px; text-align: left; font-weight: 600; }
        .data-table td { padding: 12px; border-bottom: 1px solid #dee2e6; }
        .chart-container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .update-indicator { position: fixed; bottom: 20px; right: 20px; background: #2c5aa0; color: white; padding: 10px 20px; border-radius: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 999; font-size: 0.9rem; display: flex; align-items: center; gap: 10px; }
        .pulse { animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
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
                    <div><h2>CleanCare</h2><p>Admin Panel</p></div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="../dashboards/admin.php"><i>📊</i> Dashboard</a>
                <a href="manage_bookings.php"><i>📅</i> Bookings</a>
                <a href="manage_customers.php"><i>👥</i> Customers</a>
                <a href="manage_employees.php"><i>👷</i> Employees</a>
                <a href="manage_inventory.php"><i>📦</i> Inventory</a>
                <a href="manage_finance.php" class="active"><i>💰</i> Finance</a>
                <a href="reports.php"><i>📈</i> Reports</a>
                <a href="settings.php"><i>⚙️</i> Settings</a>
            </nav>
        </aside>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1 style="margin: 0; color: #2c5aa0;">💰 Finance Management</h1>
                <a href="export_data.php?type=finance" class="btn-export">📥 Export Finance Data</a>
            </div>
            
            <div class="admin-content">
                <?php display_flash_message(); ?>
                
                <div class="form-card">
                    <h3 style="color: #2c5aa0; margin-bottom: 20px;">📅 Filter by Date Range</h3>
                    <form method="POST" id="filterForm">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Start Date</label>
                                <input type="date" name="start_date" id="start_date" value="<?= htmlspecialchars($start_date) ?>">
                            </div>
                            <div class="form-group">
                                <label>End Date</label>
                                <input type="date" name="end_date" id="end_date" value="<?= htmlspecialchars($end_date) ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn-submit">Apply Filter</button>
                        <?php if ($start_date && $end_date): ?>
                            <a href="manage_finance.php" style="margin-left: 10px; padding: 12px 30px; background: #6c757d; color: white; border-radius: 6px; text-decoration: none; display: inline-block;">Clear Filter</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card revenue">
                        <h3>Total Revenue</h3>
                        <p class="value" id="stat-revenue">R<?= number_format($total_revenue, 2) ?></p>
                    </div>
                    <div class="stat-card expenses">
                        <h3>Employee Payments</h3>
                        <p class="value" id="stat-employee-paid">R<?= number_format($total_employee_paid, 2) ?></p>
                    </div>
                    <div class="stat-card expenses">
                        <h3>Other Expenses</h3>
                        <p class="value" id="stat-expenses">R<?= number_format($total_expenses, 2) ?></p>
                    </div>
                    <div class="stat-card profit">
                        <h3>Net Profit</h3>
                        <p class="value" id="stat-profit">R<?= number_format($profit, 2) ?></p>
                    </div>
                </div>
                
                <div class="chart-container">
                    <h3 style="color: #2c5aa0; margin-bottom: 20px;">📈 Revenue Trend (Last 6 Months)</h3>
                    <canvas id="revenueChart" style="max-height: 300px;"></canvas>
                </div>
                
                <div class="form-card">
                    <h3 style="color: #2c5aa0; margin-bottom: 20px;">💵 Pay Employee</h3>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Select Employee *</label>
                                <select name="employee_id" required>
                                    <option value="">-- Select Employee --</option>
                                    <?php foreach ($employees as $emp): ?>
                                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?> (<?= htmlspecialchars($emp['emp_number']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Amount (R) *</label>
                                <input type="number" step="0.01" name="amount" required placeholder="0.00">
                            </div>
                        </div>
                        <button type="submit" name="pay_employee" class="btn-submit">Process Payment</button>
                    </form>
                </div>
                
                <div class="data-table-container">
                    <h3 style="color: #2c5aa0;">Recent Employee Payments</h3>
                    <div id="payments-table">
                        <?php if (empty($employee_payments)): ?>
                            <p style="text-align: center; color: #999; padding: 40px;">No payments recorded yet.</p>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Employee</th>
                                        <th>Email</th>
                                        <th>Amount</th>
                                        <th>Payment Date</th>
                                    </tr>
                                </thead>
                                <tbody id="payments-tbody">
                                    <?php foreach ($employee_payments as $payment): ?>
                                    <tr>
                                        <td>#<?= $payment['id'] ?></td>
                                        <td><strong><?= htmlspecialchars($payment['employee_name']) ?></strong></td>
                                        <td><?= htmlspecialchars($payment['employee_email']) ?></td>
                                        <td style="font-weight: 600; color: #28a745;">R<?= number_format($payment['amount'], 2) ?></td>
                                        <td><?= date('d M Y', strtotime($payment['pay_date'])) ?></td>
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
        const monthLabels = <?= json_encode(array_column($monthly_revenue, 'month')) ?>;
        const monthData = <?= json_encode(array_column($monthly_revenue, 'total')) ?>;
        
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Revenue (R)',
                    data: monthData,
                    borderColor: '#2c5aa0',
                    backgroundColor: 'rgba(44, 90, 160, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: function(value) { return 'R' + value.toLocaleString(); } }
                    }
                }
            }
        });

        function refreshData() {
            const startDate = document.getElementById('start_date')?.value || '';
            const endDate = document.getElementById('end_date')?.value || '';
            const url = `?ajax=refresh&start_date=${startDate}&end_date=${endDate}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('stat-revenue').textContent = 'R' + data.revenue;
                        document.getElementById('stat-employee-paid').textContent = 'R' + data.employee_paid;
                        document.getElementById('stat-expenses').textContent = 'R' + data.expenses;
                        document.getElementById('stat-profit').textContent = 'R' + data.profit;
                        
                        if (data.payments.length > 0) {
                            let tableHtml = '<table class="data-table"><thead><tr><th>ID</th><th>Employee</th><th>Email</th><th>Amount</th><th>Payment Date</th></tr></thead><tbody>';
                            data.payments.forEach(payment => {
                                const payDate = new Date(payment.pay_date);
                                const formattedDate = payDate.toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'});
                                tableHtml += `
                                    <tr>
                                        <td>#${payment.id}</td>
                                        <td><strong>${payment.employee_name}</strong></td>
                                        <td>${payment.employee_email}</td>
                                        <td style="font-weight: 600; color: #28a745;">R${parseFloat(payment.amount).toFixed(2)}</td>
                                        <td>${formattedDate}</td>
                                    </tr>
                                `;
                            });
                            tableHtml += '</tbody></table>';
                            document.getElementById('payments-table').innerHTML = tableHtml;
                        }
                        
                        document.getElementById('update-time').textContent = data.timestamp;
                    }
                })
                .catch(error => console.error('Refresh error:', error));
        }
        
        setInterval(refreshData, 5000);
    </script>
</body>
</html>