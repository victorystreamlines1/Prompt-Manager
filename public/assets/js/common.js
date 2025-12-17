/**
 * ============================================
 * Common JavaScript - Shared Across All Modules
 * ============================================
 * 
 * This file contains:
 * - Toast notification system
 * - Modal management
 * - API helper functions
 * - Utility functions
 * 
 * ============================================
 */

// ============================================
// TOAST NOTIFICATION SYSTEM
// ============================================

const Toast = {
    container: null,
    
    /**
     * Initialize toast container.
     */
    init() {
        if (this.container) return;
        
        this.container = document.createElement('div');
        this.container.className = 'toast-container';
        this.container.id = 'toast-container';
        document.body.appendChild(this.container);
    },
    
    /**
     * Show a toast notification.
     * 
     * @param {string} message - Toast message
     * @param {string} type - success, error, warning, info
     * @param {number} duration - Duration in ms (default 3000)
     */
    show(message, type = 'info', duration = 3000) {
        this.init();
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const icon = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        }[type] || 'ℹ';
        
        toast.innerHTML = `<span>${icon}</span> ${this.escapeHtml(message)}`;
        this.container.appendChild(toast);
        
        // Auto remove
        setTimeout(() => {
            toast.style.animation = 'toastSlideOut 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, duration);
        
        return toast;
    },
    
    /**
     * Shorthand methods.
     */
    success(message, duration) { return this.show(message, 'success', duration); },
    error(message, duration) { return this.show(message, 'error', duration); },
    warning(message, duration) { return this.show(message, 'warning', duration); },
    info(message, duration) { return this.show(message, 'info', duration); },
    
    /**
     * Escape HTML to prevent XSS.
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Add slide out animation
const style = document.createElement('style');
style.textContent = `
    @keyframes toastSlideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100px); opacity: 0; }
    }
`;
document.head.appendChild(style);

// ============================================
// MODAL MANAGEMENT
// ============================================

const Modal = {
    /**
     * Open a modal by ID.
     * 
     * @param {string} id - Modal overlay ID
     */
    open(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Focus first input if exists
            const input = modal.querySelector('input, textarea, select');
            if (input) {
                setTimeout(() => input.focus(), 100);
            }
        }
    },
    
    /**
     * Close a modal by ID.
     * 
     * @param {string} id - Modal overlay ID
     */
    close(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    },
    
    /**
     * Close all open modals.
     */
    closeAll() {
        document.querySelectorAll('.modal-overlay.active').forEach(modal => {
            modal.classList.remove('active');
        });
        document.body.style.overflow = '';
    },
    
    /**
     * Setup modal event listeners.
     * Auto-binds close buttons and backdrop clicks.
     */
    init() {
        // Close on backdrop click
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
        
        // Close buttons
        document.querySelectorAll('.modal-close, [data-modal-close]').forEach(btn => {
            btn.addEventListener('click', () => {
                const modal = btn.closest('.modal-overlay');
                if (modal) {
                    modal.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
        
        // Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAll();
            }
        });
    }
};

// ============================================
// API HELPER
// ============================================

const API = {
    /**
     * Make an API request.
     * 
     * @param {string} action - API action name
     * @param {object} data - Request data
     * @param {object} options - Additional options
     * @returns {Promise<object>}
     */
    async call(action, data = {}, options = {}) {
        const {
            method = 'POST',
            endpoint = window.location.href,
            showErrors = true
        } = options;
        
        try {
            const formData = new FormData();
            formData.append('action', action);
            
            // Add data to FormData
            for (const [key, value] of Object.entries(data)) {
                if (value instanceof File) {
                    formData.append(key, value);
                } else if (typeof value === 'object') {
                    formData.append(key, JSON.stringify(value));
                } else {
                    formData.append(key, value);
                }
            }
            
            const response = await fetch(endpoint, {
                method,
                body: formData
            });
            
            const result = await response.json();
            
            if (!result.success && showErrors) {
                Toast.error(result.message || 'An error occurred');
            }
            
            return result;
            
        } catch (error) {
            console.error('API Error:', error);
            if (showErrors) {
                Toast.error('Network error. Please try again.');
            }
            return { success: false, message: error.message };
        }
    },
    
    /**
     * Shorthand POST request.
     */
    async post(action, data = {}) {
        return this.call(action, data, { method: 'POST' });
    },
    
    /**
     * Shorthand GET request.
     */
    async get(action, data = {}) {
        const params = new URLSearchParams({ action, ...data });
        const endpoint = `${window.location.pathname}?${params}`;
        
        try {
            const response = await fetch(endpoint);
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            return { success: false, message: error.message };
        }
    }
};

// ============================================
// UTILITY FUNCTIONS
// ============================================

const Utils = {
    /**
     * Format file size to human readable.
     * 
     * @param {number} bytes
     * @returns {string}
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },
    
    /**
     * Format date to locale string.
     * 
     * @param {string|Date} date
     * @returns {string}
     */
    formatDate(date) {
        const d = new Date(date);
        return d.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    /**
     * Debounce function.
     * 
     * @param {Function} func
     * @param {number} wait
     * @returns {Function}
     */
    debounce(func, wait = 300) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    /**
     * Throttle function.
     * 
     * @param {Function} func
     * @param {number} limit
     * @returns {Function}
     */
    throttle(func, limit = 300) {
        let inThrottle;
        return function executedFunction(...args) {
            if (!inThrottle) {
                func(...args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    /**
     * Copy text to clipboard.
     * 
     * @param {string} text
     * @returns {Promise<boolean>}
     */
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            Toast.success('Copied to clipboard!');
            return true;
        } catch (err) {
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            
            try {
                document.execCommand('copy');
                Toast.success('Copied to clipboard!');
                return true;
            } catch (e) {
                Toast.error('Failed to copy');
                return false;
            } finally {
                textarea.remove();
            }
        }
    },
    
    /**
     * Download content as file.
     * 
     * @param {string|Blob} content
     * @param {string} filename
     * @param {string} mimeType
     */
    downloadFile(content, filename, mimeType = 'text/plain') {
        const blob = content instanceof Blob ? content : new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    },
    
    /**
     * Generate unique ID.
     * 
     * @returns {string}
     */
    uniqueId() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    },
    
    /**
     * Check if element is in viewport.
     * 
     * @param {Element} el
     * @returns {boolean}
     */
    isInViewport(el) {
        const rect = el.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    },
    
    /**
     * Escape HTML entities.
     * 
     * @param {string} str
     * @returns {string}
     */
    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },
    
    /**
     * Parse query string to object.
     * 
     * @param {string} queryString
     * @returns {object}
     */
    parseQuery(queryString = window.location.search) {
        return Object.fromEntries(new URLSearchParams(queryString));
    }
};

// ============================================
// FORM HELPERS
// ============================================

const Form = {
    /**
     * Serialize form to object.
     * 
     * @param {HTMLFormElement} form
     * @returns {object}
     */
    serialize(form) {
        const formData = new FormData(form);
        const data = {};
        
        for (const [key, value] of formData.entries()) {
            // Handle array fields (name="field[]")
            if (key.endsWith('[]')) {
                const cleanKey = key.slice(0, -2);
                if (!data[cleanKey]) data[cleanKey] = [];
                data[cleanKey].push(value);
            } else {
                data[key] = value;
            }
        }
        
        return data;
    },
    
    /**
     * Populate form with data.
     * 
     * @param {HTMLFormElement} form
     * @param {object} data
     */
    populate(form, data) {
        for (const [key, value] of Object.entries(data)) {
            const field = form.elements[key];
            if (!field) continue;
            
            if (field.type === 'checkbox') {
                field.checked = Boolean(value);
            } else if (field.type === 'radio') {
                const radio = form.querySelector(`[name="${key}"][value="${value}"]`);
                if (radio) radio.checked = true;
            } else {
                field.value = value;
            }
        }
    },
    
    /**
     * Reset form and clear errors.
     * 
     * @param {HTMLFormElement} form
     */
    reset(form) {
        form.reset();
        form.querySelectorAll('.error-message').forEach(el => el.remove());
        form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
    }
};

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    // Initialize toast container
    Toast.init();
    
    // Initialize modals
    Modal.init();
    
    console.log('✓ Common JS initialized');
});

// Export for use in modules
window.Toast = Toast;
window.Modal = Modal;
window.API = API;
window.Utils = Utils;
window.Form = Form;

