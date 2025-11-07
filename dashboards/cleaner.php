<?php
require_once '../backend/auth_check.php';
require_once '../backend/db_config.php';
require_once '../backend/functions.php';

// Ensure only cleaners can access
if ($user_role !== 'cleaner') {
    header('Location: ../index.php');
    exit;
}

// Get employee_id from employees table using email
try {
    $stmt = $pdo->prepare("SELECT id FROM employees WHERE email = :email");
    $stmt->execute([':email' => $user_email]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        $emp_number = 'EMP' . str_pad($user_id, 4, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("INSERT INTO employees (name, emp_number, email, phone) VALUES (:name, :emp_number, :email, '')");
        $stmt->execute([':name' => $user_name, ':emp_number' => $emp_number, ':email' => $user_email]);
        $employee_id = $pdo->lastInsertId();
    } else {
        $employee_id = $employee['id'];
    }

    // Handle "mark as complete"
    if (isset($_POST['complete_job'])) {
        $booking_id = $_POST['booking_id'];
        $stmt = $pdo->prepare("UPDATE bookings SET completed = 1, completed_at = NOW() WHERE id = :id AND cleaner_id = :cleaner_id");
        $stmt->execute([':id' => $booking_id, ':cleaner_id' => $employee_id]);

        // Notify admin (to be handled in functions.php)
        add_notification($pdo, 'admin', "Cleaner $user_name marked booking #$booking_id as completed.");
        set_flash_message("Job #$booking_id marked as completed successfully!", "success");
        header("Location: cleaner.php");
        exit;
    }

    // Fetch cleaner’s assigned bookings
    $stmt = $pdo->prepare("SELECT b.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address
                           FROM bookings b 
                           LEFT JOIN customers c ON b.customer_id = c.id 
                           WHERE b.cleaner_id = :employee_id 
                           ORDER BY b.booking_date DESC 
                           LIMIT 20");
    $stmt->execute([':employee_id' => $employee_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // NEW FEATURE: fetch available (unassigned) jobs
    $stmt = $pdo->query("SELECT b.*, c.name as customer_name, c.address as customer_address
                         FROM bookings b
                         LEFT JOIN customers c ON b.customer_id = c.id
                         WHERE b.cleaner_id IS NULL AND b.completed = 0
                         ORDER BY b.booking_date DESC");
    $available_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats
    $stmt = $pdo->prepare("SELECT 
                           COUNT(*) as total,
                           SUM(CASE WHEN completed = 0 THEN 1 ELSE 0 END) as pending,
                           SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed,
                           SUM(CASE WHEN completed = 1 THEN price ELSE 0 END) as total_earnings
                           FROM bookings WHERE cleaner_id = :employee_id");
    $stmt->execute([':employee_id' => $employee_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Cleaner dashboard error: " . $e->getMessage());
    $bookings = [];
    $available_jobs = [];
    $stats = ['total' => 0, 'pending' => 0, 'completed' => 0, 'total_earnings' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleaner Dashboard - CleanCare</title>
    <link rel="stylesheet" href="../frontend/css/style.css">
    <link rel="stylesheet" href="../frontend/css/dashboard.css">
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
                <li><a href="#available">Available Jobs</a></li>
            </ul>
            <div class="nav-buttons">
                <span style="margin-right: 15px; color: #666;">Welcome, <strong><?= htmlspecialchars($user_name) ?></strong></span>
                <a href="../backend/logout.php" class="btn-primary">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Dashboard -->
    <section style="padding: 40px 0; background: #f8f9fa;">
        <div class="container">
            <?php display_flash_message(); ?>
            <h2 style="color: #2c5aa0; margin-bottom: 30px;">Cleaner Dashboard</h2>

            <!-- Stats cards (unchanged) -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit,minmax(200px,1fr)); gap: 20px; margin-bottom: 40px;">
                <div style="background:white;padding:25px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);border-left:4px solid #2c5aa0;">
                    <h3>Total Jobs</h3><p><?= $stats['total'] ?></p>
                </div>
                <div style="background:white;padding:25px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);border-left:4px solid #FFC107;">
                    <h3>Pending</h3><p><?= $stats['pending'] ?></p>
                </div>
                <div style="background:white;padding:25px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);border-left:4px solid #28a745;">
                    <h3>Completed</h3><p><?= $stats['completed'] ?></p>
                </div>
                <div style="background:white;padding:25px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);border-left:4px solid #17a2b8;">
                    <h3>Earnings</h3><p>R<?= number_format($stats['total_earnings'],2) ?></p>
                </div>
            </div>

            <!-- Assigned Jobs -->
            <div id="jobs" style="background:white;padding:30px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);margin-bottom:40px;">
                <h3 style="color:#2c5aa0;margin-bottom:20px;">My Assigned Jobs</h3>
                <?php if (empty($bookings)): ?>
                    <p style="text-align:center;color:#777;">No jobs assigned yet.</p>
                <?php else: ?>
                    <table style="width:100%;border-collapse:collapse;">
                        <thead><tr>
                            <th>ID</th><th>Customer</th><th>Service</th><th>Date</th><th>Status</th><th>Action</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach($bookings as $b): ?>
                            <tr style="border-bottom:1px solid #ddd;">
                                <td>#<?= $b['id'] ?></td>
                                <td><?= htmlspecialchars($b['customer_name']) ?></td>
                                <td><?= htmlspecialchars($b['package']) ?></td>
                                <td><?= date('d M Y', strtotime($b['booking_date'])) ?></td>
                                <td><?= $b['completed'] ? '<span style="color:#28a745;">Completed</span>' : '<span style="color:#FFC107;">Pending</span>' ?></td>
                                <td style="text-align:center;">
                                    <a href="../pages/booking_details.php?id=<?= $b['id'] ?>" class="btn-primary" style="padding:6px 10px;">View</a>
                                    <?php if(!$b['completed']): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                        <button type="submit" name="complete_job" style="background:#28a745;color:white;border:none;padding:6px 10px;border-radius:6px;cursor:pointer;">Mark Complete</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- NEW FEATURE: Available Jobs -->
            <div id="available" style="background:white;padding:30px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);">
                <h3 style="color:#2c5aa0;margin-bottom:20px;">Available Jobs</h3>
                <?php if (empty($available_jobs)): ?>
                    <p style="text-align:center;color:#777;">No available jobs right now.</p>
                <?php else: ?>
                    <table style="width:100%;border-collapse:collapse;">
                        <thead><tr>
                            <th>ID</th><th>Customer</th><th>Service</th><th>Date</th><th>Location</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach($available_jobs as $job): ?>
                            <tr style="border-bottom:1px solid #ddd;">
                                <td>#<?= $job['id'] ?></td>
                                <td><?= htmlspecialchars($job['customer_name']) ?></td>
                                <td><?= htmlspecialchars($job['package']) ?></td>
                                <td><?= date('d M Y', strtotime($job['booking_date'])) ?></td>
                                <td><?= htmlspecialchars($job['location']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer class="footer"><div class="container"><p>&copy; 2025 CleanCare. All rights reserved.</p></div></footer>
</body>
</html>
