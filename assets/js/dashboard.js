/*
 * KTC Invoice Pro - Dashboard JavaScript
 */

import Chart from 'chart.js/auto';

// Configuration globale Chart.js
Chart.defaults.font.family = "'Inter', 'system-ui', '-apple-system', 'sans-serif'";
Chart.defaults.color = '#64748b';

// Initialisation des graphiques du dashboard
document.addEventListener('DOMContentLoaded', () => {
    initRevenueChart();
    initStatusChart();
    initTypeChart();
});

// Graphique d'évolution du chiffre d'affaires
function initRevenueChart() {
    const ctx = document.getElementById('revenueChart');
    if (!ctx) return;

    const data = JSON.parse(ctx.dataset.values || '[]');
    const labels = JSON.parse(ctx.dataset.labels || '[]');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Chiffre d\'affaires',
                data: data,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#2563eb',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleColor: '#fff',
                    bodyColor: '#cbd5e1',
                    borderColor: '#334155',
                    borderWidth: 1,
                    cornerRadius: 8,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return KTC.formatCurrency(context.raw);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    border: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f1f5f9'
                    },
                    border: {
                        display: false
                    },
                    ticks: {
                        callback: function(value) {
                            return KTC.formatCurrency(value);
                        }
                    }
                }
            }
        }
    });
}

// Graphique des statuts de factures
function initStatusChart() {
    const ctx = document.getElementById('statusChart');
    if (!ctx) return;

    const data = JSON.parse(ctx.dataset.values || '[]');
    const labels = JSON.parse(ctx.dataset.labels || '[]');

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    '#22c55e', // Payée
                    '#f59e0b', // En attente
                    '#3b82f6', // Envoyée
                    '#ef4444', // Annulée
                ],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleColor: '#fff',
                    bodyColor: '#cbd5e1',
                    borderColor: '#334155',
                    borderWidth: 1,
                    cornerRadius: 8,
                    padding: 12
                }
            }
        }
    });
}

// Graphique par type de produit
function initTypeChart() {
    const ctx = document.getElementById('typeChart');
    if (!ctx) return;

    const data = JSON.parse(ctx.dataset.values || '[]');
    const labels = JSON.parse(ctx.dataset.labels || '[]');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Montant',
                data: data,
                backgroundColor: [
                    '#3b82f6', // Logiciel
                    '#8b5cf6', // Matériel
                    '#06b6d4', // Service
                ],
                borderRadius: 6,
                maxBarThickness: 50
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleColor: '#fff',
                    bodyColor: '#cbd5e1',
                    borderColor: '#334155',
                    borderWidth: 1,
                    cornerRadius: 8,
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            return KTC.formatCurrency(context.raw);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    border: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f1f5f9'
                    },
                    border: {
                        display: false
                    },
                    ticks: {
                        callback: function(value) {
                            return KTC.formatCurrency(value);
                        }
                    }
                }
            }
        }
    });
}

// Export pour utilisation externe
window.KTCDashboard = {
    initRevenueChart,
    initStatusChart,
    initTypeChart
};
