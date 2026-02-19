/*
 * KTC Invoice Pro - Application JavaScript principale
 */

// Import des styles
import '../css/app.css';

// Import de Stimulus (contrôleurs)
import './bootstrap';

// Import d'Alpine.js
import Alpine from 'alpinejs';

// Configuration d'Alpine.js
window.Alpine = Alpine;

// Composants Alpine.js globaux
Alpine.data('dropdown', () => ({
    open: false,
    toggle() {
        this.open = !this.open;
    },
    close() {
        this.open = false;
    }
}));

Alpine.data('modal', () => ({
    open: false,
    show() {
        this.open = true;
        document.body.classList.add('overflow-hidden');
    },
    hide() {
        this.open = false;
        document.body.classList.remove('overflow-hidden');
    }
}));

Alpine.data('tabs', (defaultTab = '') => ({
    activeTab: defaultTab,
    setTab(tab) {
        this.activeTab = tab;
    },
    isActive(tab) {
        return this.activeTab === tab;
    }
}));

Alpine.data('toast', () => ({
    toasts: [],
    add(message, type = 'info', duration = 5000) {
        const id = Date.now();
        this.toasts.push({ id, message, type });
        if (duration > 0) {
            setTimeout(() => this.remove(id), duration);
        }
    },
    remove(id) {
        this.toasts = this.toasts.filter(t => t.id !== id);
    }
}));

Alpine.data('sidebar', () => ({
    open: false,
    toggle() {
        this.open = !this.open;
    },
    close() {
        this.open = false;
    }
}));

// Démarrage d'Alpine.js
Alpine.start();

// Utilitaires globaux
window.KTC = {
    // Formater un montant en FCFA
    formatCurrency(amount, currency = 'FCFA') {
        const formatter = new Intl.NumberFormat('fr-FR', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
        return `${formatter.format(amount)} ${currency}`;
    },

    // Formater une date
    formatDate(date, format = 'short') {
        const d = new Date(date);
        const options = format === 'long' 
            ? { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }
            : { year: 'numeric', month: '2-digit', day: '2-digit' };
        return d.toLocaleDateString('fr-FR', options);
    },

    // Copier dans le presse-papier
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (err) {
            console.error('Erreur de copie:', err);
            return false;
        }
    },

    // Confirmation avant action
    confirm(message, callback) {
        if (window.confirm(message)) {
            callback();
        }
    },

    // Debounce
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
    },

    // Throttle
    throttle(func, limit) {
        let inThrottle;
        return function executedFunction(...args) {
            if (!inThrottle) {
                func(...args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
};

// Gestion des flashs messages (auto-fermeture)
document.addEventListener('DOMContentLoaded', () => {
    // Auto-fermeture des alertes après 5 secondes
    document.querySelectorAll('[data-auto-dismiss]').forEach(alert => {
        const duration = parseInt(alert.dataset.autoDismiss) || 5000;
        setTimeout(() => {
            alert.classList.add('opacity-0', 'transition-opacity', 'duration-300');
            setTimeout(() => alert.remove(), 300);
        }, duration);
    });

    // Confirmation de suppression
    document.querySelectorAll('[data-confirm]').forEach(element => {
        element.addEventListener('click', (e) => {
            const message = element.dataset.confirm || 'Êtes-vous sûr de vouloir effectuer cette action ?';
            if (!window.confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // Toggle sidebar mobile
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            sidebarOverlay?.classList.toggle('hidden');
        });

        sidebarOverlay?.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
        });
    }
});

// Log de démarrage en développement
if (process.env.NODE_ENV === 'development') {
    console.log('🚀 KTC Invoice Pro - Application démarrée');
}
