/**
 * BiGlobal · Página principal
 *
 * Reemplaza al antiguo Progreso.js. Ese archivo guardaba un "progreso"
 * falso en localStorage; el progreso real ahora vive en la base de datos
 * (tabla progreso) y se ve en el panel del estudiante.
 *
 * Aquí solo quedan dos cosas:
 *   1. El boletín del footer (todavía no guarda correos: lo decimos claro).
 *   2. Cerrar el menú del celular al tocar un enlace.
 */

document.addEventListener("DOMContentLoaded", () => {

    /* ---------- 1. Boletín ---------- */
    const formBoletin = document.getElementById("form-boletin");
    const campoBoletin = document.getElementById("campo-boletin");
    const mensajeBoletin = document.getElementById("mensaje-boletin");

    if (formBoletin && campoBoletin && mensajeBoletin) {
        formBoletin.addEventListener("submit", (evento) => {
            evento.preventDefault();

            if (!campoBoletin.checkValidity() || campoBoletin.value.trim() === "") {
                mensajeBoletin.className = "small mb-0 es-error";
                mensajeBoletin.textContent = "Escribe un correo válido, por ejemplo nombre@correo.com.";
                campoBoletin.setAttribute("aria-invalid", "true");
                campoBoletin.focus();
                return;
            }

            // TODO (siguiente versión): enviar a un endpoint PHP que guarde
            // el correo en una tabla "boletin_suscriptores".
            campoBoletin.removeAttribute("aria-invalid");
            mensajeBoletin.className = "small mb-0 es-info";
            mensajeBoletin.textContent = "¡Gracias! El boletín abre muy pronto. Mientras tanto, crea tu cuenta gratis para empezar a aprender.";
        });
    }

    /* ---------- 2. Menú de celular ---------- */
    const menu = document.getElementById("menuPrincipal");

    if (menu && window.bootstrap) {
        menu.querySelectorAll(".nav-link").forEach((enlace) => {
            enlace.addEventListener("click", () => {
                if (menu.classList.contains("show")) {
                    window.bootstrap.Collapse.getOrCreateInstance(menu).hide();
                }
            });
        });
    }
});
