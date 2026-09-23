# BiGlobal

Plataforma para aprender idiomas (inglés, español y lengua de señas),
inspirada en Open English y Duolingo.

Este README documenta la parte del proyecto correspondiente a
**login, panel de administrador, página principal, el rol de
estudiante, el despliegue en producción y la migración en curso a
una arquitectura MVC**.

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

## 3. Rol de estudiante

En `src/estudiante/`:

- `index.php` — panel de bienvenida con estadísticas y cursos en progreso.
- `cursos.php` — catálogo de cursos publicados, con barra de progreso.
- `curso.php` — lecciones de un curso, con botón para marcarlas como completadas.
- `completar_leccion.php` — registra el progreso y **emite un certificado
  automáticamente** cuando se completan todas las lecciones publicadas de un curso.
- `certificados.php` — certificados obtenidos por el estudiante.

Estos 5 archivos son ahora "front controllers" delgados: la lógica real
vive en `app/Controllers/EstudianteController.php` y
`app/Models/EstudianteModel.php` (ver sección 4).

Reutiliza la misma paleta de colores y tipografía del panel de
administrador (`src/admin/css/style.css`) más un archivo adicional
(`src/estudiante/css/estudiante.css`) con los componentes nuevos
(tarjetas de curso, barra de progreso, tarjeta de certificado).

### Registro como profesor (solicitud pendiente de aprobación)

El registro público (`src/auth/registro.php`) deja elegir entre
**Estudiante** y **Profesor**. Estudiante se activa al instante
(`rol_id=2`, `estado='Activo'`). Profesor queda en espera (`rol_id=3`,
`estado='Pendiente'`) y no puede iniciar sesión hasta que un
administrador lo apruebe cambiándole el estado a Activo desde
**Usuarios y roles**.

## 4. Arquitectura: migración a MVC (en curso)

El proyecto se está reestructurando de "un archivo PHP por página" a
Modelo-Vista-Controlador, módulo por módulo:

```
app/
├── bootstrap.php              # carga auth/proteger.php + bd/conexion.php
├── Controllers/
│   ├── EstudianteController.php
│   └── Admin/
│       └── UsuariosController.php
├── Models/
│   ├── EstudianteModel.php
│   └── UsuarioModel.php
└── Views/
    ├── partials/
    │   ├── admin_sidebar.php   # compartido entre páginas admin, recibe $paginaActiva
    │   └── admin_topbar.php    # compartido, recibe $tituloPagina
    ├── estudiante/
    └── admin/usuarios/
```

Los archivos en `src/estudiante/` y en `src/admin/` de los módulos ya
migrados solo instancian su Controller correspondiente; las URLs no
cambiaron.

**Migrado:** `estudiante/` completo · `admin/usuarios.php` +
`editar_usuario.php` + `guardar_usuario.php` + `eliminar_usuario.php`.

**Pendiente:** el resto de `admin/` (`index.php`, `cursos.php`,
`idiomas.php`, `lecciones.php`, `reportes.php`, `errores.php`,
`moderacion.php`, `certificados.php`, `configuracion.php`) sigue con la
estructura original de un archivo por página. Al migrarlos, reusar
`admin_sidebar.php` / `admin_topbar.php` en vez de duplicar ese HTML.

`src/profesor/` está vacío (módulo externo a cargo de otra persona/equipo,
ver sección 6) — no se toca ni se migra por ahora.

## 5. Despliegue en producción

El proyecto está desplegado con la app y la base de datos separadas:

- **App:** [Render](https://render.com), Web Service con Docker
  (`Dockerfile` en la raíz), plan Free.
  - Imagen base `php:8.2-apache`; `DocumentRoot` apunta a `src/` (no a la
    raíz del repo), así que `config/`, `app/` y `database/` no quedan
    expuestos públicamente aunque viajen dentro del contenedor.
  - El `Dockerfile` copia `config/`, `app/` y `src/` a la imagen — si se
    agrega una carpeta nueva en la raíz del proyecto, hay que sumar su
    `COPY` ahí también.
- **Base de datos:** [TiDB Cloud Serverless](https://tidbcloud.com)
  (compatible con MySQL), conexión con SSL obligatorio.
  `src/bd/conexion.php` activa SSL de forma condicional según la variable
  `DB_SSL`, así que en local (XAMPP/WAMP, sin SSL) no hay que cambiar
  nada.

Variables de entorno que se configuran en el panel de Render (no en
`.env`, que solo se usa en local):

```
DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
DB_SSL=true
DB_SSL_CA=/etc/ssl/certs/ca-certificates.crt
DB_CHARSET=utf8mb4
APP_ENV=production
APP_DEBUG=false
```

Flujo de trabajo: los commits son locales hasta hacer `git push` a
`main`; Render redespliega automáticamente al detectar el push.

## 6. Próximos pasos sugeridos

- Terminar la migración a MVC del resto de `admin/` (sección 4).
- Agregar una columna `idioma_id` en `cursos` para vincular cada curso
  a un idioma de forma explícita (hoy son dos catálogos independientes).
- Extender la protección CSRF ya creada para login/registro a los
  formularios del panel de administrador.
- Reemplazar las tablas simples de `reportes_contenido` con un flujo
  real de reportes generado por usuarios (hoy no hay una pantalla
  pública para reportar contenido).
- Construir el panel del rol de **instructor/profesor**
  (`src/profesor/`, actualmente vacío) — a cargo de otro integrante del
  equipo; el registro y la aprobación de la cuenta ya funcionan, falta
  el panel en sí.
- Un motor real de actividades interactivas (quizzes evaluables, audio,
  etc.); hoy `actividades` son de solo lectura para el estudiante.
- Unificar la librería de íconos del panel admin: el dashboard
  (`admin/index.php`) usa Bootstrap Icons, el resto de `admin/` todavía
  usa Font Awesome.
