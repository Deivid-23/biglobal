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
    } > "$PHP_INI_DIR/conf.d/biglobal.ini"

# Solo la carpeta src/ es pública. app/ y config/ quedan fuera del navegador.
ENV APACHE_DOCUMENT_ROOT /var/www/html/src
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Sin listado de carpetas, sin versión de Apache y con cabeceras de seguridad
RUN { \
        echo "ServerTokens Prod"; \
        echo "ServerSignature Off"; \
        echo "<Directory /var/www/html/src>"; \
        echo "    Options -Indexes +FollowSymLinks"; \
        echo "</Directory>"; \
        echo "Header always set X-Content-Type-Options \"nosniff\""; \
        echo "Header always set X-Frame-Options \"SAMEORIGIN\""; \
        echo "Header always set Referrer-Policy \"strict-origin-when-cross-origin\""; \
    } > /etc/apache2/conf-available/biglobal.conf \
    && a2enconf biglobal

WORKDIR /var/www/html

COPY config/ ./config/
COPY app/ ./app/
COPY src/ ./src/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
