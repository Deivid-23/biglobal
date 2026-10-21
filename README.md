# BiGlobal

Plataforma para aprender idiomas (inglés, español) y Lengua de Señas Colombiana (LSC),
inspirada en Duolingo y Open English.

Este README cubre: **página principal, login/registro, recuperación de contraseña,
panel de administrador, rol de estudiante y despliegue**. El módulo de profesor
(`src/profesor/`) lo mantiene otra persona del equipo.

---

## 1. Estructura

```
BiGlobal/
├── app/                      # Lógica (NO es pública)
│   ├── bootstrap.php         # carga config, sesión, conexión, CSRF y helpers
│   ├── Controllers/          # AuthController, EstudianteController, RecuperacionController, Admin/*
│   ├── Models/               # consultas a la BD (siempre con PDO preparado)
│   ├── Helpers/              # Config, Seguridad, Correo, Panel (layout de paneles)  (¡"Helpers" con H mayúscula!)
│   └── Views/                # HTML de estudiante, auth y admin (MVC)
├── config/config.php         # lee .env / variables de entorno
├── database/
│   ├── schema.sql                     # estructura real (instalación nueva)
│   ├── cambios_2026-10.sql            # ejecutar en la BD actual
│   └── propuesta_siguiente_version.sql# NO ejecutar todavía (plan)
├── src/                      # ÚNICA carpeta pública (DocumentRoot)
│   ├── index.html            # página principal
│   ├── privacidad.html, terminos.html, contacto.html
│   ├── css/tokens.css        # ★ sistema de diseño: colores, fuentes, sombras
│   ├── css/componentes.css   # botones, campos, avisos, progreso, estados vacíos
│   ├── css/style.css · auth.css · paginas.css
│   ├── auth/                 # login, registro, Google, recuperar/restablecer contraseña
│   ├── estudiante/           # panel del estudiante (rutas delgadas → controlador)
│   ├── admin/                # panel admin (index.php = enrutador → AdminController)
│   ├── superadmin/           # panel del super administrador (SuperAdminController)
│   └── profesor/             # módulo del otro integrante (no se modificó)
├── tools/                    # terminal: generar_hash, probar_conexion, respaldar_bd, ejecutar_sql
└── recursos_sin_usar/        # imágenes que no usa ninguna página (no se despliegan)
```

## 2. Modelo de datos real (manda `biglobal.sql`)

| Dato | Dónde | Valores |
|---|---|---|
| Rol | `usuarios.rol_id` → `roles` | 1 `admin`, 2 `estudiante`, 3 `docente`, 4 `superadmin` |
| Tema | `usuarios.tema` | `claro`, `oscuro` o NULL (usa el del sistema) |
| Estado de la cuenta | `usuarios.activo` | 0 inactivo/rechazado · 1 activo · 2 docente pendiente |
| Publicado | `cursos.estado`, `lecciones.estado`, `idiomas.estado` | 1 sí · 0 no |
| Progreso | `progreso` | una fila por lección completada |
| Recuperar contraseña | `recuperacion_password` | el token se guarda como huella SHA-256 |

`usuarios.estado` existe en la BD pero **el código no lo usa** (solo se llena al crear la cuenta).

## 3. Instalación local (XAMPP)

1. Copia `.env.example` como `.env` y llena tus datos (nunca subas ni comprimas el `.env`).
2. Importa tu base: `mysql -u root biglobal < biglobal.sql`
   (o para una base vacía: `mysql -u root < database/schema.sql`).
3. Ejecuta los cambios: `mysql -u root biglobal < database/cambios_2026-10.sql`
4. Abre `http://localhost/BiGlobal/src/index.html`.

Para crear un admin a mano: `php tools/generar_hash.php "TuClave"` y luego el `INSERT`
que aparece al final de `database/schema.sql`.

## 4. Sistema de diseño

- **Un solo archivo de tokens**: `src/css/tokens.css`. Azul de marca `#2563EB` (hover `#1D4ED8`),
  fondo azul claro `#EFF6FF`, acentos `#DBEAFE` / `#E0F2FE`. Verde solo para éxito y progreso,
  naranja para logros. Texto `#1E293B`; el gris secundario quedó en `#5E6E85`
  (un tono más oscuro que `#64748B` para cumplir contraste AA sobre el azul claro).
- **Fuentes**: Manrope (títulos) y Lato (texto). **Íconos**: solo Font Awesome 6.5.2.
- **Componentes compartidos** (`componentes.css`): `.boton--primario`, `.entrada`, `.aviso--exito`,
  `.progreso`, `.vacio`, `.toast-biglobal`… Los usan login, estudiante, páginas legales y admin.
- **Movimiento**: todo por debajo de 300 ms, curva `--ease-out`, y se apaga con
  `prefers-reduced-motion`.
- **Modo claro / oscuro**: `src/js/tema.js` se carga en el `<head>` antes del CSS (sin parpadeo).
  Orden: tema guardado en la cuenta → último elegido en el navegador → el del sistema.
  El botón (`data-alternar-tema`) está en la barra superior de todas las pantallas y guarda la
  elección en `usuarios.tema` vía `src/auth/guardar_tema.php`. El modo oscuro solo redefine
  variables en `tokens.css` (`:root[data-tema="oscuro"]`); contraste AA revisado.
- **Paneles (admin, super admin, profesor)**: un solo layout (`app/Views/panel/`, `src/css/panel.css`,
  `src/js/panel.js`) con el mismo estilo del panel del estudiante. Ya no se usa Tailwind.
  El color de acento (`configuracion.color_acento`, por defecto `blue`) cambia los botones principales.
- **Página principal**: sigue usando Bootstrap 5 para la rejilla, el menú y el acordeón,
  pero los colores, botones y fuentes salen de los tokens.

## 5. Rol de estudiante

`src/estudiante/index.php?page=…` → `EstudianteController` → vistas en `app/Views/estudiante/`.

| Página | Qué hace |
|---|---|
| `inicio` | saludo, "continuar donde lo dejaste", resumen y primeros pasos si es nuevo |
| `cursos` | catálogo de cursos publicados |
| `mis-cursos` | cursos con al menos una lección completada |
| `curso&id=N` | ruta de lecciones (hecha / siguiente / pendiente) |
| `leccion&id=N` | contenido de la lección, actividades y botón "Terminé esta lección" |
| `certificados` | certificados emitidos (se generan solos al terminar un curso) |

`cursos.php`, `curso.php`, `certificados.php` y `completar_leccion.php` siguen funcionando.
En celular hay barra inferior de navegación; en escritorio, barra lateral.

## 5b. Roles y paneles

| Rol | Panel | Puede |
|---|---|---|
| `superadmin` | `src/superadmin/` + todo `src/admin/` | Todo lo del admin + **Cuentas y roles** (crear admins, cambiar roles, activar/desactivar, borrar definitivamente) y **Configuración global** (general, apariencia, notificaciones, seguridad, correo, sistema) |
| `admin` | `src/admin/` | Contenido (cursos, idiomas, lecciones, actividades), crear estudiantes y docentes, aprobar/rechazar docentes, su perfil |
| `docente` | `src/profesor/` | Sus cursos, lecciones y actividades (solo si está aprobado: `activo = 1`) |
| `estudiante` | `src/estudiante/` | Aprender, progreso, certificados, perfil |

- Los permisos se validan **en el servidor**: `protegerRol()` y `protegerSuperAdmin()` en `src/auth/proteger.php`.
  Un admin que escriba la URL o envíe el formulario a mano recibe 403 o un error.
- Siempre queda al menos un super administrador activo: nadie puede cambiarse el rol, desactivarse
  ni borrarse a sí mismo, y no se puede quitar el último. El primero se creó con `database/superadmin_y_tema.sql`.

## 6. Recuperación de contraseña

`auth/recuperar.php` → correo con enlace → `auth/restablecer.php?token=…`

- El enlace vence en 60 min, sirve una sola vez y se responde igual exista o no el correo.
- Máximo 3 enlaces por hora por usuario.
- El correo sale por la **API de Brevo** si existe `BREVO_API_KEY` (recomendado: Render gratis bloquea
  los puertos SMTP desde sep-2025). Si no, por SMTP (sin librerías): variables `SMTP_*` o la pestaña
  Configuración > Correo. El remitente sale de `CORREO_REMITENTE` (o `SMTP_FROM`, o el panel).
- Configuración > Correo tiene el botón «Enviar correo de prueba» (manda a la cuenta del super admin).
- En local sin SMTP (`APP_ENV=local`) la pantalla muestra el enlace de prueba.
- En producción **es obligatorio `APP_URL`** para armar el enlace.

## 7. Seguridad

- Consultas preparadas (PDO) en todo el código propio; `htmlspecialchars` al imprimir.
- CSRF en login, registro, elegir rol, recuperar/restablecer, completar lección, guardar tema y todos los
  formularios de los paneles (admin, super admin, profesor).
- Cookies de sesión `HttpOnly`, `SameSite=Lax` y `Secure` en HTTPS.
- Contraseñas con `password_hash`; las reglas (largo, mayúscula, número) salen de
  Configuración > Seguridad y se aplican en registro, admin y recuperación.
- Sin credenciales en el código: todo en `.env` / variables de Render.
- Docker: PHP en modo producción (errores al log), sin listado de carpetas y con cabeceras
  `nosniff`, `X-Frame-Options` y `Referrer-Policy`.

## 8. Despliegue (Render + TiDB Cloud)

Variables en Render > Environment:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<tu-servicio>.onrender.com
DB_HOST=<host de TiDB>   DB_PORT=4000   DB_NAME=biglobal
DB_USER=<usuario TiDB>   DB_PASS=<clave TiDB>
DB_SSL=true
DB_SSL_CA=/etc/ssl/certs/ca-certificates.crt
GOOGLE_CLIENT_ID=…  GOOGLE_CLIENT_SECRET=…
GOOGLE_REDIRECT_URI=https://<tu-servicio>.onrender.com/auth/google_callback.php
BREVO_API_KEY=…  CORREO_REMITENTE=<correo verificado en Brevo>
TAREAS_TOKEN=<64 caracteres aleatorios, igual al secreto de GitHub Actions>
```

En Linux las rutas distinguen mayúsculas: `app/Helpers/Config.php` (no `helpers/config.php`),
`img/icono-lengua-senas.png` (sin ñ).

## 9. Pendiente (siguiente versión)

Ver `database/propuesta_siguiente_version.sql` para las tablas:
gamificación (racha, XP, medallas, lecciones desbloqueables), actividades que se puedan
resolver (quiz), página principal desde la BD (`cursos.idioma_id`, LSC como idioma),
boletín y contacto reales, testimonios aprobados, perfil del estudiante y terminar de
dividir `src/admin/index.php` usando los controladores de `app/Controllers/Admin/`
(hoy esos controladores **no tienen ruta**; quedaron corregidos para la BD real pero sin usar).
