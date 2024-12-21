FROM ghcr.io/roadrunner-server/roadrunner:2024.3.1 AS roadrunner
FROM php:8.2-cli

COPY --from=roadrunner /usr/bin/rr /usr/local/bin/rr

# Install necessary extensions
RUN apt-get clean && rm -rf /var/lib/apt/lists/* && apt-get update && apt-get update && apt-get install -y git unzip libpq-dev && \
    docker-php-ext-install pdo pdo_pgsql sockets && \
    pecl install redis && \
    docker-php-ext-enable redis

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy dependencies and source files
COPY src/composer.json .
RUN composer install

# Copy source files
COPY src/ .

CMD rr serve

# fallback entrypoint
# CMD ["bash", "-c", "tail -f /dev/null"]