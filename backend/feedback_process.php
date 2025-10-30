<?php
// backend/feedback_process.php
session_start();
require_once 'db_config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'submit':
        submit_feedback();
        break;
    case 'get_by_booking':
        get_feedback_by_booking();
        break;
    case 'get_by_cleaner':
        get_feedback_by_cleaner();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Submit feedback for a completed booking
 */
function submit_feedback() {
    global $pdo;
    
    try {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $rating = intval($_POST['rating'] ?? 0);
        $comments = sanitize_input($_POST['comments'] ?? '');
        $user_id = $_SESSION['user_id'];
        
        // Validation
        if ($rating < 1 || $rating > 5) {
            echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
            return;
        }
        
        if (empty($comments)) {
            echo json_encode(['success' => false, 'message' => 'Please provide feedback comments']);
            return;
        }
        
        // Check if booking exists and belongs to user
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
        
        if ($booking['status'] !== 'completed') {
            echo json_encode(['success' => false, 'message' => 'Feedback can only be submitted for completed bookings']);
            return;
        }
        
        // Store feedback in contact_messages (temporary solution)
        $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message, submitted_at) 
                               VALUES (:name, :email, :subject, :message, NOW())");
        $stmt->execute([
            ':name' => $_SESSION['user_name'],
            ':email' => $_SESSION['user_email'],
            ':subject' => "Booking #$booking_id Feedback - Rating: $rating/5",
            ':message' => $comments
        ]);
        
        // Log activity
        log_activity($user_id, 'feedback_submitted', "Booking ID: $booking_id, Rating: $rating");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Thank you for your feedback!'
        ]);
        
    } catch (PDOException $e) {
        error_log("Feedback submission error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Unable to submit feedback. Please try again.']);
    }
}

/**
 * Get feedback for a specific booking
 */
function get_feedback_by_booking() {
    global $pdo;
    
    try {
        $booking_id = intval($_GET['booking_id'] ?? 0);
        
        $stmt = $pdo->prepare("SELECT * FROM contact_messages 
                               WHERE subject LIKE :subject 
                               ORDER BY submitted_at DESC");
        $stmt->execute([':subject' => "%Booking #$booking_id%"]);
        $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'feedback' => $feedback]);
        
    } catch (PDOException $e) {
        error_log("Get feedback error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Unable to fetch feedback']);
    }
}

/**
 * Get all feedback for a cleaner
 */
function get_feedback_by_cleaner() {
    global $pdo;
    
    try {
        $cleaner_id = intval($_GET['cleaner_id'] ?? 0);
        
        $sql = "SELECT b.id as booking_id, 
                       c.name as customer_name,
                       b.date,
                       b.service_type
                FROM bookings b
                JOIN users c ON b.customer_id = c.id
                WHERE b.cleaner_id = :cleaner_id 
                AND b.status = 'completed'
                ORDER BY b.date DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':cleaner_id' => $cleaner_id]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get feedback for each booking
        foreach ($bookings as &$booking) {
            $stmt = $pdo->prepare("SELECT * FROM contact_messages 
                                   WHERE subject LIKE :subject 
                                   LIMIT 1");
            $stmt->execute([':subject' => "%Booking #" . $booking['booking_id'] . "%"]);
            $booking['feedback'] = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        echo json_encode(['success' => true, 'bookings' => $bookings]);
        
    } catch (PDOException $e) {
        error_log("Get cleaner feedback error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Unable to fetch feedback']);
    }
}
?>