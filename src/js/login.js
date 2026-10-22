/* =========================================================
   BiGlobal · Login y registro
   ---------------------------------------------------------
   - Cambia entre las pestañas "Iniciar sesión" y "Crear cuenta".
   - Envía los formularios a login.php / registro.php con fetch.
   - Muestra los mensajes dentro de la tarjeta (sin alert()).
   - Lee ?error=... que manda el login con Google.
========================================================= */

/* Mensajes para los errores que devuelve google_callback.php */
const ERRORES_GOOGLE = {
    state_invalido:    "La sesión con Google expiró. Inténtalo de nuevo.",
    acceso_cancelado:  "Cancelaste el inicio con Google.",
    sin_codigo:        "Google no envió la autorización. Inténtalo de nuevo.",
    token_invalido:    "No pudimos validar tu cuenta de Google. Inténtalo de nuevo.",
    sin_access_token:  "No pudimos validar tu cuenta de Google. Inténtalo de nuevo.",
    userinfo_fallido:  "No pudimos leer los datos de tu cuenta de Google.",
    datos_incompletos: "Tu cuenta de Google no compartió el correo. Usa otra cuenta.",
    cuenta_inactiva:   "Tu cuenta está inactiva. Escríbenos para revisarla.",
    correo_ya_existe:  "Ya existe una cuenta con ese correo. Inicia sesión.",
    google_no_config:  "El inicio con Google no está disponible en este momento."
};

const MENSAJES_OK = {
    password_actualizada: "¡Listo! Tu contraseña se actualizó. Ya puedes iniciar sesión.",
    eliminacion_programada: "Programamos la eliminación de tu cuenta en 30 días y te enviamos un correo. Si cambias de opinión, inicia sesión antes de esa fecha."
};

let reglasPassword = { minimo: 8, mayuscula: false, numero: false };

/* ---------------------------------------------------------
   Mensaje dentro de la tarjeta
--------------------------------------------------------- */
function mostrarMensaje(texto, tipo = "error") {
    const caja = document.getElementById("mensaje-auth");
    if (!caja) return;

    const iconos = { error: "fa-circle-exclamation", exito: "fa-circle-check", info: "fa-circle-info" };

    caja.innerHTML = "";
    const aviso = document.createElement("div");
    aviso.className = "aviso aviso--" + tipo;
    aviso.setAttribute("data-nuevo", "");

    const icono = document.createElement("i");
    icono.className = "fa-solid " + (iconos[tipo] || iconos.info);
    icono.setAttribute("aria-hidden", "true");

    const parrafo = document.createElement("span");
    parrafo.textContent = texto; // textContent: nunca interpreta HTML

    aviso.append(icono, parrafo);
    caja.appendChild(aviso);

    // En celular el mensaje puede quedar arriba, fuera de la vista: lo mostramos
    const sinMovimiento = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    const rect = caja.getBoundingClientRect();
    if (rect.top < 0 || rect.bottom > window.innerHeight) {
        caja.scrollIntoView({ behavior: sinMovimiento ? "auto" : "smooth", block: "center" });
    }
}

function limpiarMensaje() {
    const caja = document.getElementById("mensaje-auth");
    if (caja) caja.innerHTML = "";
}

/* ---------------------------------------------------------
   Pestañas
--------------------------------------------------------- */
function activarPestana(nombre, enfocar = true) {
    const pestanas = document.querySelector(".pestanas");
    const tabLogin = document.getElementById("tab-login");
    const tabRegistro = document.getElementById("tab-registro");
    const formLogin = document.getElementById("form-login");
    const formRegistro = document.getElementById("form-register");

    const esRegistro = nombre === "registro";

    pestanas.dataset.activa = esRegistro ? "registro" : "login";
    tabLogin.setAttribute("aria-selected", String(!esRegistro));
    tabRegistro.setAttribute("aria-selected", String(esRegistro));
    tabLogin.tabIndex = esRegistro ? -1 : 0;
    tabRegistro.tabIndex = esRegistro ? 0 : -1;
    formLogin.hidden = esRegistro;
    formRegistro.hidden = !esRegistro;

    document.title = (esRegistro ? "Crea tu cuenta" : "Inicia sesión") + " | BiGlobal";
    limpiarMensaje();

    if (enfocar) {
        const primerCampo = (esRegistro ? formRegistro : formLogin).querySelector("input:not([type=hidden])");
        if (primerCampo) primerCampo.focus();
    }
}

/* ---------------------------------------------------------
   Ver / ocultar contraseña
--------------------------------------------------------- */
function alternarPassword(boton) {
    const input = document.getElementById(boton.dataset.ver);
    const icono = boton.querySelector("i");
    if (!input) return;

    const mostrar = input.type === "password";
    input.type = mostrar ? "text" : "password";
    icono.className = mostrar ? "fa-regular fa-eye-slash" : "fa-regular fa-eye";
    boton.setAttribute("aria-label", mostrar ? "Ocultar contraseña" : "Mostrar contraseña");
}

/* ---------------------------------------------------------
   Seguridad de la contraseña (solo orientativo)
--------------------------------------------------------- */
function medirPassword(password) {
    let nivel = 0;
    if (password.length > 0) nivel++;
    if (password.length >= reglasPassword.minimo) nivel++;
    if (/[A-Z]/.test(password) && /\d/.test(password)) nivel++;
    if (password.length >= 10 && /[^A-Za-z0-9]/.test(password)) nivel++;

    document.getElementById("medidor").dataset.nivel = String(nivel);
    validarCoincidencia();
}

function validarCoincidencia() {
    const password = document.getElementById("reg-password");
    const confirmar = document.getElementById("reg-confirm");
    const error = document.getElementById("match-error");

    const noCoinciden = confirmar.value.length > 0 && password.value !== confirmar.value;
    error.hidden = !noCoinciden;
    confirmar.setAttribute("aria-invalid", String(noCoinciden));
}

/* Texto de ayuda con las reglas que definió el administrador */
function pintarReglas() {
    const partes = ["Mínimo " + reglasPassword.minimo + " caracteres"];
    if (reglasPassword.mayuscula) partes.push("una mayúscula");
    if (reglasPassword.numero) partes.push("un número");
    document.getElementById("reglas-password").textContent = partes.join(", ") + ".";
}

/* Revisa la contraseña en el navegador antes de enviarla */
function errorEnPassword(password) {
    if (password.length < reglasPassword.minimo) return "La contraseña debe tener al menos " + reglasPassword.minimo + " caracteres.";
    if (reglasPassword.mayuscula && !/[A-ZÁÉÍÓÚÑ]/.test(password)) return "La contraseña debe tener al menos una letra mayúscula.";
    if (reglasPassword.numero && !/\d/.test(password)) return "La contraseña debe tener al menos un número.";
    return null;
}

/* ---------------------------------------------------------
   Token CSRF
--------------------------------------------------------- */
async function obtenerCsrf() {
    const res = await fetch("csrf_token.php", { credentials: "same-origin" });
    const datos = await res.json();

    if (datos.reglas) {
        reglasPassword = datos.reglas;
        pintarReglas();
    }

    document.querySelectorAll('input[name="csrf_token"]').forEach(input => {
        input.value = datos.csrf_token;
    });

    return datos.csrf_token;
}

/* ---------------------------------------------------------
   Enviar login o registro
--------------------------------------------------------- */
async function enviarFormulario(evento, tipo) {
    evento.preventDefault();
    limpiarMensaje();

    const form = evento.currentTarget;
    const boton = form.querySelector('button[type="submit"]');
    const datos = new FormData();
    let url;

    if (tipo === "registro") {
        url = "registro.php";

        const nombre = document.getElementById("reg-name").value.trim();
        const correo = document.getElementById("reg-email").value.trim();
        const password = document.getElementById("reg-password").value;
        const confirmar = document.getElementById("reg-confirm").value;

        if (!nombre || !correo || !password || !confirmar) {
            return mostrarMensaje("Completa todos los campos para crear tu cuenta.");
        }
        if (!document.getElementById("reg-email").checkValidity()) {
            return mostrarMensaje("Revisa tu correo: parece que le falta algo (ej. nombre@correo.com).");
        }
        const errorPassword = errorEnPassword(password);
        if (errorPassword) return mostrarMensaje(errorPassword);
        if (password !== confirmar) return mostrarMensaje("Las contraseñas no coinciden.");
        if (!document.getElementById("terms").checked) {
            return mostrarMensaje("Para crear tu cuenta acepta los términos y la política de privacidad.");
        }

        const rol = document.querySelector('input[name="reg-role"]:checked')?.value || "estudiante";

        datos.append("nombre", nombre);
        datos.append("correo", correo);
        datos.append("password", password);
        datos.append("confirm_password", confirmar);
        datos.append("rol", rol);
    } else {
        url = "login.php";

        const correo = document.getElementById("login-email").value.trim();
        const password = document.getElementById("login-password").value;

        if (!correo || !password) {
            return mostrarMensaje("Escribe tu correo y tu contraseña.");
        }

        datos.append("correo", correo);
        datos.append("password", password);
    }

    boton.dataset.cargando = "true";
    boton.disabled = true;

    try {
        let token = form.querySelector('input[name="csrf_token"]').value;
        if (!token) token = await obtenerCsrf();
        datos.append("csrf_token", token);

        const respuesta = await fetch(url, { method: "POST", body: datos, credentials: "same-origin" });
        const texto = await respuesta.text();

        let resultado;
        try {
            resultado = JSON.parse(texto);
        } catch (e) {
            console.error("Respuesta no válida del servidor:", texto);
            throw new Error("respuesta");
        }

        if (!resultado.success) {
            mostrarMensaje(resultado.message || "No pudimos completar la operación.");
            return;
        }

        if (resultado.redirect) {
            mostrarMensaje(resultado.message || "¡Listo!", "exito");
            window.location.href = resultado.redirect;
            return;
        }

        mostrarMensaje(resultado.message || "¡Listo!", "exito");

    } catch (error) {
        mostrarMensaje("No pudimos conectar con el servidor. Revisa tu internet e inténtalo de nuevo.");
    } finally {
        boton.dataset.cargando = "false";
        boton.disabled = false;
    }
}

/* ---------------------------------------------------------
   Inicio
--------------------------------------------------------- */
document.addEventListener("DOMContentLoaded", () => {

    // Pestañas: clic y flechas del teclado
    const tabLogin = document.getElementById("tab-login");
    const tabRegistro = document.getElementById("tab-registro");

    tabLogin.addEventListener("click", () => activarPestana("login"));
    tabRegistro.addEventListener("click", () => activarPestana("registro"));

    document.querySelector(".pestanas").addEventListener("keydown", (e) => {
        if (e.key === "ArrowRight" || e.key === "ArrowLeft") {
            const vaARegistro = document.querySelector(".pestanas").dataset.activa === "login";
            activarPestana(vaARegistro ? "registro" : "login", false);
            (vaARegistro ? tabRegistro : tabLogin).focus();
        }
    });

    // Botones "ver contraseña"
    document.querySelectorAll("[data-ver]").forEach(boton => {
        boton.addEventListener("click", () => alternarPassword(boton));
    });

    // Medidor y coincidencia de contraseñas
    document.getElementById("reg-password").addEventListener("input", e => medirPassword(e.target.value));
    document.getElementById("reg-confirm").addEventListener("input", validarCoincidencia);

    // Aviso cuando se elige Docente
    document.querySelectorAll('input[name="reg-role"]').forEach(radio => {
        radio.addEventListener("change", () => {
            document.getElementById("role-choice-hint").hidden =
                document.querySelector('input[name="reg-role"]:checked').value !== "docente";
        });
    });

    // Envío de formularios
    document.getElementById("form-login").addEventListener("submit", e => enviarFormulario(e, "login"));
    document.getElementById("form-register").addEventListener("submit", e => enviarFormulario(e, "registro"));

    // ¿Viene de "Registrarse" (#registro) o de un error de Google?
    const params = new URLSearchParams(window.location.search);
    if (window.location.hash === "#registro" || params.get("modo") === "registro") {
        activarPestana("registro", false);
    }

    // Si el enlace cambia solo el #, también cambiamos de pestaña
    window.addEventListener("hashchange", () => {
        activarPestana(window.location.hash === "#registro" ? "registro" : "login", false);
    });

    const error = params.get("error");
    if (error) mostrarMensaje(ERRORES_GOOGLE[error] || "Algo salió mal. Inténtalo de nuevo.");

    const ok = params.get("ok");
    if (ok && MENSAJES_OK[ok]) mostrarMensaje(MENSAJES_OK[ok], "exito");

    // Token CSRF y reglas de contraseña
    obtenerCsrf().catch(() => {
        mostrarMensaje("No pudimos preparar el formulario. Recarga la página.");
    });
});
