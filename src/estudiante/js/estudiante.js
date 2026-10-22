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

/* Formularios con data-confirmar="¿Seguro…?": piden confirmación antes de enviarse. */
document.querySelectorAll("form[data-confirmar]").forEach(function (form) {
    form.addEventListener("submit", function (evento) {
        if (!window.confirm(form.dataset.confirmar)) {
            evento.preventDefault();
        }
    });
});
