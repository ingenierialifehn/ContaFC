FROM php:8.4-fpm-alpine

# ─── Dependencias del sistema ─────────────────────────────────────────────────
# Se añade libfbclient para que el driver pdo_firebird funcione en tiempo de ejecución
RUN apk add --no-cache \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    linux-headers \
    firebird-dev \
    libfbclient \
    mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    pdo_firebird \
    intl \
    zip \
    gd \
    mbstring \
    opcache \
    && rm -rf /var/cache/apk/* [cite: 1, 2]

# ─── Composer ────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ─── Directorio de trabajo ───────────────────────────────────────────────────
WORKDIR /var/www/html

# ─── Configuración de usuario y carpetas ─────────────────────────────────────
RUN addgroup -g 1000 appgroup && adduser -u 1000 -G appgroup -s /bin/sh -D appuser [cite: 3]

# Creamos el directorio temporal para las migraciones y asignamos permisos
RUN mkdir -p /var/www/tmp && chown -R appuser:appgroup /var/www/tmp /var/www/html

USER appuser [cite: 3]

EXPOSE 9000
CMD ["php-fpm"]