document.addEventListener('DOMContentLoaded', () => {
    // Password show/hide toggle
    document.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var targetId = btn.getAttribute('data-password-toggle');
            var input = document.getElementById(targetId);
            var icon = btn.querySelector('i');
            if (!input) return;
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    initThemeToggle();
    initReportFilter();
    initCharts();
});

// ──────────────────────────────────────────────
//  Dark mode
// ──────────────────────────────────────────────
function initThemeToggle() {
    const btn = document.getElementById('themeToggleBtn');
    if (!btn) return;

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        document.documentElement.setAttribute('data-theme', theme);
        const icon = btn.querySelector('i');
        if (icon) {
            icon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        }
    }

    btn.addEventListener('click', () => {
        const current = localStorage.getItem('dk_theme') || 'light';
        const next = current === 'dark' ? 'light' : 'dark';
        localStorage.setItem('dk_theme', next);
        applyTheme(next);
    });

    // Sync icon with current saved theme
    const saved = localStorage.getItem('dk_theme') || 'light';
    applyTheme(saved);
}

function initReportFilter() {
    const filterSelect = document.getElementById('filterSelect');
    if (!filterSelect) return;

    const customFields = document.querySelectorAll('.custom-range');
    filterSelect.addEventListener('change', () => {
        const custom = filterSelect.value === 'custom';
        customFields.forEach((el) => el.classList.toggle('d-none', !custom));
    });
}

async function initCharts() {
    const categoryCanvas = document.getElementById('chartCategory');
    const dailyCanvas = document.getElementById('chartDaily');
    const trendCanvas = document.getElementById('chartTrend');

    if (!categoryCanvas || !dailyCanvas || !trendCanvas) return;

    const url = new URL(window.location.origin + window.location.pathname.replace('/reports', '/reports/charts'));
    const params = new URLSearchParams(window.location.search);
    params.forEach((v, k) => url.searchParams.set(k, v));

    try {
        const response = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await response.json();

        new Chart(categoryCanvas, {
            type: 'pie',
            data: {
                labels: data.category.map((x) => x.name),
                datasets: [{
                    data: data.category.map((x) => Number(x.total)),
                    backgroundColor: ['#1d4ed8', '#0ea5a4', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6'],
                }],
            },
            options: { plugins: { legend: { position: 'bottom' } } },
        });

        new Chart(dailyCanvas, {
            type: 'bar',
            data: {
                labels: data.daily.map((x) => x.day_label),
                datasets: [{
                    label: 'Expense by Day',
                    data: data.daily.map((x) => Number(x.total)),
                    backgroundColor: '#2563eb',
                    borderRadius: 8,
                }],
            },
            options: { scales: { y: { beginAtZero: true } } },
        });

        const trendMap = new Map(data.trend.map((x) => [Number(x.month_num), Number(x.total)]));
        const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Monthly Trend',
                    data: monthLabels.map((_, idx) => trendMap.get(idx + 1) || 0),
                    borderColor: '#0ea5a4',
                    tension: 0.35,
                    fill: false,
                }],
            },
            options: { scales: { y: { beginAtZero: true } } },
        });
    } catch (error) {
        console.error('Failed to load report charts', error);
    }
}
