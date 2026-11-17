# PHP runtime for the long-running processes: Horizon (needs pcntl/posix, which Windows lacks) and Reverb.
FROM php:8.4-cli-alpine

RUN apk add --no-cache postgresql-dev icu-dev linux-headers \
    && docker-php-ext-install pdo_pgsql pcntl intl opcache \
    && apk del linux-headers

WORKDIR /var/www/html

# The project directory is mounted at runtime, so code changes don't need a rebuild.
CMD ["php", "artisan", "horizon"]
