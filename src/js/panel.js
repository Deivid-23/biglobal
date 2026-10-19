/**
 * BiGlobal · Paneles de gestión (admin, super administrador, profesor)
 *
 * 1. Menú lateral en celular (se abre como cajón).
 * 2. Modales <dialog> para crear registros.
 * 3. Confirmación antes de acciones peligrosas (data-confirmar="...").
 * 4. Avisos flotantes que se cierran solos.
 * 5. Gráficos con Chart.js, con colores del tema actual.
 */
document.addEventListener("DOMContentLoaded", () => {

    /* ---------- 1. Menú lateral ---------- */
    const menu = document.getElementById("pnl-menu");
    const velo = document.querySelector("[data-cerrar-menu]");
    const botonMenu = document.querySelector("[data-abrir-menu]");

    function abrirMenu() {
        menu.classList.add("esta-abierta");
        velo.classList.add("esta-visible");
        botonMenu.setAttribute("aria-expanded", "true");
        const primero = menu.querySelector("a");
        if (primero) primero.focus();
    }

    function cerrarMenu() {
        menu.classList.remove("esta-abierta");
        velo.classList.remove("esta-visible");
        botonMenu.setAttribute("aria-expanded", "false");
    }

    if (menu && velo && botonMenu) {
        botonMenu.addEventListener("click", abrirMenu);
        velo.addEventListener("click", cerrarMenu);
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && menu.classList.contains("esta-abierta")) {
                cerrarMenu();
                botonMenu.focus();
            }
        });
    }

    /* ---------- 2. Modales ---------- */
    document.querySelectorAll("[data-abrir-modal]").forEach((boton) => {
        boton.addEventListener("click", () => {
            const modal = document.getElementById(boton.dataset.abrirModal);
            if (modal && modal.showModal) modal.showModal();
        });
    });

    document.querySelectorAll("dialog.pnl-modal").forEach((modal) => {
        modal.querySelectorAll("[data-cerrar-modal]").forEach((b) => b.addEventListener("click", () => modal.close()));
        // Clic en el fondo oscuro: cerrar
        modal.addEventListener("click", (e) => { if (e.target === modal) modal.close(); });
    });

    /* ---------- 3. Confirmar acciones peligrosas ---------- */
    document.querySelectorAll("form[data-confirmar]").forEach((form) => {
        form.addEventListener("submit", (e) => {
            if (!window.confirm(form.dataset.confirmar)) e.preventDefault();
        });
    });

    /* Evitar doble envío: el botón muestra "cargando" */
    document.querySelectorAll(".pnl-body form").forEach((form) => {
        form.addEventListener("submit", (e) => {
            if (e.defaultPrevented) return;
            const boton = form.querySelector('button[type="submit"]');
            if (boton) { boton.dataset.cargando = "true"; }
        });
    });

    /* ---------- 4. Avisos flotantes ---------- */
    const toast = document.querySelector("[data-toast]");
    if (toast) {
        const ocultar = () => { toast.dataset.oculto = "true"; setTimeout(() => toast.remove(), 300); };
        toast.addEventListener("click", ocultar);
        setTimeout(ocultar, 4500);
    }

    /* ---------- 5. Gráficos ---------- */
    const lienzos = document.querySelectorAll("canvas[data-etiquetas]");
    if (!lienzos.length) return;

    const graficos = {};
    const css = (v) => getComputedStyle(document.documentElement).getPropertyValue(v).trim();

    function paleta() {
        return [css("--azul-600"), css("--verde-600") || "#16A34A", "#0284C7", "#EA580C", "#7C3AED", "#DB2777", "#CA8A04", "#64748B"];
    }

    function crear(canvas, tipo) {
        const colores = paleta();
        const circular = tipo === "pie" || tipo === "doughnut";
        const texto = css("--texto-suave");
        const linea = css("--borde");

        return new Chart(canvas.getContext("2d"), {
            type: tipo,
            data: {
                labels: JSON.parse(canvas.dataset.etiquetas || "[]"),
                datasets: [{
                    label: "Total",
                    data: JSON.parse(canvas.dataset.valores || "[]"),
                    backgroundColor: circular ? colores : colores[0],
                    borderColor: circular ? css("--superficie") : colores[0],
                    borderWidth: circular ? 2 : 2,
                    borderRadius: tipo === "bar" ? 8 : 0,
                    tension: 0.35,
                    pointRadius: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: window.matchMedia("(prefers-reduced-motion: reduce)").matches ? false : { duration: 300 },
                plugins: {
                    legend: { display: circular, position: "bottom", labels: { color: texto, boxWidth: 12 } },
                },
                scales: circular ? {} : {
                    x: { grid: { display: false }, ticks: { color: texto } },
                    y: { beginAtZero: true, grid: { color: linea }, ticks: { color: texto, precision: 0 } },
                },
            },
        });
    }

    function dibujarTodos() {
        if (typeof Chart === "undefined") return;
        lienzos.forEach((canvas) => {
            const selector = document.querySelector(`[data-grafico-tipo="${canvas.id}"]`);
            let tipo = canvas.dataset.tipo || "bar";
            try { tipo = localStorage.getItem("grafico-" + canvas.id) || tipo; } catch (e) { /* sin localStorage */ }
            if (selector) selector.value = tipo;
            if (graficos[canvas.id]) graficos[canvas.id].destroy();
            graficos[canvas.id] = crear(canvas, tipo);
        });
    }

    document.addEventListener("change", (e) => {
        const id = e.target.dataset ? e.target.dataset.graficoTipo : null;
        if (!id) return;
        try { localStorage.setItem("grafico-" + id, e.target.value); } catch (err) { /* sin localStorage */ }
        const canvas = document.getElementById(id);
        if (graficos[id]) graficos[id].destroy();
        graficos[id] = crear(canvas, e.target.value);
    });

    // Al cambiar de tema, redibujar con los colores nuevos
    new MutationObserver(dibujarTodos).observe(document.documentElement, { attributes: true, attributeFilter: ["data-tema"] });

    if (typeof Chart !== "undefined") dibujarTodos();
    else window.addEventListener("load", dibujarTodos);
});

/* Selects que guardan solos al cambiar (ej. el rol en "Cuentas y roles") */
document.addEventListener("change", (e) => {
    if (e.target.matches && e.target.matches("select[data-enviar-al-cambiar]")) {
        const form = e.target.form;
        if (form && window.confirm("¿Cambiar el rol de esta cuenta?")) form.submit();
        else if (form) form.reset();
    }
});

/* "Otro color" en Apariencia: al mover el selector, marcar su opción */
document.addEventListener("input", (e) => {
    if (e.target.matches && e.target.matches(".pnl-color__selector")) {
        const radio = e.target.closest(".pnl-color").querySelector(":scope > input[type=radio]");
        if (radio) radio.checked = true;
    }
});
