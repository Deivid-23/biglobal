FROM php:8.2-apache

# ca-certificates: necesario para la conexión SSL con TiDB Cloud y SMTP
RUN apt-get update && apt-get install -y --no-install-recommends ca-certificates \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite headers

# PHP en modo producción: los errores van al log, nunca a la pantalla
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && { \
        echo "expose_php = Off"; \
        echo "session.cookie_httponly = 1"; \
        echo "session.use_strict_mode = 1"; \
        echo "date.timezone = America/Bogota"; \
        echo "upload_max_filesize = 6M"; \
        echo "post_max_size = 32M"; \
        echo "max_file_uploads = 10"; \
    } > "$PHP_INI_DIR/conf.d/biglobal.ini"

# Solo la carpeta src/ es pública. app/ y config/ quedan fuera del navegador.
ENV APACHE_DOCUMENT_ROOT /var/www/html/src
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Sin listado de carpetas, sin versión de Apache y con cabeceras de seguridad
#
# CSP (Content-Security-Policy): el navegador solo ejecuta scripts de este
# sitio y de cdnjs/jsdelivr (siempre con integrity). Ningún <script> ni
# onclick="" en línea: van en archivos .js. Los estilos en línea (style="")
# sí se permiten. Iframes: solo los reproductores de YouTube (sin cookies) y
# Vimeo, para los videos de las lecciones (app/Helpers/Video.php).
# Wompi: la API (tokenizar la tarjeta desde el navegador) y su checkout.
# Si agregas un CDN, una fuente o un servicio nuevo, súmalo aquí.
# HSTS: el navegador recuerda por 1 año que este sitio solo va por HTTPS.
RUN { \
        echo "ServerTokens Prod"; \
        echo "ServerSignature Off"; \
        echo "<Directory /var/www/html/src>"; \
        echo "    Options -Indexes +FollowSymLinks"; \
        echo "</Directory>"; \
        echo "Header always set X-Content-Type-Options \"nosniff\""; \
        echo "Header always set X-Frame-Options \"SAMEORIGIN\""; \
        echo "Header always set Referrer-Policy \"strict-origin-when-cross-origin\""; \
        echo "Header always set Content-Security-Policy \"default-src 'self'; script-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data:; connect-src 'self' https://sandbox.wompi.co https://production.wompi.co; frame-src https://www.youtube-nocookie.com https://player.vimeo.com; object-src 'none'; base-uri 'self'; form-action 'self' https://checkout.wompi.co; frame-ancestors 'self'; upgrade-insecure-requests\""; \
        echo "Header always set Strict-Transport-Security \"max-age=31536000\""; \
        echo "Header always set Permissions-Policy \"camera=(), microphone=(), geolocation=(), payment=(), usb=()\""; \
        echo "Header always set Cross-Origin-Opener-Policy \"same-origin\""; \
    } > /etc/apache2/conf-available/biglobal.conf \
    && a2enconf biglobal

WORKDIR /var/www/html

COPY config/ ./config/
COPY app/ ./app/
COPY src/ ./src/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
