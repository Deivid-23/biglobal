/* =========================================================
   Página pública del certificado (certificado.php).
   - Código QR que lleva a esta misma página de verificación.
     Si la librería no carga, el cuadro se oculta y el código sigue visible.
   - Botón "Imprimir o guardar en PDF".
========================================================= */
window.addEventListener("load", function () {
    "use strict";

    var imprimir = document.querySelector("[data-imprimir]");
    if (imprimir) {
        imprimir.addEventListener("click", function () { window.print(); });
    }

    var caja = document.querySelector("[data-qr]");
    if (!caja) return;
    if (typeof QRCode === "undefined") { caja.hidden = true; return; }

    var url = caja.dataset.qr;
    if (url.charAt(0) === "/") url = location.origin + url;
    new QRCode(caja, { text: url, width: 112, height: 112, colorDark: "#1E3A8A", colorLight: "#FFFFFF", correctLevel: QRCode.CorrectLevel.M });
    caja.removeAttribute("title");
});
