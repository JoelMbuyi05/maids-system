<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services - CleanCare</title>
    <link rel="stylesheet" href="../frontend/css/style.css">
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
                        <p class="tagline">Our Services</p>
                    </div>
                </div>
            </div>
            <ul class="nav-menu">
                <li><a href="../index.php">Home</a></li>
                <li><a href="../index.php#about">About</a></li>
                <li><a href="#" class="active">Services</a></li>
                <li><a href="../index.php#contact">Contact</a></li>
            </ul>
            <div class="nav-buttons">
                <?php if ($is_logged_in): ?>
                    <a href="../dashboards/client.php" class="btn-primary">Dashboard</a>
                <?php else: ?>
                    <a href="../login.php" class="btn-outline">Sign In</a>
                    <a href="../register.php" class="btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Services Content -->
    <section style="padding: 80px 0; background: #f8f9fa;">
        <div class="container">
            <h1 style="text-align: center; color: #2c5aa0; font-size: 3rem; margin-bottom: 20px;">Our Services</h1>
            <p style="text-align: center; color: #666; font-size: 1.2rem; margin-bottom: 60px; max-width: 800px; margin-left: auto; margin-right: auto;">
                Professional cleaning services tailored to your needs. All our services come with a satisfaction guarantee.
            </p>

            <div class="services-grid" style="margin-bottom: 60px;">
                <!-- Regular Cleaning -->
                <div class="service-card" style="background: white;">
                    <div class="service-icon">🏠</div>
                    <h3>Regular Cleaning</h3>
                    <p class="service-price">From R500</p>
                    <ul style="text-align: left; margin: 20px 0; padding-left: 20px; color: #666;">
                        <li>Dusting all surfaces</li>
                        <li>Vacuuming & mopping floors</li>
                        <li>Kitchen cleaning</li>
                        <li>Bathroom sanitization</li>
                        <li>Weekly or bi-weekly schedule</li>
                    </ul>
                    <a href="<?= $is_logged_in ? 'book_service.php' : '../login.php' ?>" class="btn-primary">Book Now</a>
                </div>

                <!-- Deep Cleaning -->
                <div class="service-card featured" style="background: white;">
                    <div class="popular-badge">Most Popular</div>
                    <div class="service-icon">✨</div>
                    <h3>Deep Cleaning</h3>
                    <p class="service-price">From R1,200</p>
                    <ul style="text-align: left; margin: 20px 0; padding-left: 20px; color: #666;">
                        <li>Complete house sanitization</li>
                        <li>Behind furniture cleaning</li>
                        <li>Appliance deep clean</li>
                        <li>Carpet & upholstery cleaning</li>
                        <li>Window washing (interior)</li>
                    </ul>
                    <a href="<?= $is_logged_in ? 'book_service.php' : '../login.php' ?>" class="btn-primary">Book Now</a>
                </div>

                <!-- Move In/Out -->
                <div class="service-card" style="background: white;">
                    <div class="service-icon">📦</div>
                    <h3>Move In/Out Cleaning</h3>
                    <p class="service-price">From R1,500</p>
                    <ul style="text-align: left; margin: 20px 0; padding-left: 20px; color: #666;">
                        <li>Empty property cleaning</li>
                        <li>All rooms & cupboards</li>
                        <li>Kitchen & appliances</li>
                        <li>Bathrooms & fixtures</li>
                        <li>Windows & doors</li>
                    </ul>
                    <a href="<?= $is_logged_in ? 'book_service.php' : '../login.php' ?>" class="btn-primary">Book Now</a>
                </div>

                <!-- Office Cleaning -->
                <div class="service-card" style="background: white;">
                    <div class="service-icon">🏢</div>
                    <h3>Office Cleaning</h3>
                    <p class="service-price">From R800</p>
                    <ul style="text-align: left; margin: 20px 0; padding-left: 20px; color: #666;">
                        <li>Workspace sanitization</li>
                        <li>Common areas cleaning</li>
                        <li>Restroom maintenance</li>
                        <li>Trash removal</li>
                        <li>After-hours service available</li>
                    </ul>
                    <a href="<?= $is_logged_in ? 'book_service.php' : '../login.php' ?>" class="btn-primary">Book Now</a>
                </div>

                <!-- Carpet Cleaning -->
                <div class="service-card" style="background: white;">
                    <div class="service-icon">🧼</div>
                    <h3>Carpet Cleaning</h3>
                    <p class="service-price">From R600</p>
                    <ul style="text-align: left; margin: 20px 0; padding-left: 20px; color: #666;">
                        <li>Steam cleaning</li>
                        <li>Stain removal</li>
                        <li>Odor elimination</li>
                        <li>Fast drying</li>
                        <li>Eco-friendly products</li>
                    </ul>
                    <a href="<?= $is_logged_in ? 'book_service.php' : '../login.php' ?>" class="btn-primary">Book Now</a>
                </div>

                <!-- Window Cleaning -->
                <div class="service-card" style="background: white;">
                    <div class="service-icon">🪟</div>
                    <h3>Window Cleaning</h3>
                    <p class="service-price">From R400</p>
                    <ul style="text-align: left; margin: 20px 0; padding-left: 20px; color: #666;">
                        <li>Interior & exterior</li>
                        <li>Streak-free finish</li>
                        <li>Frame & sill cleaning</li>
                        <li>High-rise capability</li>
                        <li>Safety guaranteed</li>
                    </ul>
                    <a href="<?= $is_logged_in ? 'book_service.php' : '../login.php' ?>" class="btn-primary">Book Now</a>
                </div>
            </div>

            <!-- Why Choose Us -->
            <div style="background: white; padding: 60px 40px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); margin-top: 60px;">
                <h2 style="text-align: center; color: #2c5aa0; margin-bottom: 40px;">Why Choose CleanCare?</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
                    <div style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 15px;">✅</div>
                        <h4 style="color: #2c5aa0; margin-bottom: 10px;">100% Satisfaction Guarantee</h4>
                        <p style="color: #666;">We stand behind our work</p>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 15px;">🔒</div>
                        <h4 style="color: #2c5aa0; margin-bottom: 10px;">Insured & Bonded</h4>
                        <p style="color: #666;">Your property is protected</p>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 15px;">🌿</div>
                        <h4 style="color: #2c5aa0; margin-bottom: 10px;">Eco-Friendly Products</h4>
                        <p style="color: #666;">Safe for family & pets</p>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 15px;">⏰</div>
                        <h4 style="color: #2c5aa0; margin-bottom: 10px;">Flexible Scheduling</h4>
                        <p style="color: #666;">Book at your convenience</p>
                    </div>
                </div>
            </div>
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
</body>
</html>