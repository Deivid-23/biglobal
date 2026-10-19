# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Estudiantes** (rol `estudiante`): personas en Colombia que quieren aprender inglés, español o Lengua de Señas Colombiana (LSC). Entran desde celular o computador, siguen cursos por lecciones y quieren ver su avance.
- **Docentes** (rol `docente`): crean cursos, lecciones y actividades. Se registran solos y quedan pendientes (`activo = 2`) hasta que un admin los aprueba. Su módulo (`src/profesor`) lo mantiene otra persona del equipo.
- **Administradores** (rol `admin`): gestionan usuarios, catálogo, solicitudes de docentes y configuración del sitio.

## Product Purpose

Plataforma para aprender idiomas y LSC con cursos por lecciones, progreso guardado en la base de datos y certificados al completar un curso. Éxito = el estudiante vuelve, completa lecciones y obtiene su certificado.

## Positioning

Combina idiomas orales (inglés, español) con Lengua de Señas Colombiana en una sola plataforma, con enfoque de inclusión. Inspiración declarada: Duolingo (motivación, progreso) y Open English (seriedad).

## Capabilities and Constraints

- PHP 8.2 + PDO, MySQL/MariaDB; producción en Docker (php:8.2-apache) sobre Render con TiDB Cloud (SSL). Sistema de archivos Linux: rutas sensibles a mayúsculas.
- Sin paso de build en el servidor: CSS y JS se sirven como archivos estáticos.
- Login con correo/contraseña y con Google. Estado de cuenta en `usuarios.activo` (0 inactivo, 1 activo, 2 pendiente).
- Planes de precios ($0 / $15 / $25) se muestran en la página principal por decisión del dueño; todavía no existe sistema de pagos.
- Pendiente (no construido aún): gamificación (racha, XP, medallas), página principal alimentada desde la BD, boletín/contacto reales, perfil del estudiante.

## Brand Commitments

- Nombre: BiGlobal. Logo: `src/img/logo-Biglobal.png`.
- Color base obligatorio: azul `#2563EB` (hover `#1D4ED8`), fondo azul muy claro `#EFF6FF`, acentos `#DBEAFE` y `#E0F2FE`, naranja suave ya usado en tarjetas. Texto `#1E293B` / `#64748B`.
- Tipografías: Manrope (títulos) y Lato (texto).
- Verde solo para éxito y progreso; naranja para rachas y logros. El azul siempre es el color principal. Tema claro por defecto.
- Tono: amigable y motivador, en español, sin perder seriedad.

## Evidence on Hand

- Cursos, idiomas y lecciones reales están en la BD (`biglobal.sql`); hoy son pocos y de prueba. LSC todavía no existe como idioma ni curso en la BD.
- No hay testimonios, calificaciones ni cifras de alumnos reales: no deben inventarse.

## Product Principles

1. Lo que se muestra al estudiante sale de la base de datos, no de datos escritos a mano ni de localStorage.
2. Motivar sin distraer: celebrar el progreso real, con mensajes cortos y en tono amable.
3. Seguro por defecto: consultas preparadas, CSRF en formularios, nada de credenciales en el código.
4. Código legible para alguien que está aprendiendo: comentarios en español sencillo.

## Accessibility & Inclusion

Producto con enfoque de inclusión (LSC): contraste suficiente, navegación por teclado con foco visible, textos alternativos en imágenes y respeto de `prefers-reduced-motion`.
