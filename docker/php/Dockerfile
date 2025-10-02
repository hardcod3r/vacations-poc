FROM php:8.3-fpm-alpine


ARG UID=1000
ARG GID=1000


# system deps
RUN apk add --no-cache git unzip icu-dev libpq-dev bash shadow

RUN apk add --no-cache ncurses


# PHP extensions
RUN docker-php-ext-configure intl \
&& docker-php-ext-install -j$(nproc) intl opcache pdo pdo_pgsql


# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


# Workdir
WORKDIR /var/www/html


# Create user and set permissions
RUN usermod -u ${UID} www-data && groupmod -g ${GID} www-data || true
RUN chown -R www-data:www-data /var/www/html
USER www-data


# Optimize PHP-FPM
USER root
RUN { \
echo "pm = dynamic"; \
echo "pm.max_children = 20"; \
echo "pm.start_servers = 2"; \
echo "pm.min_spare_servers = 2"; \
echo "pm.max_spare_servers = 4"; \
} > /usr/local/etc/php-fpm.d/zz-pool.conf


# Opcache recommended
RUN { \
echo "opcache.enable=1"; \
echo "opcache.enable_cli=1"; \
echo "opcache.jit_buffer_size=64M"; \
echo "opcache.memory_consumption=128"; \
echo "opcache.max_accelerated_files=20000"; \
} > /usr/local/etc/php/conf.d/opcache.ini


USER www-data