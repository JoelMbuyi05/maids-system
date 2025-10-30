<?php
// --- PHP Form Handling Logic ---
$form_message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic data retrieval and trimming
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $userType = $_POST['userType'] ?? '';

    // Simple Server-Side Validation
    if ($password !== $confirmPassword) {
        $form_message = 'Error: Passwords do not match. Please ensure both passwords are the same.';
        $message_type = 'error';
    } 
    elseif (empty($fullName) || empty($email) || empty($password) || empty($userType)) {
         $form_message = 'Error: Please fill out all required fields.';
        $message_type = 'error';
    }
    // Simulate Successful Registration
    else {
        // In a real application, you would perform:
        // 1. Data Sanitization
        // 2. Password Hashing (e.g., password_hash($password, PASSWORD_DEFAULT))
        // 3. Database Insertion
        // 4. Session/Cookie setup and redirect
        
        $form_message = "Success! Account created for **" . htmlspecialchars($fullName) . "** as a **" . htmlspecialchars($userType) . "**. You can now proceed to log in.";
        $message_type = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - CleanCare</title>
    <!-- Load Tailwind CSS for modern, responsive styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom Font Configuration */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
        .auth-body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="auth-body">

    <!-- Register Section -->
    <section class="auth-section-centered">
        <!-- Container for the form and brand -->
        <div class="w-full max-w-lg mx-auto">

            <!-- Brand Header -->
            <div class="text-center mb-6">
                <div class="logo-icon-small mb-2">
                    <!-- Embedded SVG Logo -->
                    <svg viewBox="0 0 60 60" width="60" height="60">
                        <circle cx="30" cy="30" r="28" fill="#2c5aa0"/>
                        <path d="M 20 32 L 30 22 L 40 32 L 40 42 L 20 42 Z" fill="white"/>
                        <path d="M 18 32 L 30 20 L 42 32" stroke="white" stroke-width="2" fill="none" stroke-linecap="round"/>
                        <rect x="27" y="36" width="6" height="6" fill="#2c5aa0"/>
                        <circle cx="36" cy="26" r="1.5" fill="#FFC107"/>
                        <path d="M 36 23 L 36 29 M 33 26 L 39 26" stroke="#FFC107" stroke-width="1.5" fill="none" stroke-linecap="round"/>
                        <circle cx="24" cy="28" r="1" fill="#FFC107"/>
                        <path d="M 24 26 L 24 30 M 22 28 L 26 28" stroke="#FFC107" stroke-width="1" fill="none" stroke-linecap="round"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">CleanCare</h1>
                <p class="text-gray-500 mt-1">Create your account</p>
            </div>

            <!-- Form Card -->
            <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-xl border border-gray-100">
                <!-- Action points to itself for PHP processing -->
                <form id="registerForm" action="" method="POST"> 

                    <!-- PHP Message Display -->
                    <?php if ($form_message): ?>
                        <div id="phpMessage" class="text-sm p-3 rounded-lg mb-4 <?php echo $message_type === 'error' ? 'bg-red-100 text-red-700 border border-red-300' : 'bg-green-100 text-green-700 border border-green-300'; ?>">
                            <?php echo $form_message; ?>
                        </div>
                    <?php endif; ?>
                    <!-- End PHP Message Display -->
                    
                    <div class="form-row">
                        <input type="text" id="fullName" name="fullName" placeholder="Full name" required />
                    </div>

                    <div class="form-row">
                        <input type="email" id="email" name="email" placeholder="Email address" required />
                    </div>

                    <div class="form-row">
                        <input type="tel" id="phone" name="phone" placeholder="Phone number (e.g., 0712345678)" required />
                    </div>

                    <div class="form-row">
                        <textarea id="address" name="address" rows="3" placeholder="Your address" required></textarea>
                    </div>

                    <div class="form-row relative">
                        <select id="userType" name="userType" required class="pr-10">
                            <option value="">-- Select role --</option>
                            <option value="client">Book Cleaning Services (Client)</option>
                            <option value="cleaner">Work as a Cleaner (Cleaner)</option>
                        </select>
                         <svg class="absolute right-3 top-1/2 -mt-2 w-4 h-4 text-gray-400 pointer-events-none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </div>

                    <!-- Responsive password fields -->
                    <div class="form-row-split grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <input type="password" id="password" name="password" placeholder="Password" minlength="6" required />
                        <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm password" required />
                    </div>

                    <label class="checkbox-label-compact flex items-start mb-6 text-sm text-gray-600">
                        <!-- We use mt-1 to align the checkbox with the text line-height -->
                        <input type="checkbox" id="terms" name="terms" required class="mt-1" />
                        <span class="ml-2">I agree to the <a href="#" class="text-[var(--primary-color)] hover:underline font-medium">Terms & Conditions</a></span>
                    </label>

                    <!-- Client-Side Message Box (used for password mismatch check) -->
                    <div id="errorMessage" class="text-sm p-3 bg-red-100 text-red-700 border border-red-300 rounded-lg mb-4" style="display:none;"></div>

                    <button type="submit" class="w-full py-3 bg-[var(--primary-color)] text-white font-semibold rounded-lg shadow-lg hover:bg-[#204a80] transition duration-300 ease-in-out">
                        Create Account
                    </button>

                    <div class="flex items-center my-6">
                        <div class="flex-grow border-t border-gray-300"></div>
                        <span class="flex-shrink mx-4 text-gray-500 text-sm">or</span>
                        <div class="flex-grow border-t border-gray-300"></div>
                    </div>
                    
                    <!-- Fixed footer text -->
                    <div class="text-center text-gray-600">
                        <p>Already have an account? 
                            <a href="login.html" class="text-[var(--primary-color)] hover:underline font-medium">Sign In</a>
                        </p>
                    </div>
                </form>
            </div>

        </div>
    </section>

    <!-- Client-Side Validation Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('registerForm');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirmPassword');
            const errorMessage = document.getElementById('errorMessage');
            const phpMessage = document.getElementById('phpMessage');

            // Function to show client-side validation errors
            function showClientError(message) {
                if (phpMessage) phpMessage.style.display = 'none'; // Hide PHP message if client error occurs
                errorMessage.textContent = message;
                errorMessage.style.display = 'block';
            }

            form.addEventListener('submit', (e) => {
                errorMessage.style.display = 'none'; // Clear previous client-side errors

                // Client-Side Password Match Validation
                if (passwordInput.value !== confirmPasswordInput.value) {
                    e.preventDefault();
                    showClientError('Client Error: Passwords do not match. Please correct the confirmation password.');
                    confirmPasswordInput.focus();
                    return;
                }
            });
        });
    </script>
</body>
</html>
