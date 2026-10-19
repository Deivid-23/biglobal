/* =========================================================
   TEMA (claro / oscuro)
========================================================= */
(function () {
    const toggle = document.getElementById('themeToggle');
    const icon   = document.getElementById('themeIcon');

    function actualizarIcono() {
        const esOscuro = document.documentElement.classList.contains('dark');
        if (icon) {
            icon.className = esOscuro
                ? 'fa-solid fa-sun text-sm'
                : 'fa-solid fa-moon text-sm';
        }
    }

    actualizarIcono();

    if (toggle) {
        toggle.addEventListener('click', () => {
            const esOscuro = document.documentElement.classList.toggle('dark');
            localStorage.setItem('biglobal-theme', esOscuro ? 'dark' : 'light');
            actualizarIcono();
        });
    }
})();

/* =========================================================
   SIDEBAR RESPONSIVE
========================================================= */
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidebar');
        const toggle  = document.getElementById('sidebarToggle');
        const overlay = document.getElementById('sidebarOverlay');

        if (!sidebar || !toggle || !overlay) return;

        function abrirSidebar() {
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            overlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function cerrarSidebar() {
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('translate-x-0');
            overlay.classList.add('hidden');
            document.body.style.overflow = '';
        }

        toggle.addEventListener('click', () => {
            const abierto = !sidebar.classList.contains('-translate-x-full');
            abierto ? cerrarSidebar() : abrirSidebar();
        });

        overlay.addEventListener('click', cerrarSidebar);

        sidebar.querySelectorAll('a').forEach(a => {
            a.addEventListener('click', () => {
                if (window.innerWidth < 1024) cerrarSidebar();
            });
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) cerrarSidebar();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') cerrarSidebar();
        });
    });
})();

/* =========================================================
   GRÁFICO: Lecciones por curso
========================================================= */
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('chartLecciones');
        if (!canvas || typeof Chart === 'undefined') return;

        const labels  = JSON.parse(canvas.dataset.labels  || '[]');
        const valores = JSON.parse(canvas.dataset.valores || '[]');
        const isDark  = () => document.documentElement.classList.contains('dark');

        new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Lecciones',
                    data: valores,
                    backgroundColor: (isDark() ? '#a855f7' : '#7c3aed') + 'cc',
                    borderColor: isDark() ? '#a855f7' : '#7c3aed',
                    borderWidth: 2,
                    borderRadius: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark() ? '#1a1626' : '#fff',
                        titleColor: isDark() ? '#fff' : '#0f172a',
                        bodyColor: isDark() ? '#cbd5e1' : '#334155',
                        borderColor: isDark() ? '#262033' : '#e2e8f0',
                        borderWidth: 1,
                        padding: 10,
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: isDark() ? '#94a3b8' : '#64748b', font: { size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: isDark() ? 'rgba(148,163,184,.10)' : 'rgba(100,116,139,.12)' },
                        ticks: { color: isDark() ? '#94a3b8' : '#64748b', font: { size: 11 }, precision: 0 }
                    }
                }
            }
        });
    });
})();

/* =========================================================
   FLASH (auto-cierre a los 4 segundos)
========================================================= */
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const flash = document.getElementById('flashDocente');
        if (!flash) return;

        setTimeout(() => {
            flash.classList.add('hide');
            setTimeout(() => flash.remove(), 400);
        }, 4000);
    });
})();