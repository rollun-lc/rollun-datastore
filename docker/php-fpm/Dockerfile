FROM maxrollundev/php-fpm-7.2

USER root
ENV COMPOSER_ALLOW_SUPERUSER=1

# Buster EOL: переключаем репозитории на archive.debian.org и ставим ping/ip
RUN set -eux; \
    sed -i -e 's|deb.debian.org/debian|archive.debian.org/debian|g' \
           -e 's|security.debian.org/debian-security|archive.debian.org/debian-security|g' /etc/apt/sources.list || true; \
    printf 'Acquire::Check-Valid-Until "false";\n' > /etc/apt/apt.conf.d/99no-check-valid-until; \
    apt-get update; \
    apt-get install -y --no-install-recommends iputils-ping iproute2; \
    rm -rf /var/lib/apt/lists/*

# Install xdebug
RUN pecl install xdebug-3.0.2 && docker-php-ext-enable xdebug

COPY ./php-fpm.conf  /usr/local/etc/php-fpm.conf
COPY ./conf.d/       /usr/local/etc/php/conf.d/

# Script for wait a port
COPY ./wait-for-it.sh /usr/local/bin/wait-for-it
RUN chmod 555 /usr/local/bin/wait-for-it

RUN composer self-update

# Set entrypoint
COPY ./entrypoint.sh /usr/local/bin/docker-php-entrypoint
RUN chmod +x /usr/local/bin/docker-php-entrypoint

WORKDIR /var/www/app

CMD ["php-fpm", "-R"]