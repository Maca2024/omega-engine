# ==============================================================================
# OMEGA-PHP REFACTORING ENGINE - Docker Container
# ==============================================================================
# Standalone container met PHP 8.4 en Composer voor de RalphLoop Engine.
#
# BUILD:   docker build -t solvari/omega-engine:latest .
# RUN:     docker run -it -v ../engine:/app solvari/omega-engine:latest bash
# ==============================================================================

FROM php:8.4-cli-alpine

LABEL maintainer="Solvari Engineering <engineering@solvari.com>"
LABEL description="Omega-PHP Refactoring Engine - De RalphLoop Container"
LABEL version="1.0.0"

# ==============================================================================
# SYSTEM DEPENDENCIES
# ==============================================================================

RUN apk add --no-cache \
    git \
    bash \
    curl \
    unzip \
    icu-libs \
    icu-dev \
    libzip-dev \
    linux-headers \
    $PHPIZE_DEPS

# ==============================================================================
# PHP EXTENSIONS
# ==============================================================================

RUN docker-php-ext-install \
    bcmath \
    intl \
    pcntl \
    zip \
    opcache

# ==============================================================================
# OPCACHE CONFIGURATIE (CLI optimized)
# ==============================================================================

RUN echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.interned_strings_buffer=64" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.max_accelerated_files=32531" >> /usr/local/etc/php/conf.d/opcache.ini

# ==============================================================================
# PHP MEMORY & ERROR SETTINGS
# ==============================================================================

RUN echo "memory_limit=2G" >> /usr/local/etc/php/conf.d/memory.ini && \
    echo "error_reporting=E_ALL" >> /usr/local/etc/php/conf.d/error.ini && \
    echo "display_errors=On" >> /usr/local/etc/php/conf.d/error.ini && \
    echo "log_errors=On" >> /usr/local/etc/php/conf.d/error.ini

# ==============================================================================
# COMPOSER INSTALLATIE (Latest stable)
# ==============================================================================

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Verify composer werkt
RUN composer --version

# ==============================================================================
# CLEANUP - Verwijder build dependencies
# ==============================================================================

RUN apk del icu-dev $PHPIZE_DEPS && \
    rm -rf /var/cache/apk/* /tmp/*

# ==============================================================================
# WORKING DIRECTORY
# ==============================================================================

WORKDIR /app

# ==============================================================================
# DEFAULT COMMAND
# ==============================================================================

CMD ["php", "-v"]
