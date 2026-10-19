/**
 * Configuración de Tailwind SOLO para el panel de administrador.
 *
 * Tailwind lee los archivos de "content", busca qué clases se usan
 * y genera un CSS pequeño (src/admin/css/admin.css) con solo esas clases.
 * Si agregas clases nuevas en el admin, vuelve a compilar (ver LEEME.md).
 */
module.exports = {
  content: [
    "./src/admin/**/*.php",
    "./app/Views/partials/admin_*.php",
  ],
  // El modo oscuro se activa con la clase "dark" en <html>
  darkMode: "class",
  theme: {
    extend: {
      fontFamily: {
        sans: ["Lato", "system-ui", "sans-serif"],
      },
    },
  },
};
