/**
 * Credit Risk Simulator - Client-side JavaScript
 * 
 * Handles form validation, toast notifications, and interactive features.
 */

// Toast notification system
const Toast = {
    container: null,
    
    init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        }
    },
    
    show(message, type = 'info', duration = 5000) {
        this.init();
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const iconMap = {
            success: 'check-circle',
            error: 'alert-circle',
            warning: 'alert-triangle',
            info: 'info'
        };
        
        toast.innerHTML = `
            <i data-feather="${iconMap[type]}" style="width: 20px; height: 20px;"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.remove()" style="margin-left: auto; background: none; border: none; cursor: pointer; opacity: 0.7;">
                <i data-feather="x" style="width: 16px; height: 16px;"></i>
            </button>
        `;
        
        this.container.appendChild(toast);
        
        // Initialize Feather icons in the new toast
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
        
        // Auto-remove after duration
        if (duration > 0) {
            setTimeout(() => {
                toast.style.animation = 'fadeIn 0.3s ease-out reverse';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }
    },
    
    success(message, duration) {
        this.show(message, 'success', duration);
    },
    
    error(message, duration) {
        this.show(message, 'error', duration);
    },
    
    warning(message, duration) {
        this.show(message, 'warning', duration);
    },
    
    info(message, duration) {
        this.show(message, 'info', duration);
    }
};

// Form validation
const FormValidator = {
    rules: {
        required: (value) => value.trim() !== '' || 'Ce champ est requis',
        email: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) || 'Email invalide',
        cin: (value) => /^[0-9]{8}$/.test(value) || 'CIN invalide (8 chiffres)',
        phone: (value) => !value || /^[0-9+\s-]{10,}$/.test(value) || 'Numéro de téléphone invalide',
        minLength: (length) => (value) => value.length >= length || `Minimum ${length} caractères`,
        maxLength: (length) => (value) => value.length <= length || `Maximum ${length} caractères`,
        min: (min) => (value) => parseFloat(value) >= min || `Minimum ${min}`,
        max: (max) => (value) => parseFloat(value) <= max || `Maximum ${max}`,
        numeric: (value) => !isNaN(parseFloat(value)) || 'Valeur numérique requise',
        date: (value) => !isNaN(Date.parse(value)) || 'Date invalide'
    },
    
    validate(form, validations) {
        let isValid = true;
        const errors = {};
        
        // Clear previous errors
        form.querySelectorAll('.form-error').forEach(el => el.remove());
        form.querySelectorAll('.border-red-500').forEach(el => {
            el.classList.remove('border-red-500');
        });
        
        for (const [fieldName, rules] of Object.entries(validations)) {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (!field) continue;
            
            const value = field.value;
            
            for (const rule of rules) {
                let validator, errorMessage;
                
                if (typeof rule === 'string') {
                    validator = this.rules[rule];
                } else if (typeof rule === 'function') {
                    validator = rule;
                } else if (rule.rule) {
                    validator = typeof rule.rule === 'function' 
                        ? rule.rule 
                        : this.rules[rule.rule];
                    errorMessage = rule.message;
                }
                
                if (validator) {
                    const result = validator(value);
                    if (result !== true) {
                        isValid = false;
                        errors[fieldName] = errorMessage || result;
                        
                        // Show error
                        field.classList.add('border-red-500');
                        const errorEl = document.createElement('p');
                        errorEl.className = 'form-error';
                        errorEl.textContent = errors[fieldName];
                        field.parentNode.appendChild(errorEl);
                        
                        break;
                    }
                }
            }
        }
        
        return { isValid, errors };
    }
};

// Confirmation modal
const Modal = {
    show(options) {
        const { title, message, confirmText = 'Confirmer', cancelText = 'Annuler', onConfirm, type = 'warning' } = options;
        
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop';
        backdrop.innerHTML = `
            <div class="modal-content">
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center ${type === 'danger' ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-600'}">
                            <i data-feather="${type === 'danger' ? 'trash-2' : 'alert-triangle'}" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">${title}</h3>
                            <p class="text-sm text-gray-500">${message}</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button class="btn btn-secondary modal-cancel">${cancelText}</button>
                        <button class="btn ${type === 'danger' ? 'btn-danger' : 'btn-primary'} modal-confirm">${confirmText}</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(backdrop);
        
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
        
        backdrop.querySelector('.modal-cancel').addEventListener('click', () => {
            backdrop.remove();
        });
        
        backdrop.querySelector('.modal-confirm').addEventListener('click', () => {
            if (onConfirm) onConfirm();
            backdrop.remove();
        });
        
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) {
                backdrop.remove();
            }
        });
    },
    
    confirm(title, message, onConfirm) {
        this.show({ title, message, onConfirm, type: 'warning' });
    },
    
    delete(title, message, onConfirm) {
        this.show({ title, message, onConfirm, type: 'danger', confirmText: 'Supprimer' });
    }
};

// Format currency — Dinar Tunisien
function formatCurrency(amount, currency = 'DT') {
    return new Intl.NumberFormat('fr-TN', {
        style: 'decimal',
        minimumFractionDigits: 3,
        maximumFractionDigits: 3
    }).format(amount) + ' ' + currency;
}

// Format percentage
function formatPercentage(value, decimals = 2) {
    return new Intl.NumberFormat('fr-TN', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(value) + '%';
}

// Calculate monthly payment
function calculateMonthlyPayment(principal, annualRate, durationMonths) {
    if (principal <= 0 || durationMonths <= 0) return 0;
    
    const monthlyRate = (annualRate / 100) / 12;
    
    if (monthlyRate === 0) {
        return principal / durationMonths;
    }
    
    return principal * (monthlyRate / (1 - Math.pow(1 + monthlyRate, -durationMonths)));
}

// Calculate debt ratio
function calculateDebtRatio(charges, revenus) {
    if (revenus <= 0) return 100;
    return (charges / revenus) * 100;
}

// Debounce function
function debounce(func, wait) {
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

// Auto-submit search on typing (with debounce)
document.addEventListener('DOMContentLoaded', () => {
    const searchInputs = document.querySelectorAll('input[data-auto-submit]');
    searchInputs.forEach(input => {
        input.addEventListener('input', debounce(() => {
            input.closest('form').submit();
        }, 500));
    });
    
    // Initialize any data-confirm buttons
    document.querySelectorAll('[data-confirm]').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const message = button.dataset.confirm || 'Êtes-vous sûr ?';
            const href = button.href || button.dataset.href;
            
            Modal.confirm('Confirmation', message, () => {
                if (href) {
                    window.location.href = href;
                } else if (button.form) {
                    button.form.submit();
                }
            });
        });
    });
    
    // Initialize delete confirmation buttons
    document.querySelectorAll('[data-delete-confirm]').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const message = button.dataset.deleteConfirm || 'Cette action est irréversible.';
            const href = button.href || button.dataset.href;
            
            Modal.delete('Supprimer', message, () => {
                if (href) {
                    window.location.href = href;
                } else if (button.form) {
                    button.form.submit();
                }
            });
        });
    });
});

// Export for global use
window.Toast = Toast;
window.Modal = Modal;
window.FormValidator = FormValidator;
window.formatCurrency = formatCurrency;
window.formatPercentage = formatPercentage;
window.calculateMonthlyPayment = calculateMonthlyPayment;
window.calculateDebtRatio = calculateDebtRatio;
