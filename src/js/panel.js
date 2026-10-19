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

    /* ---------- 2b. Modal "Editar": rellena el formulario con los datos de la fila ----------
       El botón trae data-editar='{"id":5,"nombre":"…"}' y data-editar-modal="modal-editar".
       Cada valor va al campo del formulario que tiene ese mismo name. */
    document.querySelectorAll("[data-editar]").forEach((boton) => {
        boton.addEventListener("click", () => {
            const modal = document.getElementById(boton.dataset.editarModal);
            const form = modal ? modal.querySelector("form") : null;
            if (!form || !modal.showModal) return;

            let datos = {};
            try { datos = JSON.parse(boton.dataset.editar || "{}"); } catch (e) { return; }

            // Quitar opciones "(valor anterior)" que dejó otra fila
            form.querySelectorAll("option[data-temporal]").forEach((o) => o.remove());

            Object.entries(datos).forEach(([nombre, valor]) => {
                const campo = form.elements.namedItem(nombre);
                if (!campo || !("value" in campo)) return;
                const texto = valor === null ? "" : String(valor);

                // Si un select no tiene ese valor (dato viejo), se agrega para no perderlo
                if (campo.tagName === "SELECT" && texto !== ""
                    && ![...campo.options].some((o) => o.value === texto)) {
                    const opcion = new Option(`${texto} (valor anterior)`, texto);
                    opcion.dataset.temporal = "true";
                    campo.add(opcion);
                }
                campo.value = texto;
            });

            // "Editar curso · Inglés A1"
            const titulo = modal.querySelector("[data-editar-titulo]");
            if (titulo) titulo.textContent = `${titulo.dataset.editarTitulo} · ${boton.dataset.editarNombre || ""}`;

            modal.showModal();
            const primero = form.querySelector("input:not([type=hidden]), select, textarea");
            if (primero) primero.focus();
        });
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

/* ---------- Campana de notificaciones (admin y super admin) ----------
   - Abre/cierra con clic, Escape o clic afuera.
   - Al tocar un aviso lo marca como leído (sendBeacon: no frena la navegación).
   - "Marcar todas" se envía con fetch; sin JavaScript es un formulario normal. */
document.addEventListener("DOMContentLoaded", () => {
    const campana = document.querySelector("[data-campana]");
    if (!campana) return;

    const boton = campana.querySelector("[data-campana-boton]");
    const panel = campana.querySelector("[data-campana-panel]");
    const contador = campana.querySelector("[data-campana-contador]");
    const formTodas = campana.querySelector("[data-campana-todas]");
    const urlLeer = "../auth/notificaciones_leer.php";
    const token = () => document.documentElement.getAttribute("data-csrf") || "";

    const abrir = () => {
        panel.hidden = false;
        boton.setAttribute("aria-expanded", "true");
        const primero = panel.querySelector("a, button");
        if (primero) primero.focus();
    };

    const cerrar = (devolverFoco) => {
        if (panel.hidden) return;
        panel.hidden = true;
        boton.setAttribute("aria-expanded", "false");
        if (devolverFoco) boton.focus();
    };

    const ponerContador = (n) => {
        contador.hidden = n <= 0;
        contador.textContent = n > 9 ? "9+" : String(n);
        boton.setAttribute("aria-label", n > 0 ? `Notificaciones: ${n} sin leer` : "Notificaciones");
        if (formTodas) formTodas.hidden = n <= 0;
    };

    boton.addEventListener("click", () => (panel.hidden ? abrir() : cerrar(false)));

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && !panel.hidden) cerrar(true);
    });

    document.addEventListener("click", (e) => {
        if (!campana.contains(e.target)) cerrar(false);
    });

    // Tocar un aviso: marcarlo como leído y seguir al enlace
    panel.addEventListener("click", (e) => {
        const aviso = e.target.closest("[data-aviso-id]");
        if (!aviso || !aviso.classList.contains("es-nueva")) return;
        const datos = new FormData();
        datos.append("csrf_token", token());
        datos.append("id", aviso.dataset.avisoId);
        if (navigator.sendBeacon) navigator.sendBeacon(urlLeer, datos);
        aviso.classList.remove("es-nueva");
        const punto = aviso.querySelector(".pnl-aviso__punto");
        if (punto) punto.remove();
        ponerContador(campana.querySelectorAll(".pnl-aviso.es-nueva").length);
        if (aviso.getAttribute("href") === "#") e.preventDefault();
    });

    // Marcar todas sin recargar la página
    if (formTodas) {
        formTodas.addEventListener("submit", async (e) => {
            e.preventDefault();
            const datos = new FormData(formTodas);
            datos.delete("volver"); // así el servidor responde JSON
            try {
                const respuesta = await fetch(urlLeer, { method: "POST", body: datos, credentials: "same-origin" });
                if (!respuesta.ok) throw new Error(String(respuesta.status));
                campana.querySelectorAll(".pnl-aviso.es-nueva").forEach((a) => a.classList.remove("es-nueva"));
                campana.querySelectorAll(".pnl-aviso__punto").forEach((p) => p.remove());
                ponerContador(0);
                // El aviso de "cargando" de los formularios no aplica aquí
                const enviar = formTodas.querySelector('button[type="submit"]');
                if (enviar) delete enviar.dataset.cargando;
                boton.focus();
            } catch (err) {
                formTodas.submit(); // si falla el fetch, envío normal
            }
        });
    }
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
