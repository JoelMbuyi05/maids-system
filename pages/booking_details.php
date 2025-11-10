<?php
session_start();
require_once '../backend/db_config.php';
require_once '../backend/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$booking_id = intval($_GET['id'] ?? 0);
$user_role = $_SESSION['user_role'];

// Map service_id to service names
$service_names = [
    1 => 'Regular Cleaning',
    2 => 'Deep Cleaning',
    3 => 'Move In/Out Cleaning',
    4 => 'Office Cleaning',
    5 => 'Carpet Cleaning',
    6 => 'Window Cleaning'
];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        // ADMIN: Assign cleaner
        if ($action === 'assign_cleaner' && $user_role === 'admin') {
            $cleaner_id = intval($_POST['cleaner_id']);
            $stmt = $pdo->prepare("UPDATE bookings SET cleaner_id = :cleaner_id, status = 'assigned' WHERE id = :id");
            $stmt->execute([':cleaner_id' => $cleaner_id, ':id' => $booking_id]);
            
            // Get cleaner info and booking details
            $stmt = $pdo->prepare("SELECT name, email FROM employees WHERE id = :id");
            $stmt->execute([':id' => $cleaner_id]);
            $cleaner = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("SELECT service_id, booking_date FROM bookings WHERE id = :id");
            $stmt->execute([':id' => $booking_id]);
            $booking_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Notify cleaner
            save_notification($cleaner_id, 'cleaner', "🎯 You have been assigned to booking #{$booking_id}");
            
            // Send email notification
            sendCleanerAssignedNotification(
                $cleaner['email'], 
                $cleaner['name'], 
                $booking_info['service_id'], 
                $booking_info['booking_date']
            );
            
            $_SESSION['flash_message'] = "Cleaner assigned successfully!";
            $_SESSION['flash_type'] = "success";
            header("Location: booking_details.php?id=$booking_id");
            exit;
        }
        
        // CLEANER: Mark as complete
        if ($action === 'mark_complete' && $user_role === 'cleaner') {
            // Get cleaner ID
            $stmt = $pdo->prepare("SELECT id, name FROM employees WHERE email = :email");
            $stmt->execute([':email' => $_SESSION['user_email']]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$employee) {
                throw new Exception("Employee not found");
            }
            
            // Update booking
            $stmt = $pdo->prepare("UPDATE bookings SET 
                                  status = 'completed_by_cleaner',
                                  completed_by_cleaner_at = NOW()
                                  WHERE id = :id AND cleaner_id = :cleaner_id");
            $stmt->execute([':id' => $booking_id, ':cleaner_id' => $employee['id']]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Booking not found or unauthorized");
            }
            
            // Get booking details
            $stmt = $pdo->prepare("SELECT b.*, e.name as cleaner_name, c.id as customer_id, c.email as customer_email, c.name as customer_name
                                  FROM bookings b 
                                  JOIN employees e ON b.cleaner_id = e.id 
                                  JOIN customers c ON b.customer_id = c.id
                                  WHERE b.id = :id");
            $stmt->execute([':id' => $booking_id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Notify admin - user_id 1 for admin
            $admin_message = "⏳ Booking #{$booking_id} marked complete by {$employee['name']}. Please review and confirm.";
            save_notification(1, 'admin', $admin_message);
            
            // Notify customer
            $customer_message = "✅ Your booking #{$booking_id} has been completed by the cleaner. Awaiting final confirmation.";
            save_notification($booking['customer_id'], 'customer', $customer_message);
            
            // Send email to admin
            $stmt = $pdo->prepare("SELECT email FROM users WHERE role = 'admin' LIMIT 1");
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($admin && isset($admin['email'])) {
                sendJobCompletionNotification($admin['email'], $employee['name'], $booking_id);
            }
            
            $_SESSION['flash_message'] = "✅ Job marked as complete! Waiting for admin confirmation.";
            $_SESSION['flash_type'] = "success";
            header("Location: booking_details.php?id=$booking_id");
            exit;
        }
        
        // CUSTOMER: Mark as complete
        if ($action === 'customer_mark_complete' && $user_role === 'customer') {
            // Get customer ID
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = :email");
            $stmt->execute([':email' => $_SESSION['user_email']]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$customer) {
                throw new Exception("Customer not found");
            }
            
            // Update booking status
            $stmt = $pdo->prepare("UPDATE bookings SET 
                                  status = 'completed_by_customer',
                                  completed_by_customer_at = NOW()
                                  WHERE id = :id AND customer_id = :customer_id 
                                  AND status IN ('confirmed', 'assigned', 'completed_by_cleaner')");
            $stmt->execute([':id' => $booking_id, ':customer_id' => $customer['id']]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Booking not ready for customer completion");
            }
            
            // Get booking details for notification
            $stmt = $pdo->prepare("SELECT service_id, booking_date, cleaner_id FROM bookings WHERE id = :id");
            $stmt->execute([':id' => $booking_id]);
            $booking_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Notify admin
            save_notification(1, 'admin', "✅ Customer marked booking #{$booking_id} as complete. Please confirm final completion.");
            
            // Notify cleaner if assigned
            if ($booking_info['cleaner_id']) {
                save_notification($booking_info['cleaner_id'], 'cleaner', "👍 Customer marked booking #{$booking_id} as complete.");
            }
            
            $_SESSION['flash_message'] = "Thank you! Marked as complete. Waiting for admin final confirmation.";
            $_SESSION['flash_type'] = "success";
            header("Location: booking_details.php?id=$booking_id");
            exit;
        }
        
        // ADMIN: Confirm completion
        if ($action === 'confirm_completion' && $user_role === 'admin') {
            // Update booking
            $stmt = $pdo->prepare("UPDATE bookings SET 
                                  status = 'completed',
                                  completed = 1,
                                  completed_by_admin_at = NOW()
                                  WHERE id = :id AND status IN ('completed_by_cleaner', 'completed_by_customer')");
            $stmt->execute([':id' => $booking_id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Booking not ready for admin confirmation");
            }
            
            // Get booking details
            $stmt = $pdo->prepare("SELECT b.*, c.email as customer_email, c.name as customer_name, c.id as customer_id,
                                  e.name as cleaner_name, e.id as cleaner_id
                                  FROM bookings b 
                                  JOIN customers c ON b.customer_id = c.id 
                                  LEFT JOIN employees e ON b.cleaner_id = e.id
                                  WHERE b.id = :id");
            $stmt->execute([':id' => $booking_id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Notify customer
            save_notification($booking['customer_id'], 'customer', "🎉 Your booking #{$booking_id} is now complete! Please leave a review.");
            
            // Notify cleaner
            if ($booking['cleaner_id']) {
                save_notification($booking['cleaner_id'], 'cleaner', "🎉 Booking #{$booking_id} has been confirmed as complete!");
            }
            
            // Send completion email to customer
            $service_name = $service_names[$booking['service_id']] ?? "Service";
            sendBookingCompletedEmail($booking['customer_email'], $booking['customer_name'], $booking_id);
            
            $_SESSION['flash_message'] = "✅ Booking confirmed as complete!";
            $_SESSION['flash_type'] = "success";
            header("Location: booking_details.php?id=$booking_id");
            exit;
        }
        
        // CUSTOMER: Cancel booking
        if ($action === 'cancel_booking' && $user_role === 'customer') {
            $reason = sanitize_input($_POST['reason'] ?? 'No reason provided');
            
            // Get customer_id and name
            $stmt = $pdo->prepare("SELECT c.id, c.name FROM customers c WHERE c.email = :email");
            $stmt->execute([':email' => $_SESSION['user_email']]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$customer) {
                throw new Exception("Customer not found");
            }
            
            // Get booking details for notification
            $stmt = $pdo->prepare("SELECT service_id, booking_date, cleaner_id, status FROM bookings WHERE id = :id AND customer_id = :customer_id");
            $stmt->execute([':id' => $booking_id, ':customer_id' => $customer['id']]);
            $booking_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking_info) {
                throw new Exception("Booking not found");
            }
            
            // Check if booking can be cancelled
            if (!in_array($booking_info['status'], ['pending', 'confirmed', 'assigned'])) {
                throw new Exception("Only pending or confirmed bookings can be cancelled");
            }
            
            // Update booking
            $stmt = $pdo->prepare("UPDATE bookings SET 
                                  status = 'cancelled',
                                  cancelled_at = NOW(),
                                  cancellation_reason = :reason
                                  WHERE id = :id AND customer_id = :customer_id");
            $stmt->execute([
                ':reason' => $reason,
                ':id' => $booking_id,
                ':customer_id' => $customer['id']
            ]);
            
            // Notify admin
            $service_name = $service_names[$booking_info['service_id']] ?? "Service";
            $notification_msg = "🚫 Booking #{$booking_id} cancelled by {$customer['name']}\n";
            $notification_msg .= "Service: {$service_name}\n";
            $notification_msg .= "Date: " . date('M j, Y', strtotime($booking_info['booking_date'])) . "\n";
            $notification_msg .= "Reason: " . ($reason ?: 'No reason provided');
            
            save_notification(1, 'admin', $notification_msg);
            
            // Notify cleaner if assigned
            if ($booking_info['cleaner_id']) {
                save_notification($booking_info['cleaner_id'], 'cleaner', "❌ Booking #{$booking_id} has been cancelled by the customer.");
            }
            
            $_SESSION['flash_message'] = "Booking cancelled successfully.";
            $_SESSION['flash_type'] = "success";
            header("Location: ../dashboards/client.php");
            exit;
        }
        
        // CUSTOMER: Submit review
        if ($action === 'submit_review' && $user_role === 'customer') {
            $rating = intval($_POST['rating']);
            $comment = sanitize_input($_POST['comment']);
            
            if ($rating < 1 || $rating > 5) {
                throw new Exception("Please select a rating");
            }
            
            // Get customer_id
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = :email");
            $stmt->execute([':email' => $_SESSION['user_email']]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get booking details
            $stmt = $pdo->prepare("SELECT cleaner_id, status FROM bookings WHERE id = :id AND customer_id = :customer_id");
            $stmt->execute([':id' => $booking_id, ':customer_id' => $customer['id']]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking) {
                throw new Exception("Booking not found");
            }
            
            if ($booking['status'] !== 'completed') {
                throw new Exception("Can only review completed bookings");
            }
            
            // Insert review
            $stmt = $pdo->prepare("INSERT INTO reviews (booking_id, customer_id, cleaner_id, rating, comment) 
                                  VALUES (:booking_id, :customer_id, :cleaner_id, :rating, :comment)");
            $stmt->execute([
                ':booking_id' => $booking_id,
                ':customer_id' => $customer['id'],
                ':cleaner_id' => $booking['cleaner_id'],
                ':rating' => $rating,
                ':comment' => $comment
            ]);
            
            // Notify cleaner
            if ($booking['cleaner_id']) {
                $stars = str_repeat('⭐', $rating);
                save_notification($booking['cleaner_id'], 'cleaner', "New review! {$stars} for booking #{$booking_id}");
            }
            
            $_SESSION['flash_message'] = "Review submitted successfully! Thank you for your feedback.";
            $_SESSION['flash_type'] = "success";
            header("Location: booking_details.php?id=$booking_id");
            exit;
        }
        
    } catch (Exception $e) {
        $_SESSION['flash_message'] = "Error: " . $e->getMessage();
        $_SESSION['flash_type'] = "error";
        header("Location: booking_details.php?id=$booking_id");
        exit;
    }
}

try {
    // Fetch booking with customer and employee details
    $stmt = $pdo->prepare("SELECT b.*, 
                           c.name as customer_name, c.phone as customer_phone, c.email as customer_email, c.address as customer_address,
                           e.name as cleaner_name, e.phone as cleaner_phone, e.email as cleaner_email
                           FROM bookings b
                           LEFT JOIN customers c ON b.customer_id = c.id
                           LEFT JOIN employees e ON b.cleaner_id = e.id
                           WHERE b.id = :id");
    $stmt->execute([':id' => $booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        $_SESSION['flash_message'] = "Booking not found.";
        $_SESSION['flash_type'] = "error";
        header('Location: ../dashboards/' . $user_role . '.php');
        exit;
    }
    
    // Check if customer has access
    if ($user_role === 'customer') {
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = :email");
        $stmt->execute([':email' => $_SESSION['user_email']]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer['id'] != $booking['customer_id']) {
            $_SESSION['flash_message'] = "Unauthorized access.";
            $_SESSION['flash_type'] = "error";
            header('Location: ../dashboards/client.php');
            exit;
        }
    }
    
    // Check if cleaner has access
    if ($user_role === 'cleaner') {
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE email = :email");
        $stmt->execute([':email' => $_SESSION['user_email']]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($employee && $employee['id'] != $booking['cleaner_id']) {
            $_SESSION['flash_message'] = "Unauthorized access.";
            $_SESSION['flash_type'] = "error";
            header('Location: ../dashboards/cleaner.php');
            exit;
        }
    }
    
    // Get all employees for assignment dropdown (admin only)
    if ($user_role === 'admin') {
        $stmt = $pdo->query("SELECT id, name FROM employees ORDER BY name");
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Check if review exists
    $stmt = $pdo->prepare("SELECT * FROM reviews WHERE booking_id = :id");
    $stmt->execute([':id' => $booking_id]);
    $existing_review = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}

// Determine status
$status = $booking['status'] ?? ($booking['completed'] ? 'completed' : 'pending');
$status_colors = [
    'pending' => '#FFC107',
    'confirmed' => '#17a2b8',
    'assigned' => '#007bff',
    'in_progress' => '#007bff',
    'completed_by_cleaner' => '#6c757d',
    'completed_by_customer' => '#17a2b8',
    'completed' => '#28a745',
    'cancelled' => '#dc3545'
];
$status_color = $status_colors[$status] ?? '#FFC107';

$status_labels = [
    'pending' => 'Pending',
    'confirmed' => 'Confirmed',
    'assigned' => 'Assigned',
    'in_progress' => 'In Progress',
    'completed_by_cleaner' => 'Awaiting Admin Review',
    'completed_by_customer' => 'Customer Approved - Awaiting Final Review',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
];
$status_label = $status_labels[$status] ?? 'Pending';

$service_name = $service_names[$booking['service_id']] ?? htmlspecialchars($booking['service_id']);

// Determine which dashboard to go back to
$back_url = match($user_role) {
    'admin' => '../dashboards/admin.php',
    'cleaner' => '../dashboards/cleaner.php',
    'customer' => '../dashboards/client.php',
    default => '../index.php'
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - CleanCare</title>
    <link rel="stylesheet" href="../frontend/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .detail-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px; }
        .detail-item { padding: 15px; background: #f8f9fa; border-radius: 8px; }
        .detail-item strong { color: #666; display: block; margin-bottom: 5px; font-size: 0.9rem; }
        .detail-item p { margin: 0; font-size: 1.1rem; color: #333; }
        .action-btns { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 25px; }
        .btn { padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: 600; border: none; cursor: pointer; transition: 0.2s ease; font-size: 1rem; }
        .btn-assign { background: #17a2b8; color: #fff; }
        .btn-complete { background: #28a745; color: #fff; }
        .btn-cancel { background: #dc3545; color: #fff; }
        .btn-back { background: #6c757d; color: #fff; }
        .btn:hover { opacity: 0.85; transform: translateY(-2px); }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 10% auto; padding: 30px; border-radius: 12px; max-width: 500px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h3 { margin: 0; color: #2c5aa0; }
        .close { font-size: 28px; font-weight: bold; color: #aaa; cursor: pointer; }
        .close:hover { color: #000; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit; }
        .rating-stars { display: flex; gap: 5px; font-size: 2rem; }
        .rating-stars span { cursor: pointer; color: #ddd; transition: color 0.2s; }
        .rating-stars span:hover, .rating-stars span.active { color: #FFC107; }
        .cleaner-action-box { background: #d4edda; border: 2px solid #28a745; padding: 25px; border-radius: 12px; margin-top: 30px; text-align: center; }
        .cleaner-action-box h4 { color: #155724; margin-top: 0; margin-bottom: 15px; font-size: 1.3rem; }
        .cleaner-action-box p { color: #155724; margin-bottom: 20px; }
        @media (max-width: 768px) { .detail-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
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
                        <p class="tagline">Booking Details</p>
                    </div>
                </div>
            </div>
            <ul class="nav-menu">
                <li><a href="<?= $back_url ?>">Dashboard</a></li>
            </ul>
            <div class="nav-buttons">
                <a href="../backend/logout.php" class="btn-primary">Logout</a>
            </div>
        </div>
    </nav>

    <section style="padding: 60px 0; background: #f8f9fa; min-height: calc(100vh - 80px);">
        <div class="container" style="max-width: 900px;">
            <h2 style="color: #2c5aa0; margin-bottom: 30px;">📋 Booking Details #<?= $booking['id'] ?></h2>
            
            <?php display_flash_message(); ?>
            
            <div style="background: white; padding: 40px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                <div style="text-align: center; margin-bottom: 30px;">
                    <span style="background: <?= $status_color ?>; color: white; padding: 10px 30px; border-radius: 25px; font-size: 1.1rem; font-weight: 600;">
                        <?= $status_label ?>
                    </span>
                </div>

                <!-- Service Info -->
                <div style="margin-bottom: 30px; padding-bottom: 30px; border-bottom: 2px solid #f0f0f0;">
                    <h3 style="color: #2c5aa0; margin-bottom: 20px;">📦 Service Information</h3>
                    <div class="detail-row">
                        <div class="detail-item">
                            <strong>Service Type</strong>
                            <p><?= $service_name ?></p>
                        </div>
                        <div class="detail-item">
                            <strong>Booking Date</strong>
                            <p><?= date('l, F j, Y', strtotime($booking['booking_date'])) ?></p>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-item">
                            <strong>Location</strong>
                            <p><?= htmlspecialchars($booking['location']) ?></p>
                        </div>
                        <div class="detail-item">
                            <strong>Price</strong>
                            <p style="font-size: 1.5rem; font-weight: 700; color: #2c5aa0;">R<?= number_format($booking['price'], 2) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Customer Info -->
                <div style="margin-bottom: 30px; padding-bottom: 30px; border-bottom: 2px solid #f0f0f0;">
                    <h3 style="color: #2c5aa0; margin-bottom: 20px;">👤 Customer Information</h3>
                    <div class="detail-row">
                        <div class="detail-item">
                            <strong>Name</strong>
                            <p><?= htmlspecialchars($booking['customer_name']) ?></p>
                        </div>
                        <div class="detail-item">
                            <strong>Email</strong>
                            <p><?= htmlspecialchars($booking['customer_email']) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Cleaner Info -->
                <div style="margin-bottom: 30px;">
                    <h3 style="color: #2c5aa0; margin-bottom: 20px;">👷 Assigned Cleaner</h3>
                    <?php if ($booking['cleaner_name']): ?>
                        <div class="detail-row">
                            <div class="detail-item">
                                <strong>Name</strong>
                                <p><?= htmlspecialchars($booking['cleaner_name']) ?></p>
                            </div>
                            <div class="detail-item">
                                <strong>Phone</strong>
                                <p><?= htmlspecialchars($booking['cleaner_phone']) ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 30px; background: #f8f9fa; border-radius: 8px;">
                            <p style="color: #999; font-style: italic; margin: 0;">⏳ No cleaner assigned yet</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Actions based on role and status -->
                
                <!-- CLEANER ACTIONS - PROMINENT DISPLAY -->
                <?php if ($user_role === 'cleaner'): ?>
                    <?php if (in_array($status, ['confirmed', 'assigned', 'pending'])): ?>
                        <div class="cleaner-action-box">
                            <h4>✅ Ready to Mark as Complete?</h4>
                            <p>Once you've finished the job, click the button below to notify the admin and customer.</p>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="mark_complete">
                                <button type="submit" class="btn btn-complete" style="font-size: 1.1rem; padding: 15px 40px;" onclick="return confirm('Have you completed this job?\n\nClicking OK will:\n✓ Notify the admin for final review\n✓ Notify the customer\n✓ Update the job status')">
                                    ✓ Mark Job as Complete
                                </button>
                            </form>
                        </div>
                    <?php elseif ($status === 'completed_by_cleaner'): ?>
                        <div style="background: #fff3cd; border: 2px solid #ffc107; padding: 25px; border-radius: 12px; margin-top: 30px; text-align: center;">
                            <h4 style="color: #856404; margin-top: 0;">⏳ Waiting for Admin Confirmation</h4>
                            <p style="color: #856404; margin: 0;">You've marked this job as complete. The admin will review and confirm shortly.</p>
                        </div>
                    <?php elseif ($status === 'completed'): ?>
                        <div style="background: #d4edda; border: 2px solid #28a745; padding: 25px; border-radius: 12px; margin-top: 30px; text-align: center;">
                            <h4 style="color: #155724; margin-top: 0;">✅ Job Completed!</h4>
                            <p style="color: #155724; margin: 0;">This job has been confirmed as complete by the admin.</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- CUSTOMER ACTIONS -->
                <?php if ($user_role === 'customer'): ?>
                    <?php if (in_array($status, ['pending', 'confirmed', 'assigned'])): ?>
                        <div class="action-btns">
                            <button onclick="showCancelModal()" class="btn btn-cancel">Cancel Booking</button>
                        </div>
                    <?php endif; ?>

                    <?php if (in_array($status, ['confirmed', 'assigned', 'completed_by_cleaner'])): ?>
                        <div class="action-btns">
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="customer_mark_complete">
                                <button type="submit" class="btn btn-complete" onclick="return confirm('Mark this service as complete? This means you are satisfied with the work done.')">
                                    ✓ Mark as Complete
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <?php if ($status === 'completed' && !$existing_review): ?>
                        <div class="action-btns">
                            <button onclick="showReviewModal()" class="btn btn-complete">⭐ Leave a Review</button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($existing_review): ?>
                        <div style="background: #e7f3ff; padding: 20px; border-radius: 8px; margin-top: 20px;">
                            <h4 style="color: #2c5aa0; margin-top: 0;">Your Review</h4>
                            <div style="color: #FFC107; font-size: 1.5rem; margin-bottom: 10px;">
                                <?php for($i = 0; $i < $existing_review['rating']; $i++) echo '★'; ?>
                                <?php for($i = $existing_review['rating']; $i < 5; $i++) echo '☆'; ?>
                            </div>
                            <p style="margin: 0;"><?= htmlspecialchars($existing_review['comment']) ?></p>
                            <small style="color: #999;">Submitted on <?= date('M j, Y', strtotime($existing_review['created_at'])) ?></small>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- ADMIN ACTIONS -->
                <?php if ($user_role === 'admin'): ?>
                    <?php if (!in_array($status, ['completed', 'cancelled'])): ?>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px;">
                        <h4 style="color: #2c5aa0; margin-bottom: 15px;">Assign/Change Cleaner</h4>
                        <form method="POST" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <input type="hidden" name="action" value="assign_cleaner">
                            <select name="cleaner_id" required style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                                <option value="">-- Select Cleaner --</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?= $emp['id'] ?>" <?= ($booking['cleaner_id'] == $emp['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($emp['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-assign">Assign Cleaner</button>
                        </form>
                    </div>
                    <?php endif; ?>

                    <?php if (in_array($status, ['completed_by_cleaner', 'completed_by_customer'])): ?>
                    <div style="background: #fff3cd; border: 2px solid #ffc107; padding: 25px; border-radius: 12px; margin-top: 30px; text-align: center;">
                        <h4 style="color: #856404; margin-top: 0;">⚠️ Pending Your Confirmation</h4>
                        <p style="color: #856404; margin-bottom: 20px;">
                            <?php if ($status === 'completed_by_cleaner'): ?>
                                The cleaner has marked this job as complete. Please review and confirm.
                            <?php else: ?>
                                The customer has marked this job as complete. Please confirm final completion.
                            <?php endif; ?>
                        </p>
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="action" value="confirm_completion">
                            <button type="submit" class="btn btn-complete" style="font-size: 1.1rem; padding: 15px 40px;" onclick="return confirm('Confirm this booking is complete? This will:\n✓ Update status to completed\n✓ Notify customer and cleaner\n✓ Allow customer to leave review')">
                                ✓ Confirm Completion
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <!-- BACK TO DASHBOARD BUTTON FOR ALL USERS -->
                <div style="text-align: center; margin-top: 40px; padding-top: 30px; border-top: 2px solid #f0f0f0;">
                    <a href="<?= $back_url ?>" class="btn btn-back" style="display: inline-block; text-decoration: none;">
                        ← Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Cancel Booking Modal -->
    <div id="cancelModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Cancel Booking</h3>
                <span class="close" onclick="closeModal('cancelModal')">&times;</span>
            </div>
            <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <p style="margin: 0;"><strong>⚠️ Are you sure you want to cancel this booking?</strong></p>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="cancel_booking">
                <div class="form-group">
                    <label>Reason for cancellation (optional)</label>
                    <textarea name="reason" rows="3" placeholder="Tell us why you're cancelling..."></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeModal('cancelModal')" class="btn" style="background: #6c757d; color: white;">Keep Booking</button>
                    <button type="submit" class="btn btn-cancel">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Review Modal -->
    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Leave a Review</h3>
                <span class="close" onclick="closeModal('reviewModal')">&times;</span>
            </div>
            <form method="POST" onsubmit="return validateReview()">
                <input type="hidden" name="action" value="submit_review">
                <input type="hidden" name="rating" id="ratingInput" value="0">
                <div class="form-group">
                    <label>Rating <span style="color: #dc3545;">*</span></label>
                    <div class="rating-stars" id="ratingStars">
                        <span data-rating="1">★</span>
                        <span data-rating="2">★</span>
                        <span data-rating="3">★</span>
                        <span data-rating="4">★</span>
                        <span data-rating="5">★</span>
                    </div>
                    <small style="color: #999;">Click on stars to rate</small>
                </div>
                <div class="form-group">
                    <label>Your Review <span style="color: #dc3545;">*</span></label>
                    <textarea name="comment" id="reviewComment" rows="4" placeholder="Share your experience..." required></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeModal('reviewModal')" class="btn" style="background: #6c757d; color: white;">Cancel</button>
                    <button type="submit" class="btn btn-complete">Submit Review</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showCancelModal() {
            document.getElementById('cancelModal').style.display = 'block';
        }

        function showReviewModal() {
            document.getElementById('reviewModal').style.display = 'block';
            setupRatingStars();
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function setupRatingStars() {
            const stars = document.querySelectorAll('#ratingStars span');
            stars.forEach(star => {
                star.onclick = function() {
                    const rating = parseInt(this.dataset.rating);
                    document.getElementById('ratingInput').value = rating;
                    stars.forEach(s => {
                        s.classList.remove('active');
                        if (parseInt(s.dataset.rating) <= rating) {
                            s.classList.add('active');
                        }
                    });
                };
            });
        }

        function validateReview() {
            const rating = document.getElementById('ratingInput').value;
            if (rating === '0' || rating === '') {
                alert('Please select a rating by clicking on the stars');
                return false;
            }
            return true;
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2025 CleanCare. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>