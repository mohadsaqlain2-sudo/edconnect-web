/**
 * Themed Chart.js charts (Phase 3B).
 *
 * Every <canvas data-chart="..."> is rendered here. The JSON in the attribute
 * is produced by the <x-chart-card> Blade component, so the pages stay free of
 * inline JavaScript.
 *
 * Extra behaviour:
 *  - charts follow the light / dark theme
 *  - line charts get a soft vertical gradient
 *  - a range <select> can reload a series through a JSON endpoint
 *  - a skeleton is shown while that request is running
 *
 * Phase 3D: Chart.js is loaded with a dynamic import, and only when the page
 * actually contains a <canvas data-chart>. Pages without charts (the landing
 * page, auth, the feed, most admin screens) never download the library.
 */

/** Chart.js constructor, resolved on first use. */
let Chart = null;

/** Shared promise so several boot calls cannot start parallel downloads. */
let chartLoader = null;

/**
 * Load chart.js/auto once. The bundler emits it as a separate chunk.
 */
function loadChartJs() {
    if (Chart) {
        return Promise.resolve(Chart);
    }

    if (! chartLoader) {
        chartLoader = import('chart.js/auto').then((module) => {
            Chart = module.default;

            return Chart;
        });
    }

    return chartLoader;
}

/** Canvas element -> Chart instance. */
const registry = new Map();

const PALETTE = ['#4f46e5', '#7c3aed', '#10b981', '#f59e0b', '#ef4444', '#0ea5e9', '#64748b', '#ec4899'];

const isDark = () => document.documentElement.getAttribute('data-bs-theme') === 'dark';
const tickColor = () => (isDark() ? '#94a3b8' : '#64748b');
const gridColor = () => (isDark() ? 'rgba(148, 163, 184, 0.14)' : 'rgba(100, 116, 139, 0.16)');
const tooltipBg = () => (isDark() ? '#f8fafc' : '#0f172a');
const tooltipText = () => (isDark() ? '#0f172a' : '#f8fafc');

function parseCanvasConfig(canvas) {
    try {
        return JSON.parse(canvas.dataset.chart);
    } catch (error) {
        console.error('[EduConnect] Could not read the chart configuration', canvas, error);

        return null;
    }
}

/**
 * Apply the theme colours to a fresh Chart.js configuration.
 */
function themeConfig(config) {
    config.options = config.options ?? {};
    config.options.color = tickColor();
    config.options.plugins = config.options.plugins ?? {};
    config.options.plugins.legend = config.options.plugins.legend ?? {};
    config.options.plugins.legend.labels = {
        ...(config.options.plugins.legend.labels ?? {}),
        color: tickColor(),
    };
    config.options.plugins.tooltip = {
        ...(config.options.plugins.tooltip ?? {}),
        backgroundColor: tooltipBg(),
        titleColor: tooltipText(),
        bodyColor: tooltipText(),
        borderColor: gridColor(),
        borderWidth: 1,
        padding: 10,
        cornerRadius: 8,
        displayColors: false,
    };

    if (config.options.scales) {
        Object.values(config.options.scales).forEach((scale) => {
            scale.ticks = { ...(scale.ticks ?? {}), color: tickColor() };
            scale.grid = { ...(scale.grid ?? {}), color: gridColor(), drawTicks: false };
            scale.border = { ...(scale.border ?? {}), color: gridColor() };
        });
    }

    return config;
}

/**
 * Re-colour a chart that is already on screen after a theme switch.
 */
function restyle(chart) {
    chart.options.color = tickColor();

    if (chart.options.plugins?.legend?.labels) {
        chart.options.plugins.legend.labels.color = tickColor();
    }

    if (chart.options.plugins?.tooltip) {
        chart.options.plugins.tooltip.backgroundColor = tooltipBg();
        chart.options.plugins.tooltip.titleColor = tooltipText();
        chart.options.plugins.tooltip.bodyColor = tooltipText();
        chart.options.plugins.tooltip.borderColor = gridColor();
    }

    Object.values(chart.options.scales ?? {}).forEach((scale) => {
        scale.ticks = { ...(scale.ticks ?? {}), color: tickColor() };
        scale.grid = { ...(scale.grid ?? {}), color: gridColor() };
        scale.border = { ...(scale.border ?? {}), color: gridColor() };
    });

    chart.update('none');
}

/**
 * Turn a line dataset into a soft area gradient that follows the chart area.
 */
function applyGradient(chart) {
    if (chart.config.type !== 'line') {
        return;
    }

    chart.data.datasets.forEach((dataset) => {
        if (dataset.gradient === false) {
            return;
        }

        const base = dataset.borderColor ?? '#4f46e5';
        dataset.backgroundColor = (context) => {
            const { chart: target, chartArea } = context.chart;

            if (!chartArea) {
                return 'rgba(79, 70, 229, 0.16)';
            }

            const gradient = target.ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
            gradient.addColorStop(0, hexToRgba(base, isDark() ? 0.42 : 0.32));
            gradient.addColorStop(1, hexToRgba(base, 0.01));

            return gradient;
        };
    });

    chart.update('none');
}

function hexToRgba(hex, alpha) {
    const clean = String(hex).replace('#', '');
    const value = clean.length === 3
        ? clean.split('').map((part) => part + part).join('')
        : clean.padEnd(6, '0');

    const int = parseInt(value.slice(0, 6), 16);
    const r = (int >> 16) & 255;
    const g = (int >> 8) & 255;
    const b = int & 255;

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function renderCanvas(canvas) {
    if (canvas.dataset.chartRendered === '1') {
        return registry.get(canvas);
    }

    if (! Chart) {
        return null;
    }

    const config = parseCanvasConfig(canvas);
    if (!config) {
        return null;
    }

    const chart = new Chart(canvas, themeConfig(config));

    canvas.dataset.chartRendered = '1';
    registry.set(canvas, chart);

    if (canvas.dataset.chartGradient !== 'false') {
        applyGradient(chart);
    }

    return chart;
}

function renderAllCharts() {
    document.querySelectorAll('canvas[data-chart]').forEach(renderCanvas);
}

/**
 * Feed a new series into an existing chart after a range change.
 */
function updateSeries(canvas, series) {
    const chart = registry.get(canvas);
    if (!chart || !series) {
        return;
    }

    const labels = Array.isArray(series.labels) ? series.labels : [];
    const values = Array.isArray(series.values) ? series.values.map(Number) : [];

    chart.data.labels = labels;

    if (chart.config.type === 'doughnut' || chart.config.type === 'pie') {
        chart.data.datasets[0].data = values;
        chart.data.datasets[0].backgroundColor = values.map((_, index) => PALETTE[index % PALETTE.length]);
    } else {
        chart.data.datasets[0].data = values;
    }

    chart.update();

    const card = canvas.closest('[data-chart-card]');
    const total = card?.querySelector('[data-chart-total]');
    if (total && typeof series.total !== 'undefined') {
        total.textContent = new Intl.NumberFormat().format(Number(series.total));
    }
}

function setLoading(card, loading) {
    const skeleton = card?.querySelector('[data-chart-skeleton]');
    if (skeleton) {
        skeleton.hidden = !loading;
    }

    const select = card?.querySelector('[data-chart-range]');
    if (select) {
        select.disabled = loading;
    }

    card?.classList.toggle('is-loading', loading);
}

async function loadRange(card, select) {
    const canvas = card.querySelector('canvas[data-chart]');
    const endpoint = canvas?.dataset.chartEndpoint;

    if (!canvas || !endpoint) {
        return;
    }

    setLoading(card, true);

    try {
        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('range', select.value);

        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`Request failed with status ${response.status}`);
        }

        const payload = await response.json();
        const key = canvas.dataset.chartSeries;
        const series = (key && payload[key]) || payload.series || null;

        updateSeries(canvas, series);
    } catch (error) {
        console.error('[EduConnect] Could not refresh the chart', error);
    } finally {
        setLoading(card, false);
    }
}

function bindRangeSelectors() {
    document.querySelectorAll('[data-chart-card]').forEach((card) => {
        const select = card.querySelector('[data-chart-range]');
        const canvas = card.querySelector('canvas[data-chart]');

        if (!select || !canvas || !canvas.dataset.chartEndpoint) {
            return;
        }

        select.addEventListener('change', () => loadRange(card, select));
    });
}

function watchTheme() {
    // theme.js dispatches an event; the observer is a fallback for the first
    // paint and for other scripts that change the attribute directly.
    document.addEventListener('edu:theme-changed', () => {
        registry.forEach(restyle);
    });

    new MutationObserver(() => registry.forEach(restyle)).observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-bs-theme'],
    });
}

/**
 * Redraw every chart after the viewport changes shape.
 *
 * Chart.js already watches its container, but orientation changes, the
 * on-screen keyboard and the collapsing desktop sidebar can all settle one
 * frame late. One rAF coalesces the burst of resize events.
 */
function watchViewport() {
    let frame = null;

    const resize = () => {
        if (frame !== null) {
            return;
        }

        frame = window.requestAnimationFrame(() => {
            frame = null;
            registry.forEach((chart) => chart.resize());
        });
    };

    window.addEventListener('resize', resize);
    window.addEventListener('orientationchange', resize);
    document.addEventListener('edu:sidebar-toggled', resize);
    document.addEventListener('edu:theme-changed', resize);
}

async function bootCharts() {
    // No canvas, no download: this is the biggest mobile win of Phase 3D.
    if (! document.querySelector('canvas[data-chart]')) {
        return;
    }

    try {
        await loadChartJs();
    } catch (error) {
        console.error('[EduConnect] Chart.js could not be loaded', error);

        return;
    }

    renderAllCharts();
    bindRangeSelectors();
    watchTheme();
    watchViewport();
}

window.EduCharts = {
    renderAll: renderAllCharts,
    updateSeries,
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootCharts);
} else {
    bootCharts();
}
