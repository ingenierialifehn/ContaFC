FROM php:8.4-fpm

# ─── Dependencias del sistema ─────────────────────────────────────────────────
# Se añade firebird-dev para que el driver pdo_firebird funcione
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    libonig-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    firebird-dev \
    default-mysql-client \
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
    && rm -rf /var/lib/apt/lists/*

# ─── Composer ────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ─── Directorio de trabajo ───────────────────────────────────────────────────
WORKDIR /var/www/html

# ─── Configuración de usuario y carpetas ─────────────────────────────────────
RUN groupadd -g 1000 appgroup && useradd -u 1000 -g appgroup -s /bin/bash -m appuser

# Creamos el directorio temporal para las migraciones y asignamos permisos
RUN mkdir -p /var/www/tmp && chown -R appuser:appgroup /var/www/tmp /var/www/html

USER appuser

EXPOSE 9000
CMD ["php-fpm"]