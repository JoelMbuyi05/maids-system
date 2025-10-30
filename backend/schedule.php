<?php
session_start();
require_once '../backend/db_config.php';

// Check if logged in as cleaner
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'cleaner') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Fetch cleaner's upcoming schedule
try {
    $stmt = $pdo->prepare("SELECT b.*, u.name as customer_name, u.phone as customer_phone 
                           FROM bookings b 
                           LEFT JOIN users u ON b.customer_id = u.id 
                           WHERE b.cleaner_id = :user_id 
                           AND b.status IN ('pending', 'confirmed')
                           AND b.date >= CURDATE()
                           ORDER BY b.date ASC, b.time ASC");
    $stmt->execute([':user_id' => $user_id]);
    $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $schedule = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - CleanCare</title>
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
                            <path d="M 18 32 L 30 20 L 42 32" stroke="white" stroke-width="2" fill="none" stroke-linecap="round"/>
                            <rect x="27" y="36" width="6" height="6" fill="#2c5aa0"/>
                            <circle cx="36" cy="26" r="1.5" fill="#FFC107"/>
                            <path d="M 36 23 L 36 29 M 33 26 L 39 26" stroke="#FFC107" stroke-width="1.5" stroke-linecap="round"/>
                            <circle cx="24" cy="28" r="1" fill="#FFC107"/>
                            <path d="M 24 26 L 24 30 M 22 28 L 26 28" stroke="#FFC107" stroke-width="1" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="logo-text">
                        <h1>CleanCare</h1>
                        <p class="tagline">My Schedule</p>
                    </div>
                </div>
            </div>
            <ul class="nav-menu">
                <li><a href="../index.php">Home</a></li>
                <li><a href="../dashboards/cleaner.php">Dashboard</a></li>
                <li><a href="#" class="active">Schedule</a></li>
            </ul>
            <div class="nav-buttons">
                <span style="margin-right: 15px; color: #666;">Welcome, <strong><?= htmlspecialchars($user_name) ?></strong></span>
                <a href="../backend/logout.php" class="btn-primary">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Schedule Content -->
    <section style="padding: 60px 0; background: #f8f9fa; min-height: calc(100vh - 80px);">
        <div class="container">
            <h2 style="color: #2c5aa0; margin-bottom: 30px;">📅 My Upcoming Schedule</h2>
            
            <?php if (empty($schedule)): ?>
                <div style="background: white; padding: 60px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); text-align: center;">
                    <p style="font-size: 3rem; margin-bottom: 20px;">📭</p>
                    <h3 style="color: #2c5aa0; margin-bottom: 10px;">No Upcoming Jobs</h3>
                    <p style="color: #666;">Check back later for new assignments or pending requests.</p>
                    <a href="../dashboards/cleaner.php" class="btn-primary" style="display: inline-block; margin-top: 20px; text-decoration: none;">Back to Dashboard</a>
                </div>
            <?php else: ?>
                <!-- Calendar View -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 25px;">
                    <?php 
                    $current_date = '';
                    foreach ($schedule as $booking): 
                        $booking_date = date('l, d F Y', strtotime($booking['date']));
                        
                        // Show date header if new day
                        if ($booking_date !== $current_date):
                            if ($current_date !== '') echo '</div></div>'; // Close previous day card
                            $current_date = $booking_date;
                    ?>
                        <div style="background: white; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); overflow: hidden;">
                            <div style="background: linear-gradient(135deg, #2c5aa0, #1e4079); color: white; padding: 20px; text-align: center;">
                                <h3 style="margin: 0; font-size: 1.3rem;"><?= $booking_date ?></h3>
                            </div>
                            <div style="padding: 20px;">
                    <?php endif; ?>
                    
                    <!-- Booking Card -->
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 15px; border-left: 4px solid <?= $booking['status'] === 'confirmed' ? '#28a745' : '#FFC107' ?>;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                            <h4 style="margin: 0; color: #2c5aa0; font-size: 1.1rem;">🕐 <?= date('H:i', strtotime($booking['time'])) ?></h4>
                            <span style="background: <?= $booking['status'] === 'confirmed' ? '#28a745' : '#FFC107' ?>; color: white; padding: 4px 12px; border-radius: 12px; font-size: 0.85rem; font-weight: 600;">
                                <?= ucfirst($booking['status']) ?>
                            </span>
                        </div>
                        <p style="margin: 8px 0; font-weight: 600; color: #333;">
                            <strong>Service:</strong> <?= htmlspecialchars($booking['service_type']) ?>
                        </p>
                        <p style="margin: 8px 0; color: #666;">
                            <strong>Customer:</strong> <?= htmlspecialchars($booking['customer_name']) ?>
                        </p>
                        <p style="margin: 8px 0; color: #666;">
                            <strong>Phone:</strong> <?= htmlspecialchars($booking['customer_phone']) ?>
                        </p>
                        <p style="margin: 8px 0; color: #666;">
                            <strong>Address:</strong> <?= htmlspecialchars($booking['address']) ?>
                        </p>
                        <p style="margin: 8px 0; font-weight: 700; color: #2c5aa0; font-size: 1.1rem;">
                            <strong>Payment:</strong> R<?= number_format($booking['price'], 2) ?>
                        </p>
                        
                        <?php if ($booking['status'] === 'pending'): ?>
                            <div style="display: flex; gap: 10px; margin-top: 15px;">
                                <button onclick="acceptBooking(<?= $booking['id'] ?>)" style="flex: 1; background: #28a745; color: white; border: none; padding: 10px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                                    ✓ Accept
                                </button>
                                <button onclick="rejectBooking(<?= $booking['id'] ?>)" style="flex: 1; background: #dc3545; color: white; border: none; padding: 10px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                                    ✗ Decline
                                </button>
                            </div>
                        <?php elseif ($booking['status'] === 'confirmed'): ?>
                            <button onclick="completeBooking(<?= $booking['id'] ?>)" style="width: 100%; margin-top: 15px; background: #17a2b8; color: white; border: none; padding: 10px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                                ✓ Mark as Complete
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <?php endforeach; ?>
                    <?php if (!empty($schedule)): ?>
                            </div> <!-- Close last day's bookings container -->
                        </div> <!-- Close last day card -->
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2025 CleanCare. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="../frontend/js/main.js"></script>
    <script>
        function acceptBooking(bookingId) {
            if (!confirm('Accept this booking?')) return;
            handleBookingAction('accept', bookingId);
        }

        function rejectBooking(bookingId) {
            if (!confirm('Reject this booking?')) return;
            handleBookingAction('reject', bookingId);
        }

        function completeBooking(bookingId) {
            if (!confirm('Mark this job as complete?')) return;
            handleBookingAction('complete', bookingId);
        }

        function handleBookingAction(action, bookingId) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('booking_id', bookingId);
            
            fetch('../backend/booking_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
    </script>
</body>
</html>