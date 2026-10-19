# CSS del panel de administrador (Tailwind compilado)

El panel `src/admin/index.php` usa clases de Tailwind (`flex`, `p-5`, `rounded-lg`...).
Antes se cargaba `tailwind.js` (367 KB) que generaba el CSS **en el navegador** cada vez.
Ahora el CSS se genera **una sola vez** en tu computador y se guarda en
`src/admin/css/admin.css` (unos 15 KB, solo con las clases que de verdad se usan).

## ¿Cuándo hay que volver a compilar?

Solo cuando agregues o cambies clases de Tailwind en `src/admin/` o en
`app/Views/partials/admin_*.php`. Si una clase nueva "no hace nada", es porque falta compilar.

## Cómo compilar (desde la raíz del proyecto)

Necesitas Node.js instalado. No hace falta `npm install`:

```bash
npx -y tailwindcss@3.4.17 -c tools/tailwind/tailwind.config.js -i tools/tailwind/entrada.css -o src/admin/css/admin.css --minify
```

Mientras trabajas puedes dejarlo "mirando" cambios:

```bash
npx -y tailwindcss@3.4.17 -c tools/tailwind/tailwind.config.js -i tools/tailwind/entrada.css -o src/admin/css/admin.css --watch
```

Sube `src/admin/css/admin.css` al repositorio: Render lo sirve tal cual (el servidor no compila nada).

## Ojo

- `src/admin/assets/js/tailwind.js` **sigue en el proyecto** porque el módulo de profesor
  (`src/profesor/index.php`) todavía lo usa. Cuando el equipo de profesor migre a un CSS
  compilado, se puede borrar.
- Los colores de marca (azul, verde, naranja) vienen de `src/css/tokens.css`, no de Tailwind.
