FROM php:8.2-apache-bullseye

RUN curl -sL https://deb.nodesource.com/setup_16.x | bash -

ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_HOME /home/.composer
RUN mkdir -p /home/.composer

RUN apt-get update && apt-get install -y \
    # Tools
    vim git curl cron wget zip unzip \
    # other
    apt-transport-https \
    build-essential \
    ca-certificates \
    mariadb-client \
    openssl \
    supervisor \
    nodejs \
    sudo && rm -rf /var/lib/apt/lists/*

# auto install dependencies and remove libs after installing ext: https://github.com/mlocati/docker-php-extension-installer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
    iconv \
    opcache \
    xml \
    intl \
    pdo_mysql \
    curl \
    json \
    zip \
    bcmath \
    mbstring \
    exif \
    fileinfo \
    dom \
    gd \
    calendar

RUN apt-get purge -y --auto-remove
RUN a2enmod rewrite

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

COPY ./docker/001-basta.conf /etc/apache2/sites-enabled/001-basta.conf

# Run from unprivileged port 8080 only
RUN sed -e 's/Listen 80/Listen 8080/g' -i /etc/apache2/ports.conf

COPY . /var/www/html

WORKDIR /var/www/html
CMD ["docker-php-entrypoint", "apache2-foreground"]
