<?php
// add_users.php - Run this ONCE to add demo users
require_once 'backend/db_config.php';

echo "<style>
body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
.success { color: green; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
.btn { background: #2c5aa0; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0; font-weight: bold; }
</style>";

echo "<h1>🚀 Adding Demo Users to Database</h1>";

try {
    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = 'admin@cleancare.com'");
    $stmt->execute();
    
    if ($stmt->fetch()) {
        echo "<div class='error'>❌ Demo users already exist! No need to run this again.</div>";
        echo "<p><a href='login.php' class='btn'>Go to Login Page</a></p>";
        exit;
    }
    
    // Hash the passwords
    $admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
    $cleaner_pass = password_hash('cleaner123', PASSWORD_DEFAULT);
    $customer_pass = password_hash('customer123', PASSWORD_DEFAULT);
    
    // Insert admin
    $sql = "INSERT INTO users (name, email, phone, address, password, role) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute(['System Admin', 'admin@cleancare.com', '0211234567', 'Cape Town, South Africa', $admin_pass, 'admin']);
    echo "<div class='success'>✅ Added: <strong>admin@cleancare.com</strong> / admin123</div>";
    
    // Insert cleaners
    $stmt->execute(['Sarah Johnson', 'sarah@cleancare.com', '0721234567', 'Rondebosch, Cape Town', $cleaner_pass, 'cleaner']);
    echo "<div class='success'>✅ Added: <strong>sarah@cleancare.com</strong> / cleaner123</div>";
    
    $stmt->execute(['Mary Williams', 'mary@cleancare.com', '0731234567', 'Claremont, Cape Town', $cleaner_pass, 'cleaner']);
    echo "<div class='success'>✅ Added: <strong>mary@cleancare.com</strong> / cleaner123</div>";
    
    $stmt->execute(['Linda Brown', 'linda@cleancare.com', '0741234567', 'Wynberg, Cape Town', $cleaner_pass, 'cleaner']);
    echo "<div class='success'>✅ Added: <strong>linda@cleancare.com</strong> / cleaner123</div>";
    
    // Insert customer
    $stmt->execute(['John Doe', 'john@example.com', '0761234567', '123 Main St, Cape Town', $customer_pass, 'customer']);
    echo "<div class='success'>✅ Added: <strong>john@example.com</strong> / customer123</div>";
    
    echo "<hr>";
    echo "<h2 style='color: green;'>🎉 SUCCESS! Demo users added to database.</h2>";
    echo "<p style='font-size: 18px;'>You can now login with any of these accounts.</p>";
    
    // Check total users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    echo "<p style='font-size: 16px;'>Total users in database: <strong style='color: #2c5aa0;'>$count</strong></p>";
    
    echo "<a href='login.php' class='btn'>Go to Login Page</a>";
    echo " <a href='index.php' class='btn' style='background: #28a745;'>Go to Homepage</a>";
    
} catch (PDOException $e) {
    echo "<div class='error'><h3>❌ ERROR:</h3>";
    echo "<p>" . $e->getMessage() . "</p></div>";
}
?>