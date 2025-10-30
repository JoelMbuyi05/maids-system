// booking.js - Booking form functionality

document.addEventListener('DOMContentLoaded', function() {
    const bookingForm = document.getElementById('bookingForm');
    
    if (bookingForm) {
        // Get available cleaners when date/time changes
        const dateInput = document.getElementById('booking_date');
        const timeInput = document.getElementById('booking_time');
        const cleanerSelect = document.getElementById('cleaner_id');
        
        if (dateInput && timeInput && cleanerSelect) {
            dateInput.addEventListener('change', updateAvailableCleaners);
            timeInput.addEventListener('change', updateAvailableCleaners);
        }
        
        // Handle form submission
        bookingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitBooking();
        });
    }
    
    // Handle booking cancellation
    const cancelButtons = document.querySelectorAll('.btn-cancel-booking');
    cancelButtons.forEach(button => {
        button.addEventListener('click', function() {
            const bookingId = this.dataset.bookingId;
            if (confirm('Are you sure you want to cancel this booking?')) {
                cancelBooking(bookingId);
            }
        });
    });
    
    // Handle booking actions (accept/reject/complete)
    const actionButtons = document.querySelectorAll('[data-booking-action]');
    actionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const action = this.dataset.bookingAction;
            const bookingId = this.dataset.bookingId;
            handleBookingAction(action, bookingId);
        });
    });
});

// Update available cleaners based on date/time
function updateAvailableCleaners() {
    const date = document.getElementById('booking_date').value;
    const time = document.getElementById('booking_time').value;
    const cleanerSelect = document.getElementById('cleaner_id');
    
    if (!date || !time) return;
    
    const formData = new FormData();
    formData.append('action', 'get_available_cleaners');
    formData.append('date', date);
    formData.append('time', time);
    
    fetch('../backend/booking_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cleanerSelect.innerHTML = '<option value="">-- Select Cleaner --</option>';
            data.cleaners.forEach(cleaner => {
                const option = document.createElement('option');
                option.value = cleaner.id;
                option.textContent = cleaner.name;
                cleanerSelect.appendChild(option);
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('Failed to fetch available cleaners');
    });
}

// Submit booking form
function submitBooking() {
    const form = document.getElementById('bookingForm');
    const formData = new FormData(form);
    formData.append('action', 'create');
    
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Processing...';
    
    fetch('../backend/booking_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage(data.message);
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showErrorMessage(data.message);
            submitBtn.disabled = false;
            submitBtn.textContent = 'Book Service';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('An error occurred. Please try again.');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Book Service';
    });
}

// Cancel booking
function cancelBooking(bookingId) {
    const formData = new FormData();
    formData.append('action', 'cancel');
    formData.append('booking_id', bookingId);
    
    fetch('../backend/booking_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage(data.message);
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showErrorMessage(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('An error occurred. Please try again.');
    });
}

// Handle booking actions (accept/reject/complete)
function handleBookingAction(action, bookingId) {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('booking_id', bookingId);
    
    fetch('../backend/booking_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage(data.message);
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showErrorMessage(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('An error occurred. Please try again.');
    });
}