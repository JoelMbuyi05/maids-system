<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions - CleanCare</title>
    <link rel="stylesheet" href="../frontend/css/style.css">
    <link rel="stylesheet" href="../frontend/css/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Specific Styles for the Terms Page */
        .content-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
        }
        
        .header-with-back {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .back-arrow-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #2c5aa0;
            margin-right: 15px;
            font-size: 1.5rem;
            transition: opacity 0.2s;
        }
        
        .back-arrow-link:hover {
            opacity: 0.8;
        }
        
        .terms-title {
            font-size: 2rem;
            color: #1c1e21;
            margin: 0;
            font-weight: 600;
        }
        
        .terms-subtitle {
            font-size: 0.9rem;
            color: #606770;
            margin: 0 0 20px 0;
        }

        .terms-section h3 {
            color: #2c5aa0;
            font-size: 1.25rem;
            margin-top: 25px;
            border-bottom: 1px solid #e4e6eb;
            padding-bottom: 5px;
        }

        .terms-section p, .terms-section ul {
            font-size: 1rem;
            line-height: 1.6;
            color: #444;
            margin-bottom: 15px;
        }

        .terms-section li {
            margin-bottom: 8px;
            margin-left: 20px;
        }

        .terms-section strong {
            color: #2c5aa0;
            font-weight: 600;
        }
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
                        <p class="tagline">Terms & Conditions</p>
                    </div>
                </div>
            </div>
            <ul class="nav-menu">
                <li><a href="../index.php">Home</a></li>
                <li><a href="../index.php#about">About</a></li>
                <li><a href="../index.php#contact">Contact</a></li>
            </ul>
            <div class="nav-buttons">
                <?php if ($is_logged_in): ?>
                    <a href="../dashboards/<?= $_SESSION['user_role'] === 'admin' ? 'admin' : ($_SESSION['user_role'] === 'cleaner' ? 'cleaner' : 'client') ?>.php" class="btn-primary">Dashboard</a>
                <?php else: ?>
                    <a href="../login.php" class="btn-outline">Sign In</a>
                    <a href="../register.php" class="btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Terms Content -->
    <section style="padding: 60px 0; background: #f8f9fa; min-height: calc(100vh - 80px);">
        <div class="container">
            <div class="content-container">
                
                <div class="header-with-back">
                    <a href="../<?= $is_logged_in ? 'index.php' : 'register.php' ?>" class="back-arrow-link" aria-label="Go Back">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </a>
                    <h1 class="terms-title">Terms & Conditions</h1>
                </div>
                
                <p class="terms-subtitle">Last updated: <strong>October 31, 2025</strong></p>

                <hr style="border: 0; height: 1px; background: #dadde1; margin-top: 10px; margin-bottom: 25px;">

                <div class="terms-section">
                    <h3>1. Acceptance of Terms</h3>
                    <p>By registering for or using the CleanCare platform, you agree to these Terms & Conditions ("Terms"). If you do not agree to these Terms, please do not use our services.</p>
                </div>

                <div class="terms-section">
                    <h3>2. CleanCare Service Description</h3>
                    <p>CleanCare operates a <strong>platform connecting Clients</strong> (those seeking cleaning services) with <strong>Cleaners</strong> (independent contractors providing services). CleanCare facilitates booking, payment processing, communication, and scheduling but <strong>does not directly employ Cleaners</strong> or perform the cleaning services itself.</p>
                </div>
                
                <div class="terms-section">
                    <h3>3. User Responsibilities and Acceptable Use</h3>
                    <p>You agree not to use the platform for unlawful, offensive, fraudulent, or abusive activities. Specifically, users must:</p>
                    <ul>
                        <li><strong>Clients:</strong> Provide accurate service location, access details, and ensure a safe working environment for the Cleaner.</li>
                        <li><strong>Cleaners:</strong> Maintain professionalism, arrive punctually, and perform services to an agreed-upon standard.</li>
                        <li><strong>All Users:</strong> Refrain from attempting to circumvent the platform to book services directly (poaching).</li>
                        <li>Do not upload or share content (e.g., photos of homes) without proper consent.</li>
                    </ul>
                </div>

                <div class="terms-section">
                    <h3>4. Payments, Fees, and Cancellations</h3>
                    <ul>
                        <li><strong>Payment Processing:</strong> All payments for services booked through the platform must be processed via CleanCare's payment system.</li>
                        <li><strong>Service Fees:</strong> CleanCare deducts a service fee (commission) from the Cleaner's pay, which is outlined in the Cleaner Agreement.</li>
                        <li><strong>Cancellations:</strong> Bookings may be cancelled up to 6 hours before the scheduled time without penalty. Late cancellations or no-shows may incur fees.</li>
                        <li><strong>Refund Policy:</strong> Refunds are issued at CleanCare's discretion based on service quality disputes and documented evidence.</li>
                    </ul>
                </div>

                <div class="terms-section">
                    <h3>5. Content, Feedback, and Reviews</h3>
                    <p>Users may submit reviews, ratings, and feedback. By submitting these, you grant CleanCare a permanent, worldwide, royalty-free license to use, publish, and display them in connection with the platform services. Reviews must be honest, relevant, and non-abusive.</p>
                    <p>CleanCare reserves the right to remove reviews that violate our community guidelines or contain offensive content.</p>
                </div>
                
                <div class="terms-section">
                    <h3>6. Privacy and Data Protection</h3>
                    <p>CleanCare collects and processes personal data in accordance with South African data protection laws, including the Protection of Personal Information Act (POPIA). Your information is used solely to:</p>
                    <ul>
                        <li>Facilitate service bookings and communication</li>
                        <li>Process payments securely</li>
                        <li>Improve our platform and customer experience</li>
                        <li>Comply with legal obligations</li>
                    </ul>
                    <p>We do not sell your personal information to third parties. For more details, please refer to our Privacy Policy.</p>
                </div>

                <div class="terms-section">
                    <h3>7. Account Security</h3>
                    <p>You are responsible for maintaining the confidentiality of your account credentials. Any activities that occur under your account are your responsibility. If you suspect unauthorized access, contact us immediately at <a href="mailto:support@cleancare.co.za" style="color: #2c5aa0;">support@cleancare.co.za</a>.</p>
                </div>
                
                <div class="terms-section">
                    <h3>8. Suspension and Termination</h3>
                    <p>We may immediately suspend or terminate your account for any violation of these Terms, including:</p>
                    <ul>
                        <li>Failure to pay for services</li>
                        <li>Abusive behavior towards Cleaners or Clients</li>
                        <li>Fraudulent activity or misrepresentation</li>
                        <li>Violation of applicable laws</li>
                    </ul>
                    <p>Users may deactivate their account at any time through account settings. Upon termination, you may request data deletion subject to legal and operational requirements.</p>
                </div>

                <div class="terms-section">
                    <h3>9. Limitation of Liability and Disclaimers</h3>
                    <p>The CleanCare platform is provided "as is" without warranties of any kind. While we strive for reliable service, CleanCare <strong>cannot guarantee the quality of work performed by Cleaners</strong> or that the service will be uninterrupted or error-free.</p>
                    <p>CleanCare is <strong>not liable for damages</strong> arising from:</p>
                    <ul>
                        <li>The Cleaner-Client relationship or services provided</li>
                        <li>Property damage or personal injury during service delivery</li>
                        <li>Disputes between Clients and Cleaners</li>
                        <li>Technical issues or platform downtime</li>
                    </ul>
                    <p>Our total liability for any claim shall not exceed the total amount paid by you in the six months prior to the incident.</p>
                </div>

                <div class="terms-section">
                    <h3>10. Insurance and Background Checks</h3>
                    <p>All Cleaners on the CleanCare platform undergo basic background verification. However, Clients are encouraged to secure appropriate insurance for their property. CleanCare recommends that Cleaners maintain professional liability insurance.</p>
                </div>

                <div class="terms-section">
                    <h3>11. Dispute Resolution</h3>
                    <p>Any disputes between Clients and Cleaners should first be reported to CleanCare support. We will attempt to mediate in good faith. If a resolution cannot be reached, disputes shall be resolved through arbitration in Cape Town, South Africa, in accordance with South African law.</p>
                </div>

                <div class="terms-section">
                    <h3>12. Intellectual Property</h3>
                    <p>All content on the CleanCare platform, including but not limited to logos, text, graphics, software, and design, is protected by copyright and trademark laws. Unauthorized use is strictly prohibited.</p>
                </div>

                <div class="terms-section">
                    <h3>13. Modifications to Terms</h3>
                    <p>We reserve the right to modify these Terms at any time. Significant changes will be communicated via email or platform notification. The updated Terms will be posted on this page with a revised "Last Updated" date. By continuing to use CleanCare after modifications, you accept the new Terms.</p>
                </div>

                <div class="terms-section">
                    <h3>14. Governing Law</h3>
                    <p>These Terms are governed by and construed in accordance with the laws of South Africa. Any legal proceedings shall be brought exclusively in the courts of Cape Town, Western Cape.</p>
                </div>

                <div class="terms-section">
                    <h3>15. Contact Information</h3>
                    <p>For questions regarding these Terms & Conditions, please contact:</p>
                    <p style="margin-left: 20px;">
                        <strong>CleanCare Support</strong><br>
                        Email: <a href="mailto:info@cleancare.co.za" style="color: #2c5aa0;">info@cleancare.co.za</a><br>
                        Phone: +27 21 123 4567<br>
                        Address: Cape Town, South Africa
                    </p>
                </div>

                <div style="background: #f0f8ff; padding: 25px; border-radius: 8px; margin-top: 40px; text-align: center; border-left: 4px solid #2c5aa0;">
                    <p style="margin: 0; font-weight: 600; color: #2c5aa0; font-size: 1.05rem;">
                        ✅ By using CleanCare, you acknowledge that you have read, understood, and agree to be bound by these Terms & Conditions.
                    </p>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <?php if (!$is_logged_in): ?>
                        <a href="../register.php" class="btn-primary" style="display: inline-block; text-decoration: none; margin-right: 15px;">I Agree - Create Account</a>
                    <?php endif; ?>
                    <a href="../index.php" class="btn-outline" style="display: inline-block; text-decoration: none;">Back to Home</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2025 CleanCare Maids System. All rights reserved.</p>
                <p>Developed by Group 54 - Richfield Graduate Institute of Technology</p>
            </div>
        </div>
    </footer>

    <script src="../frontend/js/main.js"></script>
</body>
</html>