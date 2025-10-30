// main.js - General JavaScript for site-wide functionality

document.addEventListener('DOMContentLoaded', function() {
    // Smooth scrolling for navigation links
    const navLinks = document.querySelectorAll('a[href^="#"]');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Mobile menu toggle (if you add mobile menu later)
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
        });
    }

    // Auto-hide flash messages after 5 seconds
    const flashMessages = document.querySelectorAll('.success-message, .error-message');
    flashMessages.forEach(message => {
        if (message.style.display === 'block') {
            setTimeout(() => {
                message.style.opacity = '0';
                setTimeout(() => {
                    message.style.display = 'none';
                }, 300);
            }, 5000);
        }
    });

    // Form validation enhancement
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = 'red';
                } else {
                    field.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
    });
});

// Show loading indicator
function showLoading(button) {
    button.disabled = true;
    button.innerHTML = '<span>Loading...</span>';
}

// Show success message
function showSuccessMessage(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'success-message';
    alertDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; padding: 15px 25px; background: #d4edda; color: #155724; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
    alertDiv.textContent = message;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.style.opacity = '0';
        setTimeout(() => alertDiv.remove(), 300);
    }, 3000);
}

// Show error message
function showErrorMessage(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'error-message';
    alertDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; padding: 15px 25px; background: #f8d7da; color: #721c24; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
    alertDiv.textContent = message;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.style.opacity = '0';
        setTimeout(() => alertDiv.remove(), 300);
    }, 3000);
}