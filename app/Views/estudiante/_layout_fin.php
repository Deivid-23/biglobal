<?php /* Parte inferior del layout del estudiante: cierra <main> y agrega la barra de celular. */ ?>
        </main>
    </div>
</div>

<!-- ================= BARRA INFERIOR (celular) ================= -->
<nav class="est-tabbar" aria-label="Principal">
    <?php foreach ($menu as $item): $activo = $paginaActiva === $item["page"]; ?>
        <a class="est-tabbar__enlace <?= $activo ? "es-activo" : "" ?>"
           href="index.php?page=<?= e($item["page"]) ?>"
           <?= $activo ? 'aria-current="page"' : "" ?>>
            <i class="fa-solid <?= e($item["icono"]) ?>" aria-hidden="true"></i>
            <span><?= e($item["page"] === "cursos" ? "Explorar" : $item["texto"]) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<script>
/* El aviso flotante se oculta solo a los 4 segundos (o al tocarlo). */
(function () {
    var toast = document.querySelector("[data-toast]");
    if (!toast) return;
    function ocultar() {
        toast.dataset.oculto = "true";
        setTimeout(function () { toast.remove(); }, 300);
    }
    toast.addEventListener("click", ocultar);
    setTimeout(ocultar, 4000);
})();
</script>
</body>
</html>
