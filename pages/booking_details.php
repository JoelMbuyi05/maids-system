<?php
session_start();
require_once '../backend/db_config.php';
require_once '../backend/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$booking_id = intval($_GET['id'] ?? 0);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'assign_cleaner') {
            $cleaner_id = intval($_POST['cleaner_id']);
            $stmt = $pdo->prepare("UPDATE bookings SET cleaner_id = :cleaner_id WHERE id = :id");
            $stmt->execute([':cleaner_id' => $cleaner_id, ':id' => $booking_id]);
            $_SESSION['flash_message'] = "Cleaner assigned successfully!";
            $_SESSION['flash_type'] = "success";
        } elseif ($action === 'complete') {
            $stmt = $pdo->prepare("UPDATE bookings SET completed = 1 WHERE id = :id");
            $stmt->execute([':id' => $booking_id]);
            $_SESSION['flash_message'] = "Booking marked as completed!";
            $_SESSION['flash_type'] = "success";
        } elseif ($action === 'cancel') {
            $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = :id");
            $stmt->execute([':id' => $booking_id]);
            $_SESSION['flash_message'] = "Booking cancelled successfully!";
            $_SESSION['flash_type'] = "success";
            header('Location: manage_bookings.php');
            exit;
        }
        header("Location: booking_details.php?id=$booking_id");
        exit;
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Error: " . $e->getMessage();
        $_SESSION['flash_type'] = "error";
    }
}

try {
    // Fetch booking with customer and employee details from NEW tables
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
        header('Location: manage_bookings.php');
        exit;
    }
    
    // Get all employees for assignment dropdown
    $stmt = $pdo->query("SELECT id, name FROM employees ORDER BY name");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}

// Determine status based on completed field
$status = $booking['completed'] ? 'completed' : 'pending';
$status_color = $booking['completed'] ? '#28a745' : '#FFC107';
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
        .detail-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }
        .detail-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .detail-item strong {
            color: #666;
            display: block;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        .detail-item p {
            margin: 0;
            font-size: 1.1rem;
            color: #333;
        }
        .action-btns {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 25px;
        }
        .btn {
            padding: 12px 25px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: 0.2s ease;
            font-size: 1rem;
        }
        .btn-assign { background: #17a2b8; color: #fff; }
        .btn-complete { background: #28a745; color: #fff; }
        .btn-cancel { background: #dc3545; color: #fff; }
        .btn:hover { opacity: 0.85; transform: translateY(-2px); }
        .assign-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .assign-form select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-right: 10px;
            font-size: 1rem;
        }
        @media (max-width: 768px) {
            .detail-row {
                grid-template-columns: 1fr;
            }
        }
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
                        <p class="tagline">Booking Details</p>
                    </div>
                </div>
            </div>
            <ul class="nav-menu">
                <li><a href="../dashboards/<?= $_SESSION['user_role'] ?>.php">Dashboard</a></li>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <li><a href="manage_bookings.php">Manage Bookings</a></li>
                <?php endif; ?>
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
                <!-- Status Badge -->
                <div style="text-align: center; margin-bottom: 30px;">
                    <span style="background: <?= $status_color ?>; color: white; padding: 10px 30px; border-radius: 25px; font-size: 1.1rem; font-weight: 600;">
                        <?= ucfirst($status) ?>
                    </span>
                </div>

                <!-- Service Info -->
                <div style="margin-bottom: 30px; padding-bottom: 30px; border-bottom: 2px solid #f0f0f0;">
                    <h3 style="color: #2c5aa0; margin-bottom: 20px;">📦 Service Information</h3>
                    <div class="detail-row">
                        <div class="detail-item">
                            <strong>Package Type</strong>
                            <p><?= htmlspecialchars($booking['package']) ?></p>
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
                    <div class="detail-row">
                        <div class="detail-item">
                            <strong>Phone</strong>
                            <p><?= htmlspecialchars($booking['customer_phone'] ?: 'N/A') ?></p>
                        </div>
                        <div class="detail-item">
                            <strong>Address</strong>
                            <p><?= htmlspecialchars($booking['customer_address'] ?: 'N/A') ?></p>
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
                        <div class="detail-item" style="margin-top: 15px;">
                            <strong>Email</strong>
                            <p><?= htmlspecialchars($booking['cleaner_email']) ?></p>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 30px; background: #f8f9fa; border-radius: 8px;">
                            <p style="color: #999; font-style: italic; margin: 0;">⏳ No cleaner assigned yet</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Admin Actions -->
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <!-- Assign Cleaner Form -->
                    <?php if (!$booking['completed']): ?>
                    <div class="assign-form">
                        <h4 style="color: #2c5aa0; margin-bottom: 15px;">Assign/Change Cleaner</h4>
                        <form method="POST" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <input type="hidden" name="action" value="assign_cleaner">
                            <select name="cleaner_id" required>
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

                    <!-- Action Buttons -->
                    <div class="action-btns">
                        <?php if (!$booking['completed']): ?>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="complete">
                                <button type="submit" class="btn btn-complete" onclick="return confirm('Mark this booking as completed?')">✓ Mark as Completed</button>
                            </form>
                        <?php endif; ?>
                        
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="action" value="cancel">
                            <button type="submit" class="btn btn-cancel" onclick="return confirm('Are you sure you want to cancel/delete this booking?')">✕ Cancel Booking</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <a href="<?= $_SESSION['user_role'] === 'admin' ? 'manage_bookings.php' : '../dashboards/' . $_SESSION['user_role'] . '.php' ?>" class="btn-primary" style="text-decoration: none; display: inline-block;">
                    ← Back to <?= $_SESSION['user_role'] === 'admin' ? 'Bookings' : 'Dashboard' ?>
                </a>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2025 CleanCare. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>