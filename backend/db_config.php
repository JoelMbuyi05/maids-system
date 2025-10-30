<?php
// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');
define('DB_NAME', 'cleaning');
define('DB_USER', 'root');
define('DB_PASS', ''); // The password is often empty for local development

try {
    // Create a PDO instance (secure connection)
    $pdo = new PDO(
        "mysql:host=" .DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME,
        DB_USER, 
        DB_PASS
    );

    // Set the PDO error mode to exception for better error handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Optional: You can echo success on first run to test the connection, but remove it later
    // echo "Database connection successful!"; 

} catch (PDOException $e) {
    // Connection failed: display error and stop script execution
    die("Database connection failed: " . $e->getMessage());
}
?>