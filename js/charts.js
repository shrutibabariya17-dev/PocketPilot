// js/charts.js - Visual Chart Helpers for Reports and Dashboard

document.addEventListener('DOMContentLoaded', function () {
    // Animate progress bars on load
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(bar => {
        const targetWidth = bar.getAttribute('data-width') || bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = targetWidth;
        }, 150);
    });

    // Add interactivity to CSS bar chart columns
    const chartBars = document.querySelectorAll('.chart-bar');
    chartBars.forEach(bar => {
        bar.addEventListener('mouseenter', function () {
            this.style.opacity = '0.85';
        });
        bar.addEventListener('mouseleave', function () {
            this.style.opacity = '1';
        });
    });
});

