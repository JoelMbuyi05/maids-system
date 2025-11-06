<?php
require_once '../backend/auth_check.php';
require_once '../backend/db_config.php';
require_once '../backend/functions.php';

// Ensure only admin can access
if ($user_role !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$customer_id = intval($_GET['id'] ?? 0);

// Fetch customer data
try {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = :id");
    $stmt->execute([':id' => $customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        $_SESSION['flash_message'] = "Customer not found.";
        $_SESSION['flash_type'] = "error";
        header('Location: manage_customers.php');
        exit;
    }
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}

// Handle update
if (isset($_POST['update_customer'])) {
    try {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);

        // Check if email is taken by another customer
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = :email AND id != :id");
        $stmt->execute([':email' => $email, ':id' => $customer_id]);
        
        if ($stmt->fetch()) {
            $_SESSION['flash_message'] = "Email is already used by another customer!";
            $_SESSION['flash_type'] = "error";
        } else {
            // Update customer
            $stmt = $pdo->prepare("UPDATE customers SET name = :name, email = :email, phone = :phone, address = :address WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':address' => $address,
                ':id' => $customer_id
            ]);
            
            $_SESSION['flash_message'] = "Customer updated successfully!";
            $_SESSION['flash_type'] = "success";
            header('Location: manage_customers.php');
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Error updating customer.";
        $_SESSION['flash_type'] = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer - CleanCare</title>
    <link rel="stylesheet" href="../frontend/css/style.css">
    <link rel="stylesheet" href="../frontend/css/dashboard.css">
    <style>
        body { margin: 0; background: #f8f9fa; }
        .container { max-width: 800px; margin: 0 auto; padding: 40px 20px; }
        .form-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2c5aa0;
        }
        .btn-submit {
            background: #2c5aa0;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-right: 10px;
        }
        .btn-submit:hover { background: #1e4079; }
        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-cancel:hover { background: #5a6268; }
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
                        <p class="tagline">Edit Customer</p>
                    </div>
                </div>
            </div>
            <ul class="nav-menu">
                <li><a href="../dashboards/admin.php">Dashboard</a></li>
                <li><a href="manage_customers.php">Customers</a></li>
            </ul>
            <div class="nav-buttons">
                <a href="../backend/logout.php" class="btn-primary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2 style="color: #2c5aa0; margin-bottom: 30px;">✏️ Edit Customer</h2>
        
        <?php display_flash_message(); ?>
        
        <div class="form-card">
            <form method="POST">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($customer['name']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($customer['email']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Phone Number *</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($customer['phone']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="3"><?= htmlspecialchars($customer['address']) ?></textarea>
                </div>
                
                <div style="margin-top: 30px;">
                    <button type="submit" name="update_customer" class="btn-submit">Update Customer</button>
                    <a href="manage_customers.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2025 CleanCare. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>