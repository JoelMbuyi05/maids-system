<?php
require_once '../backend/auth_check.php';
require_once '../backend/db_config.php';
require_once '../backend/functions.php';

// Ensure only cleaners can access
if ($user_role !== 'cleaner') {
    header('Location: ../index.php');
    exit;
}

// Get employee_id from employees table using email - MUST COME FIRST!
try {
    $stmt = $pdo->prepare("SELECT id, name FROM employees WHERE email = :email");
    $stmt->execute([':email' => $user_email]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        $emp_number = 'EMP' . str_pad($user_id, 4, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("INSERT INTO employees (name, emp_number, email, phone) VALUES (:name, :emp_number, :email, '')");
        $stmt->execute([':name' => $user_name, ':emp_number' => $emp_number, ':email' => $user_email]);
        $employee_id = $pdo->lastInsertId();
        $employee = ['id' => $employee_id, 'name' => $user_name];
    } else {
        $employee_id = $employee['id'];
    }

    // NOW fetch notifications (after $employee_id is set)
    $stmt = $pdo->prepare("SELECT * FROM notifications 
                          WHERE user_role = 'cleaner' 
                          AND user_id = :user_id
                          AND is_read = 0 
                          ORDER BY created_at DESC 
                          LIMIT 10");
    $stmt->execute([':user_id' => $employee_id]);
    $cleaner_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle "mark as complete" - AJAX REQUEST
    if (isset($_POST['ajax_complete_job'])) {
        header('Content-Type: application/json');
        $booking_id = intval($_POST['booking_id']);
        
        try {
            // Update with new status for admin review
            $stmt = $pdo->prepare("UPDATE bookings SET 
                                  status = 'completed_by_cleaner',
                                  completed_by_cleaner_at = NOW() 
                                  WHERE id = :id AND cleaner_id = :cleaner_id");
            $stmt->execute([':id' => $booking_id, ':cleaner_id' => $employee_id]);

            if ($stmt->rowCount() === 0) {
                throw new Exception("Booking not found or already updated");
            }

            // Get booking details for notifications
            $stmt = $pdo->prepare("SELECT b.*, c.name as customer_name, c.id as customer_id 
                                  FROM bookings b 
                                  JOIN customers c ON b.customer_id = c.id 
                                  WHERE b.id = :id");
            $stmt->execute([':id' => $booking_id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            // Notify admin
            save_notification(1, 'admin', "⏳ Booking #{$booking_id} marked complete by {$employee['name']}. Please review and confirm.");
            
            // Notify customer
            save_notification($booking['customer_id'], 'customer', "✅ Your booking #{$booking_id} has been completed by the cleaner. Awaiting final confirmation.");
            
            // Send email to admin
            $stmt = $pdo->prepare("SELECT email FROM users WHERE role = 'admin' LIMIT 1");
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($admin && isset($admin['email'])) {
                sendJobCompletionNotification($admin['email'], $employee['name'], $booking_id);
            }
            
            echo json_encode([
                'success' => true,
                'message' => "✅ Job #{$booking_id} marked as complete! Waiting for admin confirmation.",
                'new_status' => 'completed_by_cleaner'
            ]);
            exit;
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }

    // Fetch cleaner's assigned bookings
    $stmt = $pdo->prepare("SELECT b.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address
                           FROM bookings b 
                           LEFT JOIN customers c ON b.customer_id = c.id 
                           WHERE b.cleaner_id = :employee_id 
                           ORDER BY b.booking_date DESC 
                           LIMIT 20");
    $stmt->execute([':employee_id' => $employee_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch available (unassigned) jobs
    $stmt = $pdo->query("SELECT b.*, c.name as customer_name, c.address as customer_address
                         FROM bookings b
                         LEFT JOIN customers c ON b.customer_id = c.id
                         WHERE b.cleaner_id IS NULL AND (b.status = 'pending' OR b.status IS NULL) AND b.completed = 0
                         ORDER BY b.booking_date DESC");
    $available_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats - using status field properly
    $stmt = $pdo->prepare("SELECT 
                           COUNT(*) as total,
                           SUM(CASE WHEN status IN ('confirmed', 'assigned', 'pending', 'completed_by_cleaner') AND completed = 0 THEN 1 ELSE 0 END) as pending,
                           SUM(CASE WHEN status = 'completed' OR completed = 1 THEN 1 ELSE 0 END) as completed,
                           SUM(CASE WHEN status = 'completed' OR completed = 1 THEN price ELSE 0 END) as total_earnings
                           FROM bookings WHERE cleaner_id = :employee_id");
    $stmt->execute([':employee_id' => $employee_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Service name mapping
    $service_names = [
        1 => 'Regular Cleaning',
        2 => 'Deep Cleaning',
        3 => 'Move In/Out Cleaning',
        4 => 'Office Cleaning',
        5 => 'Carpet Cleaning',
        6 => 'Window Cleaning'
    ];

} catch (PDOException $e) {
    error_log("Cleaner dashboard error: " . $e->getMessage());
    $bookings = [];
    $available_jobs = [];
    $cleaner_notifications = [];
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
    <style>
        .success-message {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #155724;
        }
        .error-message {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #721c24;
        }
        .status-badge {
            padding: 6px 14px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
            display: inline-block;
            white-space: nowrap;
        }
        .status-pending { background: #FFC107; color: #000; }
        .status-confirmed { background: #17a2b8; }
        .status-assigned { background: #007bff; }
        .status-completed_by_cleaner { background: #6c757d; }
        .status-completed_by_customer { background: #20c997; }
        .status-completed { background: #28a745; }
        .status-cancelled { background: #dc3545; }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead tr {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        
        th {
            padding: 14px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            color: #495057;
        }
        
        tbody tr {
            border-bottom: 1px solid #ddd;
            transition: background-color 0.2s;
        }
        
        tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        td {
            padding: 14px 12px;
            vertical-align: middle;
        }
    </style>
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
    <section style="padding: 40px 0; background: #f8f9fa; min-height: calc(100vh - 80px);">
        <div class="container">
            <div id="flash-message-container"></div>
            
            <?php display_flash_message(); ?>

            <?php if (!empty($cleaner_notifications)): ?>
            <div style="background: #e7f3ff; border-left: 4px solid #17a2b8; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                <h3 style="margin-top: 0; color: #0c5460;">🔔 New Notifications (<?= count($cleaner_notifications) ?>)</h3>
                <?php foreach ($cleaner_notifications as $notif): ?>
                    <div style="background: white; padding: 15px; border-radius: 6px; margin-bottom: 10px;">
                        <p style="margin: 0; color: #333;"><?= htmlspecialchars($notif['message']) ?></p>
                        <small style="color: #999;"><?= date('M j, g:i a', strtotime($notif['created_at'])) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <h2 style="color: #2c5aa0; margin-bottom: 30px;">Cleaner Dashboard</h2>

            <!-- Stats cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit,minmax(200px,1fr)); gap: 20px; margin-bottom: 40px;">
                <div style="background:white;padding:25px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);border-left:4px solid #2c5aa0;">
                    <h3 style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Total Jobs</h3>
                    <p style="font-size: 2rem; color: #2c5aa0; font-weight: 700; margin: 0;"><?= $stats['total'] ?></p>
                </div>
                <div style="background:white;padding:25px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);border-left:4px solid #FFC107;">
                    <h3 style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Pending</h3>
                    <p style="font-size: 2rem; color: #FFC107; font-weight: 700; margin: 0;"><?= $stats['pending'] ?></p>
                </div>
                <div style="background:white;padding:25px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);border-left:4px solid #28a745;">
                    <h3 style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Completed</h3>
                    <p style="font-size: 2rem; color: #28a745; font-weight: 700; margin: 0;"><?= $stats['completed'] ?></p>
                </div>
                <div style="background:white;padding:25px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);border-left:4px solid #17a2b8;">
                    <h3 style="color: #666; font-size: 0.9rem; margin-bottom: 10px;">Earnings</h3>
                    <p style="font-size: 2rem; color: #17a2b8; font-weight: 700; margin: 0;">R<?= number_format($stats['total_earnings'],2) ?></p>
                </div>
            </div>

            <!-- Assigned Jobs -->
            <div id="jobs" style="background:white;padding:30px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);margin-bottom:40px;">
                <h3 style="color:#2c5aa0;margin-bottom:20px;">My Assigned Jobs</h3>
                <?php if (empty($bookings)): ?>
                    <p style="text-align:center;color:#777; padding: 40px;">No jobs assigned yet.</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width:100%;border-collapse:collapse;">
                            <thead>
                                <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                    <th style="padding: 12px; text-align: left;">ID</th>
                                    <th style="padding: 12px; text-align: left;">Customer</th>
                                    <th style="padding: 12px; text-align: left;">Service</th>
                                    <th style="padding: 12px; text-align: left;">Date</th>
                                    <th style="padding: 12px; text-align: left;">Status</th>
                                    <th style="padding: 12px; text-align: center;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="bookings-tbody">
                            <?php foreach($bookings as $b): 
                                // Determine status - handle NULL and empty status
                                if (empty($b['status'])) {
                                    $status = $b['completed'] == 1 ? 'completed' : 'pending';
                                } else {
                                    $status = $b['status'];
                                }
                                
                                $status_labels = [
                                    'pending' => 'Pending',
                                    'confirmed' => 'Confirmed',
                                    'assigned' => 'Assigned',
                                    'completed_by_cleaner' => 'Awaiting Review',
                                    'completed_by_customer' => 'Customer Approved',
                                    'completed' => 'Completed',
                                    'cancelled' => 'Cancelled'
                                ];
                                $status_label = $status_labels[$status] ?? 'Pending';
                                
                                $service_name = $service_names[$b['service_id']] ?? "Service #{$b['service_id']}";
                                
                                // Determine if can mark complete
                                $can_mark_complete = in_array($status, ['confirmed', 'assigned', 'pending']) && $b['completed'] != 1;
                            ?>
                                <tr style="border-bottom:1px solid #ddd;" id="booking-row-<?= $b['id'] ?>">
                                    <td style="padding: 12px;">#<?= $b['id'] ?></td>
                                    <td style="padding: 12px;"><?= htmlspecialchars($b['customer_name']) ?></td>
                                    <td style="padding: 12px;"><?= htmlspecialchars($service_name) ?></td>
                                    <td style="padding: 12px;"><?= date('d M Y', strtotime($b['booking_date'])) ?></td>
                                    <td style="padding: 12px;">
                                        <span class="status-badge status-<?= $status ?>" id="status-badge-<?= $b['id'] ?>">
                                            <?= $status_label ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px; text-align: center; white-space: nowrap;" id="action-cell-<?= $b['id'] ?>">
                                        <a href="../pages/booking_details.php?id=<?= $b['id'] ?>" 
                                           style="background: #2c5aa0; color: white; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; display: inline-block; margin-right: 5px;">
                                            View
                                        </a>
                                        <?php if($can_mark_complete): ?>
                                        <button onclick="markComplete(<?= $b['id'] ?>)" id="complete-btn-<?= $b['id'] ?>"
                                                style="background:#28a745;color:white;border:none;padding:8px 15px;border-radius:6px;cursor:pointer;font-size:0.85rem;font-weight:600;">
                                            ✓ Mark Complete
                                        </button>
                                        <?php elseif($status === 'completed_by_cleaner'): ?>
                                        <span style="background: #6c757d; color: white; padding: 8px 15px; border-radius: 6px; font-size: 0.85rem; display: inline-block;">
                                            ⏳ Awaiting Admin
                                        </span>
                                        <?php elseif($status === 'completed'): ?>
                                        <span style="background: #28a745; color: white; padding: 8px 15px; border-radius: 6px; font-size: 0.85rem; display: inline-block;">
                                            ✅ Completed
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Available Jobs -->
            <div id="available" style="background:white;padding:30px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);">
                <h3 style="color:#2c5aa0;margin-bottom:20px;">Available Jobs (Unassigned)</h3>
                <?php if (empty($available_jobs)): ?>
                    <p style="text-align:center;color:#777; padding: 40px;">No available jobs right now.</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width:100%;border-collapse:collapse;">
                            <thead>
                                <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                    <th style="padding: 12px; text-align: left;">ID</th>
                                    <th style="padding: 12px; text-align: left;">Customer</th>
                                    <th style="padding: 12px; text-align: left;">Service</th>
                                    <th style="padding: 12px; text-align: left;">Date</th>
                                    <th style="padding: 12px; text-align: left;">Location</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach($available_jobs as $job): 
                                $service_name = $service_names[$job['service_id']] ?? "Service #{$job['service_id']}";
                            ?>
                                <tr style="border-bottom:1px solid #ddd;">
                                    <td style="padding: 12px;">#<?= $job['id'] ?></td>
                                    <td style="padding: 12px;"><?= htmlspecialchars($job['customer_name']) ?></td>
                                    <td style="padding: 12px;"><?= htmlspecialchars($service_name) ?></td>
                                    <td style="padding: 12px;"><?= date('d M Y', strtotime($job['booking_date'])) ?></td>
                                    <td style="padding: 12px;"><?= htmlspecialchars($job['location']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <script>
        function showFlashMessage(message, type = 'success') {
            const container = document.getElementById('flash-message-container');
            const className = type === 'error' ? 'error-message' : 'success-message';
            container.innerHTML = `<div class="${className}">${message}</div>`;
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
            
            // Scroll to top to show message
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        function markComplete(bookingId) {
            if (!confirm('Mark this job as complete?\n\nThis will:\n✓ Notify the admin for review\n✓ Notify the customer\n✓ Update the job status')) {
                return;
            }
            
            const actionCell = document.getElementById(`action-cell-${bookingId}`);
            const btn = document.getElementById(`complete-btn-${bookingId}`);
            const originalHTML = actionCell.innerHTML;
            
            // Show loading state
            btn.disabled = true;
            btn.innerHTML = '⏳ Processing...';
            btn.style.opacity = '0.6';
            
            // Send AJAX request
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax_complete_job=1&booking_id=${bookingId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showFlashMessage(data.message, 'success');
                    
                    // Update the status badge
                    const statusBadge = document.getElementById(`status-badge-${bookingId}`);
                    if (statusBadge) {
                        statusBadge.className = 'status-badge status-completed_by_cleaner';
                        statusBadge.textContent = 'Awaiting Review';
                    }
                    
                    // Update action cell - replace button with waiting message
                    const viewBtn = actionCell.querySelector('a');
                    actionCell.innerHTML = '';
                    actionCell.appendChild(viewBtn);
                    
                    const waitingSpan = document.createElement('span');
                    waitingSpan.style.cssText = 'background: #6c757d; color: white; padding: 8px 15px; border-radius: 6px; font-size: 0.85rem; display: inline-block;';
                    waitingSpan.textContent = '⏳ Awaiting Admin';
                    actionCell.appendChild(waitingSpan);
                    
                } else {
                    showFlashMessage('Error: ' + data.message, 'error');
                    actionCell.innerHTML = originalHTML;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFlashMessage('Network error. Please try again.', 'error');
                actionCell.innerHTML = originalHTML;
            });
        }
    </script>

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