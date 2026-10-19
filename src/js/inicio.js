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

    /* ---------- 1b. Imagen del hero: se inclina hacia el cursor ----------
       Solo en computadores con mouse y si el sistema no pidió menos movimiento.
       requestAnimationFrame: calculamos como máximo una vez por cuadro. */
    const zonaHero = document.querySelector("[data-hero-inclinar]");
    const imagenHero = zonaHero ? zonaHero.querySelector(".imagen-hero-estudiantes") : null;
    const resplandor = document.querySelector(".fondo-resplandor-hero");
    const conMouse = window.matchMedia("(hover: hover) and (pointer: fine)").matches;
    const sinMovimiento = window.matchMedia("(prefers-reduced-motion: reduce)");

    if (zonaHero && imagenHero && conMouse) {
        const area = zonaHero.parentElement; // toda la columna reacciona, no solo la imagen
        let pendiente = null;

        const mover = () => {
            if (!pendiente) return;
            const { x, y } = pendiente; // de -0.5 a 0.5
            imagenHero.style.transform =
                `perspective(900px) rotateX(${(-y * 8).toFixed(2)}deg) rotateY(${(x * 10).toFixed(2)}deg) scale(1.03)`;
            if (resplandor) {
                resplandor.style.transform =
                    `translate(calc(-50% + ${(-x * 24).toFixed(1)}px), calc(-50% + ${(-y * 24).toFixed(1)}px))`;
            }
            pendiente = null;
        };

        area.addEventListener("pointermove", (e) => {
            if (sinMovimiento.matches) return;
            const r = area.getBoundingClientRect();
            pendiente = { x: (e.clientX - r.left) / r.width - 0.5, y: (e.clientY - r.top) / r.height - 0.5 };
            zonaHero.classList.add("esta-encima");
            requestAnimationFrame(mover);
        });

        area.addEventListener("pointerleave", () => {
            pendiente = null;
            zonaHero.classList.remove("esta-encima");
            imagenHero.style.transform = "";      // vuelve suave con la transición del CSS
            if (resplandor) resplandor.style.transform = "";
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
