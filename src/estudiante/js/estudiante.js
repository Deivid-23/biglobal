/* =========================================================
   Panel del estudiante: pequeños comportamientos.
   (Antes estaban como <script> dentro del HTML; se movieron aquí
   para que la política de seguridad CSP pueda bloquear cualquier
   script en línea.)
========================================================= */
(function () {
    "use strict";

    /* El aviso flotante se oculta solo a los 4 segundos (o al tocarlo). */
    var toast = document.querySelector("[data-toast]");
    if (toast) {
        var ocultar = function () {
            toast.dataset.oculto = "true";
            setTimeout(function () { toast.remove(); }, 300);
        };
        toast.addEventListener("click", ocultar);
        setTimeout(ocultar, 4000);
    }

    /* Perfil: muestra "contraseña actual" solo cuando el correo cambia. */
    var correo = document.getElementById("correo");
    var bloque = document.getElementById("confirmar-correo");
    if (correo && bloque) {
        correo.addEventListener("input", function () {
            bloque.hidden = correo.value.trim().toLowerCase() === correo.dataset.original;
        });
    }

    /* Certificados: "Copiar enlace" copia la dirección pública de verificación. */
    document.querySelectorAll("[data-copiar-certificado]").forEach(function (boton) {
        boton.addEventListener("click", function () {
            var url = new URL("../certificado.php?codigo=" + encodeURIComponent(boton.dataset.copiarCertificado), location.href).href;
            var texto = boton.querySelector("span");
            var listo = function () {
                texto.textContent = "¡Enlace copiado!";
                setTimeout(function () { texto.textContent = "Copiar enlace"; }, 2500);
            };
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(listo, function () { window.prompt("Copia este enlace:", url); });
            } else {
                window.prompt("Copia este enlace:", url);
            }
        });
    });
})();

/* Menú lateral en celular: se abre con ☰ y se cierra con la X, el fondo o Escape. */
(function () {
    "use strict";
    var menu = document.getElementById("est-menu");
    var abrir = document.querySelector("[data-abrir-menu]");
    var velo = document.querySelector(".est-velo");
    if (!menu || !abrir || !velo) return;

    function cerrar(devolverFoco) {
        if (!menu.classList.contains("esta-abierta")) return;
        menu.classList.remove("esta-abierta");
        velo.classList.remove("esta-visible");
        document.body.classList.remove("menu-abierto");
        abrir.setAttribute("aria-expanded", "false");
        if (devolverFoco) abrir.focus();
    }

    abrir.addEventListener("click", function () {
        menu.classList.add("esta-abierta");
        velo.classList.add("esta-visible");
        document.body.classList.add("menu-abierto");
        abrir.setAttribute("aria-expanded", "true");
        var activo = menu.querySelector(".est-nav__enlace.es-activo") || menu.querySelector("a");
        if (activo) activo.focus();
    });
    document.querySelectorAll("[data-cerrar-menu]").forEach(function (el) {
        el.addEventListener("click", function () { cerrar(true); });
    });
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") cerrar(true);
    });
    // Si la pantalla crece a computador con el cajón abierto, se cierra
    window.matchMedia("(min-width: 900px)").addEventListener("change", function () { cerrar(false); });
})();

/* Zona horaria: si el navegador está en otra zona, se guarda (la racha cuenta los días con esa hora).
   El servidor solo acepta un cambio al día. */
(function () {
    "use strict";
    var html = document.documentElement;
    var zona = "";
    try { zona = Intl.DateTimeFormat().resolvedOptions().timeZone || ""; } catch (e) { return; }
    if (!zona || !html.dataset.csrf || zona === html.dataset.zona) return;
    var clave = "biglobal-zona-enviada";
    try { if (sessionStorage.getItem(clave) === zona) return; sessionStorage.setItem(clave, zona); } catch (e) { /* sin almacenamiento: se intenta igual */ }
    var datos = new FormData();
    datos.append("csrf_token", html.dataset.csrf);
    datos.append("zona", zona);
    fetch("guardar_zona.php", { method: "POST", body: datos, credentials: "same-origin" }).catch(function () {});
})();

/* Formularios con data-confirmar="¿Seguro…?": piden confirmación antes de enviarse. */
document.querySelectorAll("form[data-confirmar]").forEach(function (form) {
    form.addEventListener("submit", function (evento) {
        if (!window.confirm(form.dataset.confirmar)) {
            evento.preventDefault();
        }
    });
});
