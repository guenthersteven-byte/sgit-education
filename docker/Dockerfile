# ============================================================================
# sgiT Education Platform - PHP-FPM Dockerfile v1.5
# ============================================================================
#
# v1.5: FIX - libzip Runtime behalten für ZipArchive!
#
# @version 1.5
# @date 05.12.2025
# ============================================================================

FROM php:8.3-fpm-alpine

# ============================================================================
# System Dependencies (Runtime + Dev für Build)
# ============================================================================
RUN apk add --no-cache \
    # SQLite
    sqlite \
    sqlite-dev \
    # GD
    libpng libpng-dev \
    libjpeg-turbo libjpeg-turbo-dev \
    freetype freetype-dev \
    # Zip (WICHTIG: libzip für Runtime!)
    libzip \
    libzip-dev \
    # ICU
    icu-dev icu-libs \
    # OCR
    tesseract-ocr \
    tesseract-ocr-data-deu \
    tesseract-ocr-data-eng \
    # Tools
    curl bash

# ============================================================================
# PHP Extensions
# ============================================================================
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

RUN docker-php-ext-install -j$(nproc) \
    gd \
    pdo \
    pdo_sqlite \
    zip \
    intl \
    opcache \
    exif

# ============================================================================
# Cleanup - NUR Dev-Pakete entfernen, Runtime behalten!
# ============================================================================
RUN apk del \
    sqlite-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    && rm -rf /var/cache/apk/*

# ============================================================================
# OPcache
# ============================================================================
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini

# ============================================================================
# Workdir
# ============================================================================
WORKDIR /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
