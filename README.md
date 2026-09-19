# BiGlobal

Plataforma para aprender idiomas (inglés, español y lengua de señas),
inspirada en Open English y Duolingo.

Este README documenta la parte del proyecto correspondiente a
**login, panel de administrador, página principal y el nuevo rol de
estudiante**.

## 1. Instalación

1. Clona el repositorio y copia el archivo de configuración:

   ```bash
   cp .env.example .env
   ```

2. Edita `.env` con los datos de tu base de datos (local o externa):

   ```
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=biglobal
   DB_USER=root
   DB_PASS=
   ```

3. Crea el esquema y los datos base (roles e idiomas):

   ```bash
   mysql -u root -p < database/schema.sql
   ```

4. Genera el primer usuario administrador. Primero crea el hash de la
   contraseña:

   ```bash
   php tools/generar_hash.php "TuContraseñaSegura"
   ```

   Copia el hash que imprime y créate como administrador:

   ```sql
   INSERT INTO usuarios (nombre, apellido, correo, password, rol_id, estado)
   VALUES ('Admin', 'BiGlobal', 'admin@biglobal.com', '<hash generado>', 1, 'Activo');
   ```

5. Sirve la carpeta `src/` con PHP (por ejemplo `php -S localhost:8000 -t src`)
   y abre `index.html`.

## 2. Qué se corrigió respecto a la versión anterior

- **Base de datos externa por configuración**: las credenciales ya no
  están escritas en el código (`src/bd/conexion.php`); se leen de
  `.env` mediante `config/config.php`.
- **Modelo de roles inconsistente (bug crítico)**: el login usaba
  `usuarios.rol_id` + `usuarios.activo`, mientras que el panel de
  usuarios usaba una tabla `usuario_roles` + `usuarios.estado`. Un
  usuario creado desde el panel nunca podía iniciar sesión. Ahora todo
  el proyecto usa un único modelo: `usuarios.rol_id` (FK a `roles`) y
  `usuarios.estado` (`'Activo'` / `'Inactivo'`).
- **Rutas de administrador sin protección**: 14 archivos del panel
  (`guardar_*.php`, `editar_*.php`, `eliminar_*.php`, `lecciones.php`,
  `actividades.php`) no verificaban sesión de administrador y podían
  ejecutarse sin haber iniciado sesión. Todos llaman ahora a
  `protegerRol("admin")`.
- **Enlaces rotos del panel**: `reportes.php`, `moderacion.php`,
  `certificados.php` y `configuracion.php` estaban enlazados en el
  menú pero no existían. Ahora son páginas reales y funcionales.
  `errores.php` se consolidó dentro de `reportes.php`.
- **Módulo de actividades incompleto**: `actividades.php` enlazaba a
  `guardar_actividad.php`, `editar_actividad.php` y
  `eliminar_actividad.php`, que tampoco existían. Ya están creados.
- **Archivos de depuración expuestos**: se eliminó `prueba_bd.php` y
  se movió `generar_hash.php` a `tools/` como script de línea de
  comandos (ya no trae una contraseña en texto plano dentro del
  código).
- **CSRF y límite de intentos** en login y registro
  (`src/auth/csrf.php`, `csrf_token.php`), para mitigar ataques CSRF
  y fuerza bruta en los formularios públicos.
- **Endpoint de logout**: no existía; se agregó `src/auth/logout.php`.
- **Enlaces "Volver al inicio" rotos** en `usuarios.php`, `cursos.php`
  e `idiomas.php` (apuntaban a `../index.php`, que no existe; el
  archivo real es `index.html`).
- **Ícono roto en la página principal**: el archivo del ícono de
  "Lengua de señas" tenía un nombre corrupto en disco
  (`icono-lenguaje-se#U00f1as.png`) que no coincidía con el `<img>`
  del HTML. Se renombró y el ícono ya carga correctamente.
- **Enlaces absolutos hardcodeados**: `index.html` tenía 3 enlaces a
  `/BiGlobal/src/html/login.html` (ruta absoluta local) mezclados con
  enlaces relativos al mismo destino. Esos enlaces solo funcionaban en
  un entorno local con esa carpeta exacta y se rompían en cualquier
  otro hosting. Ya son todos relativos.
- **Carpeta `src/html/` eliminada**: solo contenía `login.html`
  (login + registro en una sola pantalla). Se movió a `src/auth/`,
  junto con el resto de la lógica de autenticación
  (`login.php`, `registro.php`, `logout.php`, `proteger.php`), en vez
  de tener una carpeta genérica "html" separada de su propia lógica.

## 3. Rol de estudiante (nuevo)

En `src/estudiante/`:

- `index.php` — panel de bienvenida con estadísticas y cursos en progreso.
- `cursos.php` — catálogo de cursos publicados, con barra de progreso.
- `curso.php` — lecciones de un curso, con botón para marcarlas como completadas.
- `completar_leccion.php` — registra el progreso y **emite un certificado
  automáticamente** cuando se completan todas las lecciones publicadas de un curso.
- `certificados.php` — certificados obtenidos por el estudiante.

Reutiliza la misma paleta de colores y tipografía del panel de
administrador (`src/admin/css/style.css`) más un archivo adicional
(`src/estudiante/css/estudiante.css`) con los componentes nuevos
(tarjetas de curso, barra de progreso, tarjeta de certificado).

## 4. Despliegue y hosting

El proyecto sigue siendo PHP + MySQL "clásico" (sin contenedores
obligatorios), así que tienes dos caminos razonables:

**Opción simple (recomendada para la entrega académica):** un hosting
compartido con PHP y MySQL en el mismo lugar (sin configurar nada
externo): Hostinger (plan económico, confiable, con dominio y correo)
o, si el presupuesto es cero, InfinityFree. Subes `src/` como raíz
del sitio, importas `database/schema.sql` desde phpMyAdmin y llenas
`.env` con las credenciales que te da el panel.

**Opción "profesional" (separar app y base de datos, como pediste):**
- **App:** Render o Railway (despliegan desde Git; PHP corre en un
  contenedor Docker sencillo). Railway tiene un plan gratuito con
  crédito mensual limitado; Render tiene plan gratuito sin
  restricción de horario pero el servicio "duerme" tras un rato sin
  uso y demora al despertar.
- **Base de datos externa:** Aiven ofrece un plan gratuito con MySQL
  real (1 GB de RAM/disco), pensado justo para este caso. TiDB Cloud
  Serverless (compatible con MySQL) es otra alternativa con capa
  gratuita generosa. Evita Clever Cloud para esto: quitó su plan
  gratuito en 2023.

Con cualquiera de las dos opciones, solo necesitas cambiar los
valores de `.env` — el código ya está preparado para apuntar a una
base de datos externa sin tocar `conexion.php`.

## 5. Próximos pasos sugeridos (no incluidos en esta entrega)

- Agregar una columna `idioma_id` en `cursos` para vincular cada curso
  a un idioma de forma explícita (hoy son dos catálogos independientes).
- Extender la protección CSRF ya creada para login/registro a los
  formularios del panel de administrador.
- Reemplazar las tablas simples de `reportes_contenido` con un flujo
  real de reportes generado por usuarios (hoy no hay una pantalla
  pública para reportar contenido).
- Construir el rol de **instructor/profesor** (carpeta `src/profesor/`,
  a cargo de otro integrante del equipo).
- Un motor real de actividades interactivas (quizzes evaluables, audio,
  etc.); hoy `actividades` son de solo lectura para el estudiante.
