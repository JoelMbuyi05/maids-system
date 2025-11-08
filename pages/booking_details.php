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
            $stmt = $pdo->prepare("UPDATE bookings SET cleaner_id = :cleaner_id, status = 'confirmed' WHERE id = :id");
            $stmt->execute([':cleaner_id' => $cleaner_id, ':id' => $booking_id]);
            
            // Get cleaner info
            $stmt = $pdo->prepare("SELECT name, email FROM employees WHERE id = :id");
            $stmt->execute([':id' => $cleaner_id]);
            $cleaner = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Notify cleaner
            save_user_notification($cleaner_id, 'cleaner', "You have been assigned to booking #{$booking_id}");
            
            // Send email
            $stmt = $pdo->prepare("SELECT booking_date, service_id FROM bookings WHERE id = :id");
            $stmt->execute([':id' => $booking_id]);
            $booking_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            sendCleanerAssignedNotification(
                $cleaner['email'], 
                $cleaner['name'], 
                $booking_info['service_id'],
                date('d M Y', strtotime($booking_info['booking_date']))
            );
            
            $_SESSION['flash_message'] = "Cleaner assigned successfully!";
            $_SESSION['flash_type'] = "success";
            header("Location: booking_details.php?id=$booking_id");
            exit;
        }
        
        // CLEANER: Mark as complete
        if ($action === 'mark_complete' && $user_role === 'cleaner') {
            // Get cleaner ID
            $stmt = $pdo->prepare("SELECT id FROM employees WHERE email = :email");
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
            $stmt = $pdo->prepare("SELECT b.*, e.name as cleaner_name 
                                  FROM bookings b 
                                  JOIN employees e ON b.cleaner_id = e.id 
                                  WHERE b.id = :id");
            $stmt->execute([':id' => $booking_id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Notify admin
            save_user_notification(1, 'admin', "Booking #{$booking_id} marked complete by {$booking['cleaner_name']}. Please review and confirm.");
            
            // Send email to admin
            sendJobCompletionNotification('admin@cleancare.com', $booking['cleaner_name'], $booking_id);
            
            $_SESSION['flash_message'] = "Marked as complete. Waiting for admin confirmation.";
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
                                  WHERE id = :id AND status = 'completed_by_cleaner'");
            $stmt->execute([':id' => $booking_id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Booking not ready for admin confirmation");
            }
            
            // Get booking details
            $stmt = $pdo->prepare("SELECT b.*, c.email as customer_email, c.name as customer_name, c.id as customer_id
                                  FROM bookings b 
                                  JOIN customers c ON b.customer_id = c.id 
                                  WHERE b.id = :id");
            $stmt->execute([':id' => $booking_id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Notify customer
            save_user_notification($booking['customer_id'], 'customer', "Your booking #{$booking_id} is now complete! Please leave a review.");
            
            $_SESSION['flash_message'] = "Booking confirmed as complete!";
            $_SESSION['flash_type'] = "success";
            header("Location: booking_details.php?id=$booking_id");
            exit;
        }
        
        // CUSTOMER: Cancel booking
        if ($action === 'cancel_booking' && $user_role === 'customer') {
            $reason = sanitize_input($_POST['reason'] ?? 'Customer requested cancellation');
            
            // Get customer_id
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = :email");
            $stmt->execute([':email' => $_SESSION['user_email']]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$customer) {
                throw new Exception("Customer not found");
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
            save_user_notification(1, 'admin', "Booking #{$booking_id} cancelled by customer. Reason: {$reason}");
            
            $_SESSION['flash_message'] = "Booking cancelled successfully.";
            $_SESSION['flash_type'] = "success";
            header("Location: booking_details.php?id=$booking_id");
            exit;
        }
        
        // CUSTOMER: Submit review
        if ($action === 'submit_review' && $user_role === 'customer') {
            $rating = intval($_POST['rating']);
            $comment = sanitize_input($_POST['comment']);
            
            // Get customer_id
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = :email");
            $stmt->execute([':email' => $_SESSION['user_email']]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get booking details
            $stmt = $pdo->prepare("SELECT cleaner_id FROM bookings WHERE id = :id AND customer_id = :customer_id");
            $stmt->execute([':id' => $booking_id, ':customer_id' => $customer['id']]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking) {
                throw new Exception("Booking not found");
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
                save_user_notification($booking['cleaner_id'], 'cleaner', "You received a {$rating}-star review for booking #{$booking_id}");
            }
            
            $_SESSION['flash_message'] = "Review submitted successfully!";
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
        
        if ($employee['id'] != $booking['cleaner_id']) {
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
    'in_progress' => '#007bff',
    'completed_by_cleaner' => '#6c757d',
    'completed' => '#28a745',
    'cancelled' => '#dc3545'
];
$status_color = $status_colors[$status] ?? '#FFC107';

$service_name = $service_names[$booking['service_id']] ?? htmlspecialchars($booking['service_id']);
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
                <li><a href="../dashboards/<?= $user_role ?>.php">Dashboard</a></li>
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
                        <?= ucwords(str_replace('_', ' ', $status)) ?>
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
                
                <!-- CUSTOMER ACTIONS -->
                <?php if ($user_role === 'customer'): ?>
                    <?php if ($status === 'pending' || $status === 'confirmed'): ?>
                        <div class="action-btns">
                            <button onclick="showCancelModal()" class="btn btn-cancel">Cancel Booking</button>
                        </div>
                    <?php endif; ?>

                    <?php if ($status === 'completed' && !$existing_review): ?>
                        <div class="action-btns">
                            <button onclick="showReviewModal()" class="btn btn-complete">Leave a Review</button>
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
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- CLEANER ACTIONS -->
                <?php if ($user_role === 'cleaner' && ($status === 'confirmed' || $status === 'in_progress')): ?>
                    <div class="action-btns">
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="action" value="mark_complete">
                            <button type="submit" class="btn btn-complete" onclick="return confirm('Mark this booking as complete?')">
                                ✓ Mark as Complete
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- ADMIN ACTIONS -->
                <?php if ($user_role === 'admin'): ?>
                    <?php if ($status !== 'completed' && $status !== 'cancelled'): ?>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px;">
                        <h4 style="color: #2c5aa0; margin-bottom: 15px;">Assign/Change Cleaner</h4>
                        <form method="POST" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <input type="hidden" name="action" value="assign_cleaner">
                            <select name="cleaner_id" required style="padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
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

                    <?php if ($status === 'completed_by_cleaner'): ?>
                    <div class="action-btns">
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="action" value="confirm_completion">
                            <button type="submit" class="btn btn-complete" onclick="return confirm('Confirm this booking is complete?')">
                                ✓ Confirm Completion
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
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
            <form method="POST">
                <input type="hidden" name="action" value="submit_review">
                <input type="hidden" name="rating" id="ratingInput" value="0">
                <div class="form-group">
                    <label>Rating</label>
                    <div class="rating-stars" id="ratingStars">
                        <span data-rating="1">★</span>
                        <span data-rating="2">★</span>
                        <span data-rating="3">★</span>
                        <span data-rating="4">★</span>
                        <span data-rating="5">★</span>
                    </div>
                </div>
                <div class="form-group">
                    <label>Your Review</label>
                    <textarea name="comment" rows="4" placeholder="Share your experience..." required></textarea>
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