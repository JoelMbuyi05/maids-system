document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registerForm');
    const errorMessageDiv = document.getElementById('errorMessage');
    const successMessageDiv = document.getElementById('successMessage');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');

    // Helper function to clear and display messages
    const displayMessage = (element, message, isSuccess = false) => {
        errorMessageDiv.style.display = 'none';
        successMessageDiv.style.display = 'none';
        
        element.textContent = message;
        element.style.display = 'block';
        element.focus(); // Good for accessibility
    };

    form.addEventListener('submit', async (e) => {
        e.preventDefault(); // Stop the default form submission

        // Clear previous messages
        /*displayMessage(errorMessageDiv, '', false);
        displayMessage(successMessageDiv, '', true);*/

        errorMessageDiv.style.display = 'none';
        successMessageDiv.style.display = 'none';

        // --- Frontend Validation Check ---
        if (passwordInput.value !== confirmPasswordInput.value) {
            displayMessage(errorMessageDiv, 'Passwords do not match. Please try again.');
            return;
        }
        if (passwordInput.value.length < 6) {
            displayMessage(errorMessageDiv, 'Password must be at least 6 characters long.');
            return;
        }

        // --- Data Submission ---
        
        // 1. Collect form data
        //const formData = new FormData(form);

        const submitButton = form.querySelector('button[type="submit"]');

        try {
            // Disable button and change text while processing
            submitButton.disabled = true;
            submitButton.textContent = 'Processing...';
            
            // 2. Send data to the PHP backend
            // NOTE: The path is relative to the HTML file (register.html)
            const response = await fetch("../../backend/register_process.php", {
                method: 'POST',
                body: formData
            });

            // 3. Handle the JSON response from PHP
            const result = await response.json();

            if (result.success) {
                displayMessage(successMessageDiv, result.message, true);
                form.reset(); // Clear the form on successful registration
                
                // Optional: Redirect the user to the login page after a short delay
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 2000); 

            } else {
                // Display error message from the PHP script
                displayMessage(errorMessageDiv, result.message);
            }

        } catch (error) {
            console.error('Registration Error:', error);
            displayMessage(errorMessageDiv, 'A network error occurred. Please check your connection.');
        } finally {
            // Re-enable the button regardless of success/failure
            submitButton.disabled = false;
            submitButton.textContent = 'Create Account';
        }
    });
});