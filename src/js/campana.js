/**
 * BiGlobal · Campana de notificaciones de la barra superior
 * (app/Views/panel/_campana.php). Sin JavaScript también funciona.
 */
/* ---------- Campana de notificaciones (todos los roles) ----------
   La cargan el panel (admin, super admin, docente) y el estudiante.
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
