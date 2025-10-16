// assets/script.js
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    initTooltips();
    
    // Initialize form validations
    initFormValidations();
    
    // Initialize animations
    initAnimations();
    
    // Initialize search functionality
    initSearchFunctionality();
});

// Tooltip initialization
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltipText = this.getAttribute('data-tooltip');
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = tooltipText;
            
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
            
            this._tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                document.body.removeChild(this._tooltip);
                this._tooltip = null;
            }
        });
    });
}

// Form validation initialization
function initFormValidations() {
    // Real-time password confirmation
    const confirmPasswordInputs = document.querySelectorAll('input[name*="confirm"]');
    
    confirmPasswordInputs.forEach(input => {
        input.addEventListener('input', function() {
            const passwordInput = document.querySelector('input[name="password"], input[name="new_password"], input[name="reg_password"]');
            if (passwordInput && passwordInput.value !== this.value) {
                this.setCustomValidity('Password tidak sama');
                this.classList.add('error');
            } else {
                this.setCustomValidity('');
                this.classList.remove('error');
            }
        });
    });
    
    // Email validation
    const emailInputs = document.querySelectorAll('input[type="email"]');
    
    emailInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (this.value && !emailRegex.test(this.value)) {
                this.setCustomValidity('Format email tidak valid');
                this.classList.add('error');
            } else {
                this.setCustomValidity('');
                this.classList.remove('error');
            }
        });
    });
    
    // Phone number validation (if needed)
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Remove non-digit characters
            this.value = this.value.replace(/\D/g, '');
            
            // Format phone number (Indonesian format)
            if (this.value.length > 4) {
                this.value = this.value.replace(/(\d{4})(\d{4})(\d+)/, '$1-$2-$3');
            }
        });
    });
}

// Animation initialization
function initAnimations() {
    // Fade in animation for cards
    const animateElements = document.querySelectorAll('.animal-card, .receipt, .alert');
    
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    animateElements.forEach(element => {
        observer.observe(element);
    });
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Search functionality
function initSearchFunctionality() {
    const searchInput = document.getElementById('search-location');
    const animalSelect = document.getElementById('search-animal');
    const dateInput = document.getElementById('search-dates');
    
    if (searchInput) {
        // Live search as user types
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch();
            }, 300);
        });
    }
    
    if (animalSelect) {
        animalSelect.addEventListener('change', performSearch);
    }
    
    if (dateInput) {
        // Initialize date picker
        dateInput.type = 'date';
        dateInput.min = new Date().toISOString().split('T')[0];
        dateInput.addEventListener('change', performSearch);
    }
}

// Perform search with current filters
function performSearch() {
    const location = document.getElementById('search-location')?.value.toLowerCase() || '';
    const animal = document.getElementById('search-animal')?.value || '';
    const date = document.getElementById('search-dates')?.value || '';
    
    const cards = document.querySelectorAll('.animal-card');
    let visibleCount = 0;
    
    cards.forEach(card => {
        let shouldShow = true;
        
        // Filter by animal type
        if (animal && card.dataset.animalId !== animal) {
            shouldShow = false;
        }
        
        // Filter by name/location
        if (location) {
            const animalName = card.querySelector('.animal-name').textContent.toLowerCase();
            if (!animalName.includes(location)) {
                shouldShow = false;
            }
        }
        
        // Show/hide with animation
        if (shouldShow) {
            card.style.display = 'block';
            card.style.opacity = '0';
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 50);
            visibleCount++;
        } else {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.display = 'none';
            }, 200);
        }
    });
    
    // Show no results message
    let noResultsMsg = document.getElementById('no-results-message');
    if (visibleCount === 0) {
        if (!noResultsMsg) {
            noResultsMsg = document.createElement('div');
            noResultsMsg.id = 'no-results-message';
            noResultsMsg.className = 'no-results';
            noResultsMsg.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #666;">
                    <div style="font-size: 48px; margin-bottom: 20px;">🔍</div>
                    <h3>Tidak ada hewan yang ditemukan</h3>
                    <p>Coba ubah filter pencarian Anda</p>
                    <button onclick="clearSearch()" class="btn btn-secondary" style="margin-top: 15px;">
                        Reset Pencarian
                    </button>
                </div>
            `;
            document.querySelector('.animals-grid').appendChild(noResultsMsg);
        }
        noResultsMsg.style.display = 'block';
    } else if (noResultsMsg) {
        noResultsMsg.style.display = 'none';
    }
}

// Clear search filters
function clearSearch() {
    const searchInput = document.getElementById('search-location');
    const animalSelect = document.getElementById('search-animal');
    const dateInput = document.getElementById('search-dates');
    
    if (searchInput) searchInput.value = '';
    if (animalSelect) animalSelect.value = '';
    if (dateInput) dateInput.value = '';
    
    // Show all cards
    const cards = document.querySelectorAll('.animal-card');
    cards.forEach(card => {
        card.style.display = 'block';
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
    });
    
    // Hide no results message
    const noResultsMsg = document.getElementById('no-results-message');
    if (noResultsMsg) {
        noResultsMsg.style.display = 'none';
    }
}

// Loading states for buttons
function showLoading(button, text = 'Loading...') {
    button.disabled = true;
    button.dataset.originalText = button.innerHTML;
    button.innerHTML = `<span class="loading"></span> ${text}`;
}

function hideLoading(button) {
    button.disabled = false;
    button.innerHTML = button.dataset.originalText;
}

// Notification system
function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-icon">
                ${type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ'}
            </span>
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;
    
    // Add to page
    let container = document.getElementById('notification-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notification-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            max-width: 400px;
        `;
        document.body.appendChild(container);
    }
    
    container.appendChild(notification);
    
    // Auto remove
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        }
    }, duration);
}

// Form submission with loading states
function submitFormWithLoading(form, button) {
    showLoading(button, 'Memproses...');
    
    // Add form data validation if needed
    const requiredInputs = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredInputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('error');
            isValid = false;
        } else {
            input.classList.remove('error');
        }
    });
    
    if (!isValid) {
        hideLoading(button);
        showNotification('Harap lengkapi semua field yang diperlukan', 'error');
        return false;
    }
    
    return true;
}

// Utility functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR'
    }).format(amount);
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Berhasil disalin ke clipboard', 'success', 2000);
    }).catch(() => {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showNotification('Berhasil disalin ke clipboard', 'success', 2000);
    });
}

// Dark mode toggle (future feature)
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
}

// Initialize dark mode from localStorage
function initDarkMode() {
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
    }
}

// Print functionality for receipts
function printReceipt(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Resi Pemesanan</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .receipt { max-width: 400px; margin: 0 auto; }
                .receipt-header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
                .receipt-row { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px dotted #ccc; }
                .receipt-row:last-child { border-bottom: none; font-weight: bold; }
                @media print { body { margin: 0; } }
            </style>
        </head>
        <body>
            ${element.outerHTML}
            <script>window.print(); window.close();</script>
        </body>
        </html>
    `);
}

// Enhanced modal functionality
function showModal(modalId, data = {}) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    // Populate modal with data if provided
    Object.keys(data).forEach(key => {
        const element = modal.querySelector(`[data-field="${key}"]`);
        if (element) {
            element.textContent = data[key];
        }
    });
    
    modal.classList.add('show');
    
    // Focus first input
    const firstInput = modal.querySelector('input, select, textarea');
    if (firstInput) {
        setTimeout(() => firstInput.focus(), 100);
    }
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
}

function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    modal.classList.remove('show');
    
    // Restore body scroll
    document.body.style.overflow = '';
    
    // Clear form inputs
    const form = modal.querySelector('form');
    if (form) {
        form.reset();
        // Clear validation errors
        form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Escape key closes modals
    if (e.key === 'Escape') {
        const openModal = document.querySelector('.modal.show');
        if (openModal) {
            openModal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }
    
    // Ctrl/Cmd + K opens search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.getElementById('search-location');
        if (searchInput) {
            searchInput.focus();
        }
    }
});

// Auto-save form data (for drafts)
function initAutoSave(formId, storageKey) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    // Load saved data
    const savedData = localStorage.getItem(storageKey);
    if (savedData) {
        const data = JSON.parse(savedData);
        Object.keys(data).forEach(key => {
            const input = form.querySelector(`[name="${key}"]`);
            if (input && input.type !== 'password') {
                input.value = data[key];
            }
        });
    }
    
    // Save data on input
    form.addEventListener('input', function() {
        const formData = new FormData(form);
        const data = {};
        for (let [key, value] of formData.entries()) {
            if (form.querySelector(`[name="${key}"]`).type !== 'password') {
                data[key] = value;
            }
        }
        localStorage.setItem(storageKey, JSON.stringify(data));
    });
    
    // Clear saved data on successful submit
    form.addEventListener('submit', function() {
        localStorage.removeItem(storageKey);
    });
}

// Initialize on page load
window.addEventListener('load', function() {
    initDarkMode();
    
    // Add smooth transitions to all elements
    document.body.style.transition = 'all 0.3s ease';
    
    // Initialize auto-save for booking form
    initAutoSave('orderForm', 'booking_draft');
    
    console.log('🐾 BioVet website loaded successfully!');
});

