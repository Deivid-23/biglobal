FROM php:8.2-apache

# pdo_mysql: requerida por src/bd/conexion.php. rewrite: por si algún .htaccess lo usa.
RUN apt-get update && apt-get install -y --no-install-recommends ca-certificates \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite

# DocumentRoot -> src/, nunca la raíz del proyecto
ENV APACHE_DOCUMENT_ROOT /var/www/html/src
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# Solo config/ (necesaria por conexion.php) y src/. database/ y tools/ no viajan a producción.
COPY config/ ./config/
COPY src/ ./src/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80