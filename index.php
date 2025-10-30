<?php
// Start the session for checking login status
session_start();

// Determine if the user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? htmlspecialchars($_SESSION['user_name']) : '';
$user_role = $is_logged_in ? htmlspecialchars($_SESSION['user_role']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CleanCare - Professional Maid Services</title>
    <!-- Assuming your style.css is in a 'css' folder relative to this file's location -->
    <link rel="stylesheet" href="frontend/css/style.css"> 
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Apply Inter font */
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <div class="logo-container">
                    <div class="logo-icon">
                        <svg viewBox="0 0 60 60" width="45" height="45">
                            <!-- Circle background -->
                            <circle cx="30" cy="30" r="28" fill="#2c5aa0"/>
                            
                            <!-- House shape -->
                            <path d="M 20 32 L 30 22 L 40 32 L 40 42 L 20 42 Z" fill="white"/>
                            <path d="M 18 32 L 30 20 L 42 32" stroke="white" stroke-width="2" fill="none" stroke-linecap="round"/>
                            
                            <!-- Door -->
                            <rect x="27" y="36" width="6" height="6" fill="#2c5aa0"/>
                            
                            <!-- Sparkles (cleaning symbols) -->
                            <circle cx="36" cy="26" r="1.5" fill="#FFC107"/>
                            <path d="M 36 23 L 36 29 M 33 26 L 39 26" stroke="#FFC107" stroke-width="1.5" stroke-linecap="round"/>
                            
                            <circle cx="24" cy="28" r="1" fill="#FFC107"/>
                            <path d="M 24 26 L 24 30 M 22 28 L 26 28" stroke="#FFC107" stroke-width="1" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="logo-text">
                        <h1>CleanCare</h1>
                        <p class="tagline">Trusted Maid Services</p>
                    </div>
                </div>
            </div>
            <ul class="nav-menu">
                <li><a href="#index" class="active">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="#agents">Agents</a></li>
                <li><a href="#reviews">Reviews</a></li>
                <li><a href="#contact">Contact</a></li>
                <?php if ($is_logged_in): ?>
                    <!-- Show Dashboard link if logged in -->
                     <?php
                    $dashboard_link = 'dashboards/client.php';
                    if ($user_role === 'admin') {
                        $dashboard_link = 'dashboards/cleaner.php';
                    } elseif ($user_role === 'cleaner') {
                        $dashboard_link = 'dashboards/cleaner.php';
                    }
                     ?>
                    <li><a href="dashboard_link ?">Dashboard</a></li>
                <?php endif; ?>
            </ul>
            <div class="nav-buttons">
                <?php if ($is_logged_in): ?>
                    <!-- Show welcome message and logout button if logged in -->
                    <span class="text-sm font-semibold text-gray-700 mr-4 hidden md:inline">
                        Welcome, <?= $user_name ?> (<?= ucfirst($user_role) ?>)
                    </span>
                    <a href="backend/logout.php" class="btn-primary">Log Out</a>
                <?php else: ?>
                    <!-- Show Sign In/Up buttons if not logged in -->
                    <a href="login.php" class="btn-outline">Sign In</a>
                    <a href="register.php" class="btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section with Background -->
    <section id="index" class="hero-main">
        <div class="hero-overlay">
            <div class="hero-content">
                <h1 class="hero-title">Welcome to CleanCare</h1>
                <p class="hero-subtitle">Connecting you with trusted, professional household services</p>
                <p class="hero-description">We provide reliable, skilled maids to help busy families maintain a clean and comfortable home</p>
                <div class="hero-actions">
                    <a href="<?= $is_logged_in ? $dashboard_link : 'login.php' ?>" class="btn-large btn-primary">
                        <?= $is_logged_in ? 'Go to Dashboard' : 'Get Started' ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission Section -->
    <section id="about" class="mission-section">
        <div class="container">
            <div class="mission-content">
                <div class="mission-text">
                    <h2>Our Mission</h2>
                    <p>At CleanCare, our mission is to provide reliable, trustworthy, and professional household services that give families peace of mind. We carefully vet and train every maid to ensure the highest standards of service.</p>
                    <p>Founded in 2020 by Sarah Martinez, a busy mother who struggled to find reliable household help, CleanCare was born from the need to connect families with skilled professionals they can trust.</p>
                </div>
                <div class="mission-stats">
                    <div class="stat-card">
                        <h3>500+</h3>
                        <p>Happy Families</p>
                    </div>
                    <div class="stat-card">
                        <h3>150+</h3>
                        <p>Professional Maids</p>
                    </div>
                    <div class="stat-card">
                        <h3>10,000+</h3>
                        <p>Services Completed</p>
                    </div>
                    <div class="stat-card">
                        <h3>4.9★</h3>
                        <p>Average Rating</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Values -->
    <section class="values-section">
        <div class="container">
            <h2 class="section-title">Our Core Values</h2>
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">🛡️</div>
                    <h3>Trustworthy</h3>
                    <p>Background-checked and verified professionals you can rely on</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">⭐</div>
                    <h3>Professional</h3>
                    <p>Trained experts delivering high-quality service every time</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">💼</div>
                    <h3>Reliable</h3>
                    <p>Consistent, on-time service you can depend on</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">💚</div>
                    <h3>Caring</h3>
                    <p>We treat your home with the same care as our own</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <h2 class="section-title">How It Works</h2>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h3>Create Account</h3>
                    <p>Sign up in minutes and tell us about your needs</p>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h3>Browse Agents</h3>
                    <p>View profiles, ratings, and availability of our verified maids</p>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h3>Book Service</h3>
                    <p>Select your preferred date, time, and service type</p>
                </div>
                <div class="step-card">
                    <div class="step-number">4</div>
                    <h3>Relax & Enjoy</h3>
                    <p>Sit back while our professionals take care of everything</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Preview -->
    <section id="services" class="services-section">
        <div class="container">
            <h2 class="section-title">Our Services</h2>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">🏠</div>
                    <h3>Regular Cleaning</h3>
                    <p>Weekly or bi-weekly cleaning to maintain your home</p>
                    <p class="service-price">From R500</p>
                </div>
                <div class="service-card featured">
                    <div class="popular-badge">Most Popular</div>
                    <div class="service-icon">✨</div>
                    <h3>Deep Cleaning</h3>
                    <p>Thorough top-to-bottom cleaning for your entire home</p>
                    <p class="service-price">From R1,200</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">📦</div>
                    <h3>Move In/Out</h3>
                    <p>Complete cleaning for moving transitions</p>
                    <p class="service-price">From R1,500</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">🏢</div>
                    <h3>Office Cleaning</h3>
                    <p>Professional commercial space cleaning</p>
                    <p class="service-price">From R800</p>
                </div>
            </div>
            <div class="text-center">
                <a href="backend/service.php" class="btn-primary">View All Services</a>
            </div>
        </div>
    </section>

    <!-- Featured Agents Section -->
    <section class="agents-section" id="agents">
        <div class="container">
            <h2 class="section-title">Meet Our Professional Maids</h2>
            <p class="section-subtitle">All agents are background-checked, trained, and highly rated</p>
            <div class="agents-grid">
                <div class="agent-card">
                    <div class="agent-photo">
                        <div class="agent-placeholder">👩</div>
                    </div>
                    <h3>Sarah Johnson</h3>
                    <div class="agent-rating">⭐⭐⭐⭐⭐ 4.9</div>
                    <p class="agent-experience">5 years experience</p>
                    <p class="agent-specialty">Specializes in deep cleaning</p>
                    <a href="<?= $is_logged_in ? 'dashboards/client.php' : 'register.php' ?>" class="btn-small">Book Now</a>
                </div>
                <div class="agent-card">
                    <div class="agent-photo">
                        <div class="agent-placeholder">👩</div>
                    </div>
                    <h3>Mary Williams</h3>
                    <div class="agent-rating">⭐⭐⭐⭐⭐ 4.8</div>
                    <p class="agent-experience">7 years experience</p>
                    <p class="agent-specialty">Expert in office cleaning</p>
                    <a href="<?= $is_logged_in ? 'dashboards/client.php' : 'register.php' ?>" class="btn-small">Book Now</a>
                </div>
                <div class="agent-card">
                    <div class="agent-photo">
                        <div class="agent-placeholder">👩</div>
                    </div>
                    <h3>Linda Brown</h3>
                    <div class="agent-rating">⭐⭐⭐⭐⭐ 5.0</div>
                    <p class="agent-experience">3 years experience</p>
                    <p class="agent-specialty">Regular home maintenance</p>
                    <a href="<?= $is_logged_in ? 'dashboards/client.php' : 'register.php' ?>" class="btn-small">Book Now</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Reviews Section -->
    <section class="reviews-section" id="reviews">
        <div class="container">
            <h2 class="section-title">What Our Clients Say</h2>
            <div class="reviews-grid">
                <div class="review-card">
                    <div class="review-stars">⭐⭐⭐⭐⭐</div>
                    <p class="review-text">"CleanCare has been a lifesaver! Sarah comes every week and does an amazing job. My home has never looked better."</p>
                    <p class="review-author">- Jennifer M., Cape Town</p>
                </div>
                <div class="review-card">
                    <div class="review-stars">⭐⭐⭐⭐⭐</div>
                    <p class="review-text">"Professional, reliable, and trustworthy. I highly recommend their services to anyone looking for quality cleaning."</p>
                    <p class="review-author">- David K., Johannesburg</p>
                </div>
                <div class="review-card">
                    <div class="review-stars">⭐⭐⭐⭐⭐</div>
                    <p class="review-text">"The booking process was so easy and Mary was fantastic. Will definitely be using CleanCare again!"</p>
                    <p class="review-author">- Lisa P., Durban</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Ready to Experience CleanCare?</h2>
            <p>Join hundreds of satisfied families today</p>
            <a href="<?= $is_logged_in ? $dashboard_link : 'login.php' ?>" class="btn-large btn-white">
                <?= $is_logged_in ? 'Manage Your Bookings' : 'Get Started Now' ?>
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3>CleanCare</h3>
                    <p>Trusted maid services connecting busy families with professional household help.</p>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="#about">About Us</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="#agents">Our Agents</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Services</h4>
                    <ul>
                        <li><a href="#services">Regular Cleaning</a></li>
                        <li><a href="#services">Deep Cleaning</a></li>
                        <li><a href="#services">Office Cleaning</a></li>
                        <li><a href="#services">Move In/Out</a></li>
                    </ul>
                </div>
                <div id="contact" class="footer-col">
                    <h4>Contact Us</h4>
                    <p>📧 info@cleancare.co.za</p>
                    <p>📞 +27 21 123 4567</p>
                    <p>📍 Cape Town, South Africa</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 CleanCare Maids System. All rights reserved.</p>
                <p>Developed by Group 54 - Richfield Graduate Institute of Technology</p>
            </div>
        </div>
    </footer>

    <script src="frontend/js/main.js"></script>
</body>
</html>
