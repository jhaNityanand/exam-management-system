/**
 * Admin dashboard Chart.js widgets.
 * Expects window.dashboardChartsConfig with chart datasets from DashboardService.
 */
(function () {
    'use strict';

    var PALETTE = [
        'rgba(14, 116, 144, 0.85)',
        'rgba(79, 70, 229, 0.85)',
        'rgba(16, 185, 129, 0.85)',
        'rgba(244, 63, 94, 0.85)',
        'rgba(245, 158, 11, 0.85)',
        'rgba(168, 85, 247, 0.85)',
        'rgba(6, 182, 212, 0.85)',
        'rgba(99, 102, 241, 0.85)',
    ];

    var LINE_COLORS = [
        { border: 'rgba(99, 102, 241, 1)', fill: 'rgba(99, 102, 241, 0.12)' },
        { border: 'rgba(16, 185, 129, 1)', fill: 'rgba(16, 185, 129, 0.12)' },
        { border: 'rgba(244, 63, 94, 1)', fill: 'rgba(244, 63, 94, 0.12)' },
    ];

    function themeColors() {
        var isDark = document.documentElement.classList.contains('dark');
        return {
            text: isDark ? '#94a3b8' : '#64748b',
            grid: isDark ? '#1e293b' : '#f1f5f9',
            legend: isDark ? '#cbd5e1' : '#475569',
        };
    }

    function colorsFor(count) {
        var out = [];
        var i;
        for (i = 0; i < count; i += 1) {
            out.push(PALETTE[i % PALETTE.length]);
        }
        return out;
    }

    function emptySeries(chart) {
        var labels = (chart && chart.labels) || [];
        var values = (chart && chart.values) || [];
        if (!labels.length) {
            return { labels: ['No data'], values: [0] };
        }
        return { labels: labels, values: values };
    }

    function cartesianOptions(theme, stacked) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    labels: { color: theme.legend, boxWidth: 12, usePointStyle: true },
                },
            },
            scales: {
                x: {
                    stacked: !!stacked,
                    grid: { display: false },
                    ticks: { color: theme.text, maxRotation: 45, minRotation: 0 },
                },
                y: {
                    stacked: !!stacked,
                    beginAtZero: true,
                    grid: { color: theme.grid },
                    ticks: { color: theme.text, precision: 0 },
                },
            },
        };
    }

    function roundOptions(theme) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: theme.legend, boxWidth: 12, usePointStyle: true },
                },
            },
        };
    }

    function makeChart(id, config) {
        var el = document.getElementById(id);
        if (!el) {
            return null;
        }
        return new Chart(el, config);
    }

    function render() {
        if (typeof Chart === 'undefined') {
            return;
        }

        var config = window.dashboardChartsConfig || {};
        var theme = themeColors();

        var questions = emptySeries(config.questions_by_category);
        makeChart('questionsByCategoryChart', {
            type: 'bar',
            data: {
                labels: questions.labels,
                datasets: [{
                    label: 'Questions',
                    data: questions.values,
                    backgroundColor: colorsFor(questions.values.length),
                    borderRadius: 6,
                }],
            },
            options: Object.assign(cartesianOptions(theme), {
                plugins: { legend: { display: false } },
            }),
        });

        var exams = emptySeries(config.exams_by_status);
        makeChart('examsByStatusChart', {
            type: 'doughnut',
            data: {
                labels: exams.labels,
                datasets: [{
                    data: exams.values,
                    backgroundColor: colorsFor(exams.values.length),
                    borderWidth: 0,
                }],
            },
            options: roundOptions(theme),
        });

        var attempts = emptySeries(config.exam_attempts);
        makeChart('examAttemptsChart', {
            type: 'line',
            data: {
                labels: attempts.labels,
                datasets: [{
                    label: 'Attempts',
                    data: attempts.values,
                    borderColor: LINE_COLORS[1].border,
                    backgroundColor: LINE_COLORS[1].fill,
                    borderWidth: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: LINE_COLORS[1].border,
                    pointBorderWidth: 2,
                    fill: true,
                    tension: 0.4,
                }],
            },
            options: Object.assign(cartesianOptions(theme), {
                plugins: { legend: { display: false } },
            }),
        });

        var candidates = emptySeries(config.candidate_registrations);
        makeChart('candidateRegistrationsChart', {
            type: 'bar',
            data: {
                labels: candidates.labels,
                datasets: [{
                    label: 'Registrations',
                    data: candidates.values,
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderRadius: 6,
                }],
            },
            options: Object.assign(cartesianOptions(theme), {
                plugins: { legend: { display: false } },
            }),
        });

        var blogNews = emptySeries(config.blog_vs_news);
        makeChart('blogVsNewsChart', {
            type: 'pie',
            data: {
                labels: blogNews.labels,
                datasets: [{
                    data: blogNews.values,
                    backgroundColor: ['rgba(168, 85, 247, 0.85)', 'rgba(6, 182, 212, 0.85)'],
                    borderWidth: 0,
                }],
            },
            options: roundOptions(theme),
        });

        var roles = emptySeries(config.members_by_role);
        makeChart('membersByRoleChart', {
            type: 'doughnut',
            data: {
                labels: roles.labels,
                datasets: [{
                    data: roles.values,
                    backgroundColor: colorsFor(roles.values.length),
                    borderWidth: 0,
                }],
            },
            options: roundOptions(theme),
        });

        var growth = config.monthly_content_growth || { labels: [], datasets: [] };
        var growthLabels = growth.labels && growth.labels.length ? growth.labels : ['No data'];
        var growthDatasets = (growth.datasets || []).map(function (ds, index) {
            var color = LINE_COLORS[index % LINE_COLORS.length];
            return {
                label: ds.label,
                data: ds.values && ds.values.length ? ds.values : [0],
                borderColor: color.border,
                backgroundColor: color.fill,
                borderWidth: 2,
                fill: false,
                tension: 0.35,
            };
        });
        if (!growthDatasets.length) {
            growthDatasets = [{
                label: 'Content',
                data: [0],
                borderColor: LINE_COLORS[0].border,
                backgroundColor: LINE_COLORS[0].fill,
                borderWidth: 2,
                fill: false,
            }];
        }
        makeChart('monthlyContentGrowthChart', {
            type: 'line',
            data: {
                labels: growthLabels,
                datasets: growthDatasets,
            },
            options: cartesianOptions(theme),
        });

        var distribution = emptySeries(config.content_distribution);
        makeChart('contentDistributionChart', {
            type: 'pie',
            data: {
                labels: distribution.labels,
                datasets: [{
                    data: distribution.values,
                    backgroundColor: colorsFor(distribution.values.length),
                    borderWidth: 0,
                }],
            },
            options: roundOptions(theme),
        });
    }

    document.addEventListener('DOMContentLoaded', render);
})();
