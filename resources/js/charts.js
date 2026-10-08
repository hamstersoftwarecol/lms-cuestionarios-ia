import Chart from 'chart.js/auto';

const PALETTE = ['#6366f1', '#10b981', '#f59e0b', '#f43f5e', '#0ea5e9', '#8b5cf6', '#14b8a6', '#ec4899'];
const charts = [];

function themeColors() {
    const dark = document.documentElement.classList.contains('dark');
    return {
        text: dark ? '#cbd5e1' : '#475569',
        grid: dark ? 'rgba(148,163,184,0.12)' : 'rgba(100,116,139,0.15)',
    };
}

/**
 * <canvas data-chart='{"type":"line","labels":[...],"datasets":[{"label":"…","data":[…]}]}'>
 */
function build(canvas) {
    const config = JSON.parse(canvas.dataset.chart);
    const colors = themeColors();
    const isCircular = ['doughnut', 'pie', 'polarArea'].includes(config.type);

    const datasets = config.datasets.map((dataset, i) => {
        const color = dataset.color || PALETTE[i % PALETTE.length];
        return {
            borderWidth: 2,
            tension: 0.35,
            pointRadius: config.type === 'line' ? 3 : 0,
            fill: config.type === 'line' && dataset.fill !== false,
            ...dataset,
            borderColor: isCircular ? '#ffffff00' : color,
            backgroundColor: isCircular
                ? config.labels.map((_, j) => PALETTE[j % PALETTE.length])
                : config.type === 'line'
                  ? `${color}22`
                  : dataset.colors || color,
            borderRadius: config.type === 'bar' ? 6 : 0,
            maxBarThickness: 48,
        };
    });

    return new Chart(canvas, {
        type: config.type,
        data: { labels: config.labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: isCircular || datasets.length > 1, position: 'bottom', labels: { color: colors.text, usePointStyle: true } },
                tooltip: { mode: 'index', intersect: false },
            },
            scales: isCircular
                ? {}
                : {
                      x: { ticks: { color: colors.text, maxRotation: 0, autoSkip: true }, grid: { display: false } },
                      y: {
                          beginAtZero: true,
                          max: config.max ?? undefined,
                          ticks: { color: colors.text, precision: 0 },
                          grid: { color: colors.grid },
                      },
                  },
        },
    });
}

export function initCharts() {
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => charts.push(build(canvas)));

    // Al cambiar de tema se redibujan con los colores adecuados.
    window.addEventListener('theme-changed', () => {
        charts.splice(0).forEach((chart) => {
            const canvas = chart.canvas;
            chart.destroy();
            charts.push(build(canvas));
        });
    });
}
