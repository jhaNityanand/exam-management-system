/**
 * Admin dashboard Chart.js widgets.
 * Expects window.dashboardChartsConfig = { categoryChart, attemptsChart }.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Chart === 'undefined') return;

        const config = window.dashboardChartsConfig || {};
        const categoryChart = config.categoryChart || { labels: [], values: [] };
        const attemptsChart = config.attemptsChart || { labels: [], values: [] };
        const isDark = document.documentElement.classList.contains('dark');
        const textColor = isDark ? '#94a3b8' : '#64748b';
        const gridColor = isDark ? '#1e293b' : '#f1f5f9';

        const ctx1 = document.getElementById('categoryQuestionsChart');
        if (ctx1) {
            new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: categoryChart.labels.length ? categoryChart.labels : ['No data'],
                    datasets: [{
                        label: 'Questions',
                        data: categoryChart.values.length ? categoryChart.values : [0],
                        backgroundColor: 'rgba(99, 102, 241, 0.8)',
                        borderRadius: 6,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: textColor, maxRotation: 45, minRotation: 0 } },
                        y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor, precision: 0 } },
                    },
                },
            });
        }

        const ctx2 = document.getElementById('examAttemptsChart');
        if (ctx2) {
            new Chart(ctx2, {
                type: 'line',
                data: {
                    labels: attemptsChart.labels,
                    datasets: [{
                        label: 'Attempts',
                        data: attemptsChart.values,
                        borderColor: 'rgba(16, 185, 129, 1)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: 'rgba(16, 185, 129, 1)',
                        pointBorderWidth: 2,
                        fill: true,
                        tension: 0.4,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: textColor } },
                        y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor, precision: 0 } },
                    },
                },
            });
        }
    });
})();
