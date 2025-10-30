// feedback.js - Feedback and rating functionality

document.addEventListener('DOMContentLoaded', function() {
    // Initialize star rating
    const starRatings = document.querySelectorAll('.star-rating');
    starRatings.forEach(initStarRating);
    
    // Handle feedback form submission
    const feedbackForm = document.getElementById('feedbackForm');
    if (feedbackForm) {
        feedbackForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitFeedback();
        });
    }
    
    // Handle feedback buttons
    const feedbackButtons = document.querySelectorAll('.btn-leave-feedback');
    feedbackButtons.forEach(button => {
        button.addEventListener('click', function() {
            const bookingId = this.dataset.bookingId;
            showFeedbackModal(bookingId);
        });
    });
});

// Initialize star rating component
function initStarRating(container) {
    const stars = container.querySelectorAll('.star');
    const ratingInput = container.querySelector('input[name="rating"]');
    
    stars.forEach((star, index) => {
        star.addEventListener('click', function() {
            const rating = index + 1;
            ratingInput.value = rating;
            updateStars(stars, rating);
        });
        
        star.addEventListener('mouseenter', function() {
            const rating = index + 1;
            updateStars(stars, rating);
        });
    });
    
    container.addEventListener('mouseleave', function() {
        const currentRating = parseInt(ratingInput.value) || 0;
        updateStars(stars, currentRating);
    });
}

// Update star display
function updateStars(stars, rating) {
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('active');
            star.textContent = '★';
        } else {
            star.classList.remove('active');
            star.textContent = '☆';
        }
    });
}

// Show feedback modal
function showFeedbackModal(bookingId) {
    const modal = document.getElementById('feedbackModal');
    if (modal) {
        document.getElementById('feedback_booking_id').value = bookingId;
        modal.style.display = 'block';
    } else {
        // If no modal, redirect to feedback page
        window.location.href = `feedback.php?booking_id=${bookingId}`;
    }
}

// Submit feedback
function submitFeedback() {
    const form = document.getElementById('feedbackForm');
    const formData = new FormData(form);
    formData.append('action', 'submit');
    
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';
    
    fetch('../backend/feedback_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage(data.message);
            form.reset();
            const modal = document.getElementById('feedbackModal');
            if (modal) modal.style.display = 'none';
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showErrorMessage(data.message);
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Feedback';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('An error occurred. Please try again.');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Feedback';
    });
}

// Close modal
function closeFeedbackModal() {
    const modal = document.getElementById('feedbackModal');
    if (modal) modal.style.display = 'none';
}