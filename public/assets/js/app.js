/**
 * Main application JavaScript
 */

// Global app configuration
const App = {
    apiBase: '/api',
    csrfToken: null,
    currentUser: null,
    
    // Initialize app
    init() {
        this.getCsrfToken();
        this.setupEventListeners();
        this.setupFormValidation();
    },
    
    // Get CSRF token
    async getCsrfToken() {
        try {
            const response = await fetch(`${this.apiBase}/auth.php?action=csrf-token`);
            const data = await response.json();
            if (data.success) {
                this.csrfToken = data.token;
                window.csrfToken = data.token;
            }
        } catch (error) {
            console.error('Failed to get CSRF token:', error);
        }
    },
    
    // Setup global event listeners
    setupEventListeners() {
        // Handle form submissions with loading states
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form.tagName === 'FORM') {
                this.setFormLoading(form, true);
            }
        });
        
        // Handle AJAX errors globally
        window.addEventListener('unhandledrejection', (e) => {
            console.error('Unhandled promise rejection:', e.reason);
            this.showNotification('An unexpected error occurred', 'error');
        });
    },
    
    // Setup form validation
    setupFormValidation() {
        // Real-time email validation
        document.querySelectorAll('input[type="email"]').forEach(input => {
            input.addEventListener('blur', (e) => {
                this.validateEmail(e.target);
            });
        });
        
        // Real-time password validation
        document.querySelectorAll('input[type="password"]').forEach(input => {
            input.addEventListener('input', (e) => {
                this.validatePassword(e.target);
            });
        });
        
        // Password confirmation validation
        const confirmPasswordInputs = document.querySelectorAll('input[name="confirm_password"]');
        confirmPasswordInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                this.validatePasswordConfirmation(e.target);
            });
        });
    },
    
    // Email validation
    validateEmail(input) {
        const email = input.value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
            this.setFieldError(input, 'Please enter a valid email address');
        } else {
            this.clearFieldError(input);
        }
    },
    
    // Password validation
    validatePassword(input) {
        const password = input.value;
        
        if (password && password.length < 6) {
            this.setFieldError(input, 'Password must be at least 6 characters');
        } else {
            this.clearFieldError(input);
        }
    },
    
    // Password confirmation validation
    validatePasswordConfirmation(input) {
        const password = document.querySelector('input[name="password"]')?.value;
        const confirmPassword = input.value;
        
        if (confirmPassword && password !== confirmPassword) {
            this.setFieldError(input, 'Passwords do not match');
        } else {
            this.clearFieldError(input);
        }
    },
    
    // Set field error
    setFieldError(input, message) {
        this.clearFieldError(input);
        
        input.classList.add('is-invalid');
        
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = message;
        
        input.parentNode.appendChild(feedback);
    },
    
    // Clear field error
    clearFieldError(input) {
        input.classList.remove('is-invalid');
        
        const feedback = input.parentNode.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.remove();
        }
    },
    
    // Set form loading state
    setFormLoading(form, loading) {
        const submitBtn = form.querySelector('button[type="submit"]');
        
        if (loading) {
            form.classList.add('loading');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
            }
        } else {
            form.classList.remove('loading');
            if (submitBtn) {
                submitBtn.disabled = false;
                // Restore original text (you might want to store this)
                submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || 'Submit';
            }
        }
    },
    
    // Show notification
    showNotification(message, type = 'info', duration = 5000) {
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        }[type] || 'alert-info';
        
        const icon = {
            'success': 'fas fa-check-circle',
            'error': 'fas fa-exclamation-triangle',
            'warning': 'fas fa-exclamation-triangle',
            'info': 'fas fa-info-circle'
        }[type] || 'fas fa-info-circle';
        
        const alert = document.createElement('div');
        alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
        alert.style.cssText = 'top: 20px; right: 20px; z-index: 1050; min-width: 300px;';
        alert.innerHTML = `
            <i class="${icon} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alert);
        
        // Auto-remove after duration
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, duration);
    },
    
    // API helper methods
    async apiCall(endpoint, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        // Add CSRF token to POST requests
        if (options.method === 'POST' || options.method === 'PUT' || options.method === 'DELETE') {
            if (options.body && typeof options.body === 'object') {
                options.body.csrf_token = this.csrfToken;
                options.body = JSON.stringify(options.body);
            }
        }
        
        const response = await fetch(`${this.apiBase}/${endpoint}`, {
            ...defaultOptions,
            ...options
        });
        
        return response.json();
    },
    
    // Format date for display
    formatDate(dateString, options = {}) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        const defaultOptions = {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        };
        
        return date.toLocaleDateString('en-US', { ...defaultOptions, ...options });
    },
    
    // Debounce function for search/input
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

// Template management utilities
const TemplateManager = {
    // Preview template
    async previewTemplate(templateId, customData = {}) {
        try {
            const params = new URLSearchParams({ 
                action: 'render', 
                id: templateId,
                data: JSON.stringify(customData)
            });
            
            const response = await fetch(`/api/templates.php?${params}`);
            const data = await response.json();
            
            if (data.success) {
                return {
                    html: data.html,
                    css: data.css,
                    template: data.template
                };
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Template preview error:', error);
            App.showNotification('Failed to preview template', 'error');
            return null;
        }
    },
    
    // Render template in container
    renderTemplate(container, html, css) {
        if (typeof container === 'string') {
            container = document.getElementById(container);
        }
        
        if (container) {
            container.innerHTML = `
                <style>${css}</style>
                ${html}
            `;
        }
    }
};

// Invitation management utilities
const InvitationManager = {
    // Create invitation
    async createInvitation(invitationData) {
        try {
            const response = await App.apiCall('invitations.php?action=create', {
                method: 'POST',
                body: invitationData
            });
            
            if (response.success) {
                App.showNotification('Invitation created successfully!', 'success');
                return response;
            } else {
                App.showNotification(response.message, 'error');
                return null;
            }
        } catch (error) {
            console.error('Invitation creation error:', error);
            App.showNotification('Failed to create invitation', 'error');
            return null;
        }
    },
    
    // Delete invitation
    async deleteInvitation(invitationId) {
        if (!confirm('Are you sure you want to delete this invitation? This action cannot be undone.')) {
            return false;
        }
        
        try {
            const response = await App.apiCall(`invitations.php?id=${invitationId}`, {
                method: 'DELETE'
            });
            
            if (response.success) {
                App.showNotification('Invitation deleted successfully', 'success');
                return true;
            } else {
                App.showNotification(response.message, 'error');
                return false;
            }
        } catch (error) {
            console.error('Invitation deletion error:', error);
            App.showNotification('Failed to delete invitation', 'error');
            return false;
        }
    }
};

// Authentication utilities
const AuthManager = {
    // Logout user
    async logout() {
        try {
            const response = await App.apiCall('auth.php?action=logout', {
                method: 'POST',
                body: {}
            });
            
            if (response.success) {
                window.location.href = '/';
            } else {
                App.showNotification('Logout failed', 'error');
            }
        } catch (error) {
            console.error('Logout error:', error);
            App.showNotification('Logout failed', 'error');
        }
    }
};

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});

// Global utility functions for backward compatibility
function logout() {
    AuthManager.logout();
}

function deleteInvitation(invitationId) {
    InvitationManager.deleteInvitation(invitationId).then(success => {
        if (success) {
            location.reload();
        }
    });
}