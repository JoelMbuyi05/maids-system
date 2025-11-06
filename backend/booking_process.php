<?php
// backend/booking_process.php
session_start();
require_once 'db_config.php';
require_once 'functions.php'; // Assuming sanitize_input and log_activity are here

// Ensure user role is available in session
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? '';

// Check if user is logged in
if (!$user_id) {
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
    case 'get_details': // NEW ACTION: Fetch booking details for the 'View' page
        get_booking_details();
        break;
    case 'get_available_cleaners':
        get_available_cleaners();
        break;
    case 'assign_cleaner':
        assign_cleaner();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Fetch detailed booking information for the 'View' page
 */
function get_booking_details() {
    global $pdo;
    
    try {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        
        if (!$booking_id) {
            echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
            return;
        }

        // Fetch booking, customer name, and cleaner name (if assigned)
        $sql = "SELECT 
                    b.*, 
                    c.name AS customer_name, 
                    c.email AS customer_email,
                    cl.name AS cleaner_name, 
                    cl.email AS cleaner_email
                FROM bookings b
                LEFT JOIN users c ON b.customer_id = c.id
                LEFT JOIN users cl ON b.cleaner_id = cl.id
                WHERE b.id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $booking_id]);
        $booking_details = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($booking_details) {
            echo json_encode(['success' => true, 'booking' => $booking_details]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Booking not found']);
        }

    } catch (PDOException $e) {
        error_log("Get booking details error: " . $e->getMessage());
        // *** DEBUGGING: Return the exact DB error ***
        echo json_encode(['success' => false, 'message' => 'DB Fetch Error: ' . $e->getMessage()]);
    }
}


/**
 * Create new booking
 */
function create_booking() {
    global $pdo, $user_id; 
    
    try {
        $customer_id = $user_id; 
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
        
        if (strtotime($date) < strtotime('today')) {
            echo json_encode(['success' => false, 'message' => 'Booking date must be in the future']);
            return;
        }
        
        // Assuming check_booking_availability exists in functions.php
        if ($cleaner_id && !check_booking_availability($cleaner_id, $date, $time, $pdo)) {
            echo json_encode(['success' => false, 'message' => 'Selected cleaner is not available at this time']);
            return;
        }
        
        $sql = "INSERT INTO bookings (customer_id, cleaner_id, service_type, booking_date, time, address, price, status) 
                 VALUES (:customer_id, :cleaner_id, :service_type, :booking_date, :time, :address, :price, 'pending')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':customer_id' => $customer_id,
            ':cleaner_id' => $cleaner_id,
            ':service_type' => $service_type,
            ':booking_date' => $date,
            ':time' => $time,
            ':address' => $address,
            ':price' => $price
        ]);
        
        $booking_id = $pdo->lastInsertId();
        
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
 * Cancel booking (Admin or Customer)
 */
function cancel_booking() {
    global $pdo, $user_id, $user_role; 
    
    try {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        
        $stmt = $pdo->prepare("SELECT customer_id, status FROM bookings WHERE id = :id");
        $stmt->execute([':id' => $booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking) {
            echo json_encode(['success' => false, 'message' => 'Booking not found']);
            return;
        }
        
        // Check authorization: Must be the customer OR an admin
        if ($booking['customer_id'] != $user_id && $user_role !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized action. Only the customer or an admin can cancel.']);
            return;
        }
        
        if (in_array($booking['status'], ['completed', 'cancelled'])) {
            echo json_encode(['success' => false, 'message' => 'Cannot cancel this booking as it is already complete or cancelled']);
            return;
        }
        
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = :id");
        $stmt->execute([':id' => $booking_id]);
        
        log_activity($user_id, 'booking_cancelled', "Booking ID: $booking_id (Cancelled by $user_role)");
        
        echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
        
    } catch (PDOException $e) {
        error_log("Booking cancellation error: " . $e->getMessage());
        // *** DEBUGGING: Return the exact DB error ***
        echo json_encode(['success' => false, 'message' => 'DB Cancel Error: ' . $e->getMessage()]); // <-- Line 142
    }
}

/**
 * Accept booking (Cleaner only)
 */
function accept_booking() {
    global $pdo, $user_id, $user_role; 
    
    try {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        
        if ($user_role !== 'cleaner') {
            echo json_encode(['success' => false, 'message' => 'Only cleaners can accept bookings']);
            return;
        }
        
        $stmt = $pdo->prepare("SELECT cleaner_id, status FROM bookings WHERE id = :id");
        $stmt->execute([':id' => $booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking || $booking['cleaner_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'Booking not found or not assigned to you']);
            return;
        }
        
        if ($booking['status'] !== 'assigned') {
            echo json_encode(['success' => false, 'message' => 'Booking cannot be accepted']);
            return;
        }
        
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
    global $pdo, $user_id, $user_role; 
    
    try {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        
        if ($user_role !== 'cleaner') {
            echo json_encode(['success' => false, 'message' => 'Only cleaners can reject bookings']);
            return;
        }
        
        $stmt = $pdo->prepare("SELECT cleaner_id, status FROM bookings WHERE id = :id");
        $stmt->execute([':id' => $booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking || $booking['cleaner_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'Booking not found or not assigned to you']);
            return;
        }
        
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
 * Complete booking (Admin or Cleaner)
 */
function complete_booking() {
    global $pdo, $user_id, $user_role; 
    
    try {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        
        // Authorization check: Must be a cleaner OR an admin
        if ($user_role !== 'cleaner' && $user_role !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Only cleaners or admins can mark bookings as complete']);
            return;
        }
        
        $stmt = $pdo->prepare("SELECT cleaner_id, status FROM bookings WHERE id = :id");
        $stmt->execute([':id' => $booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking) {
            echo json_encode(['success' => false, 'message' => 'Booking not found']);
            return;
        }
        
        // If the user is a cleaner, ensure they are the assigned cleaner
        if ($user_role === 'cleaner' && $booking['cleaner_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'Booking not assigned to you']);
            return;
        }
        
        if ($booking['status'] !== 'confirmed' && $booking['status'] !== 'assigned') {
            echo json_encode(['success' => false, 'message' => 'Booking must be confirmed or assigned before completion']);
            return;
        }
        
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE id = :id");
        $stmt->execute([':id' => $booking_id]);
        
        log_activity($user_id, 'booking_completed', "Booking ID: $booking_id (Marked complete by $user_role)");
        
        echo json_encode(['success' => true, 'message' => 'Booking marked as complete']);
        
    } catch (PDOException $e) {
        error_log("Booking complete error: " . $e->getMessage());
        // *** DEBUGGING: Return the exact DB error ***
        echo json_encode(['success' => false, 'message' => 'DB Complete Error: ' . $e->getMessage()]); // <-- Line 331
    }
}

/**
 * Assign cleaner (Admin only)
 */
function assign_cleaner() {
    global $pdo, $user_role, $user_id; 

    $booking_id = intval($_POST['booking_id'] ?? 0);
    $cleaner_id = intval($_POST['cleaner_id'] ?? 0);

    if ($user_role !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Only admin can assign cleaners']);
        return;
    }

    if (!$booking_id || !$cleaner_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid booking ID or cleaner ID provided']);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT booking_date, time FROM bookings WHERE id = :id");
        $stmt->execute([':id' => $booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            echo json_encode(['success' => false, 'message' => 'Booking not found']);
            return;
        }

        // Check if cleaner is already busy at this time
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings 
                             WHERE cleaner_id = :cleaner_id 
                               AND booking_date = :date 
                               AND time = :time 
                               AND status NOT IN ('cancelled', 'completed')
                               AND id != :current_booking_id"); 
        $stmt->execute([
            ':cleaner_id' => $cleaner_id,
            ':date' => $booking['booking_date'],
            ':time' => $booking['time'],
            ':current_booking_id' => $booking_id 
        ]);

        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Cleaner is already assigned to another booking at this exact time']);
            return;
        }

        $stmt = $pdo->prepare("UPDATE bookings SET cleaner_id = :cleaner_id, status = 'assigned' WHERE id = :id");
        $stmt->execute([
            ':cleaner_id' => $cleaner_id,
            ':id' => $booking_id
        ]);

        log_activity($user_id, 'cleaner_assigned', "Booking ID: $booking_id, Cleaner ID: $cleaner_id (Assigned by Admin)");

        echo json_encode(['success' => true, 'message' => 'Cleaner assigned successfully']);

    } catch (PDOException $e) {
        error_log("Assign cleaner error: " . $e->getMessage());
        // *** DEBUGGING: Return the exact DB error ***
        echo json_encode(['success' => false, 'message' => 'DB Assign Error: ' . $e->getMessage()]); // <-- Line 410
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
        
        $sql = "SELECT u.id, u.name, u.email 
                 FROM users u 
                 WHERE u.role = 'cleaner' 
                 AND u.id NOT IN (
                     SELECT cleaner_id FROM bookings 
                     WHERE booking_date = :date 
                     AND time = :time 
                     AND status NOT IN ('cancelled', 'completed')
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