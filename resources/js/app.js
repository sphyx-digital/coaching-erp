import './bootstrap';
import Chart from 'chart.js/auto';

// Sensible global defaults for a clean, modern look.
Chart.defaults.font.family = "'DM Sans', 'Segoe UI', system-ui, sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = '#5a6172';
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.boxWidth = 8;
Chart.defaults.maintainAspectRatio = false;

// Alpine component: <div x-data="chart(config)"><canvas x-ref="canvas"></canvas></div>
// The config is a full Chart.js config object emitted from Blade. Re-mounts
// cleanly whenever Livewire replaces the element (via a changing wire:key).
const inr = (v) => '₹' + Number(v || 0).toLocaleString('en-IN');
const valueOf = (c) => (typeof c.raw === 'number' ? c.raw
    : (c.parsed && c.parsed.y !== undefined ? c.parsed.y
        : c.parsed && c.parsed.x !== undefined ? c.parsed.x : c.parsed));

// Apply value formatting to axes + tooltips when the config asks for it.
function decorate(cfg) {
    cfg.options = cfg.options || {};
    cfg.options.plugins = cfg.options.plugins || {};
    if (cfg._currency || cfg._percent) {
        const fmt = cfg._currency ? inr : (v) => Number(v || 0) + '%';
        cfg.options.plugins.tooltip = Object.assign({}, cfg.options.plugins.tooltip, {
            callbacks: { label: (c) => (c.dataset.label ? c.dataset.label + ': ' : '') + fmt(valueOf(c)) },
        });
        const axis = (cfg.options.indexAxis === 'y') ? 'x' : 'y';
        if (cfg.options.scales && cfg.options.scales[axis]) {
            cfg.options.scales[axis].ticks = Object.assign({}, cfg.options.scales[axis].ticks, { callback: (v) => fmt(v) });
        }
    }
    return cfg;
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('chart', (config) => ({
        instance: null,
        init() {
            this.$nextTick(() => {
                this.instance = new Chart(this.$refs.canvas, decorate(config));
            });
        },
        destroy() {
            if (this.instance) {
                this.instance.destroy();
                this.instance = null;
            }
        },
    }));
});

// PWA: register the service worker for the offline shell.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Registration failures are non-fatal; the app works online regardless.
        });
    });
}
