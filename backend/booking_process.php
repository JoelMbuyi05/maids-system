<?php
// backend/booking_process.php
session_start();
require_once 'db_config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

// Handle different actions
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        create_booking();
        break;
    case 'cancel':
        cancel_booking();
        break;
    case 'accept':
        accept_booking();
        break;
    case 'reject':
        reject_booking();
        break;
    case 'complete':
        complete_booking();
        break;
    case 'get_available_cleaners':
        get_available_cleaners();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Create new booking
 */
function create_booking() {
    global $pdo;
    
    try {
        $customer_id = $_SESSION['user_id'];
        $service_type = sanitize_input($_POST['service_type'] ?? '');
        $date = $_POST['date'] ?? '';
        $time = $_POST['time'] ?? '';
        $address = sanitize_input($_POST['address'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $cleaner_id = !empty($_POST['cleaner_id']) ? intval($_POST['cleaner_id']) : null;
        
        // Validation
        if (empty($service_type) || empty($date) || empty($time) || empty($address)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            return;
        }
        
        // Check if date is in the future
        if (strtotime($date) < strtotime('today')) {
            echo json_encode(['success' => false, 'message' => 'Booking date must be in the future']);
            return;
        }
        
        // If cleaner is specified, check availability
        if ($cleaner_id && !check_booking_availability($cleaner_id, $date, $time, $pdo)) {
            echo json_encode(['success' => false, 'message' => 'Selected cleaner is not available at this time']);
            return;
        }
        
        // Insert booking
        $sql = "INSERT INTO bookings (customer_id, cleaner_id, service_type, date, time, address, price, status) 
                VALUES (:customer_id, :cleaner_id, :service_type, :date, :time, :address, :price, 'pending')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':customer_id' => $customer_id,
            ':cleaner_id' => $cleaner_id,
            ':service_type' => $service_type,
            ':date' => $date,
            ':time' => $time,
            ':address' => $address,
            ':price' => $price
        ]);
        
        $booking_id = $pdo->lastInsertId();
        
        // Log activity
        log_activity($customer_id, 'booking_created', "Booking ID: $booking_id");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Booking created successfully!',
            'booking_id' => $booking_id
        ]);
        
    } catch (PDOException $e) {
        error_log("Booking creation error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Unable to create booking. Please try again.']);
    }
}

/**
 * Cancel booking (Customer only)
 */
function cancel_booking() {
    global $pdo;
    
    try {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $user_id = $_SESSION['user_id'];
        
        // Check if booking belongs to user
        $stmt = $pdo->prepare("SELECT customer_id, status FROM bookings WHERE id = :id");
        $stmt->execute([':id' => $booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking) {
            echo json_encode(['success' => false, 'message' => 'Booking not found']);
            return;
        }
        
        if ($booking['customer_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized action']);
            return;
        }
        
        if ($booking['status'] === 'completed' || $booking['status'] === 'cancelled') {
            echo json_encode(['success' => false, 'message' => 'Cannot cancel this booking']);
            return;
        }
        
        // Update status
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = :id");
        $stmt->execute([':id' => $booking_id]);
        
        log_activity($user_id, 'booking_cancelled', "Booking ID: $booking_id");
        
        echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
        
    } catch (PDOException $e) {
        error_log("Booking cancellation error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Unable to cancel booking']);
    }
}

/**
 * Accept booking (Cleaner only)
 */
function accept_booking() {
    global $pdo;
    
    try {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['user_role'];
        
        if ($user_role !== 'cleaner') {
            echo json_encode(['success' => false, 'message' => 'Only cleaners can accept bookings']);
            return;
        }
        
        // Check if booking is assigned to this cleaner
        $stmt = $pdo->prepare("SELECT cleaner_id, status FROM bookings WHERE id = :id");
        $stmt->execute([':id' => $booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking || $booking['cleaner_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'Booking not found or not assigned to you']);
            return;
        }
        
        if ($booking['status'] !== 'pending') {
            echo json_encode(['success' => false, 'message' => 'Booking cannot be accepted']);
            return;
        }
        
        // Update status
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = :id");
        $stmt->execute([':id' => $booking_id]);
        
        log_activity($user_id, 'booking_accepted', "Booking ID: $booking_id");
        
        echo json_encode(['success' => true, 'message' => 'Booking accepted successfully']);
        
    } catch (PDOException $e) {
        error_log("Booking accept error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Unable to accept booking']);
    }
}

/**
 * Reject booking (Cleaner only)
 */
function reject_booking() {
    global $pdo;
    
    try {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['user_role'];
        
        if ($user_role !== 'cleaner') {
            echo json_encode(['success' => false, 'message' => 'Only cleaners can reject bookings']);
            return;
        }
        
        // Check if booking is assigned to this cleaner
        $stmt = $pdo->prepare("SELECT cleaner_id, status FROM bookings WHERE id = :id");
        $stmt->execute([':id' => $booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking || $booking['cleaner_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'Booking not found or not assigned to you']);
            return;
        }
        
        // Update status and remove cleaner assignment
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'pending', cleaner_id = NULL WHERE id = :id");
        $stmt->execute([':id' => $booking_id]);
        
        log_activity($user_id, 'booking_rejected', "Booking ID: $booking_id");
        
        echo json_encode(['success' => true, 'message' => 'Booking rejected']);
        
    } catch (PDOException $e) {
        error_log("Booking reject error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Unable to reject booking']);
    }
}

/**
 * Mark booking as complete (Cleaner only)
 */
function complete_booking() {
    global $pdo;
    
    try {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['user_role'];
        
        if ($user_role !== 'cleaner') {
            echo json_encode(['success' => false, 'message' => 'Only cleaners can mark bookings as complete']);
            return;
        }
        
        // Check if booking is assigned to this cleaner
        $stmt = $pdo->prepare("SELECT cleaner_id, status FROM bookings WHERE id = :id");
        $stmt->execute([':id' => $booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking || $booking['cleaner_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'Booking not found or not assigned to you']);
            return;
        }
        
        if ($booking['status'] !== 'confirmed') {
            echo json_encode(['success' => false, 'message' => 'Only confirmed bookings can be completed']);
            return;
        }
        
        // Update status
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE id = :id");
        $stmt->execute([':id' => $booking_id]);
        
        log_activity($user_id, 'booking_completed', "Booking ID: $booking_id");
        
        echo json_encode(['success' => true, 'message' => 'Booking marked as complete']);
        
    } catch (PDOException $e) {
        error_log("Booking complete error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Unable to complete booking']);
    }
}

/**
 * Get available cleaners
 */
function get_available_cleaners() {
    global $pdo;
    
    try {
        $date = $_POST['date'] ?? '';
        $time = $_POST['time'] ?? '';
        
        // Get cleaners who don't have a booking at this time
        $sql = "SELECT u.id, u.name, u.email 
                FROM users u 
                WHERE u.role = 'cleaner' 
                AND u.id NOT IN (
                    SELECT cleaner_id FROM bookings 
                    WHERE date = :date 
                    AND time = :time 
                    AND status NOT IN ('cancelled', 'rejected')
                    AND cleaner_id IS NOT NULL
                )";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':date' => $date, ':time' => $time]);
        $cleaners = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'cleaners' => $cleaners]);
        
    } catch (PDOException $e) {
        error_log("Get cleaners error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Unable to fetch cleaners']);
    }
}
?>