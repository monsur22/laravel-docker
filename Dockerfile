FROM php:8.1-fpm

# COPY composer.lock composer.json /var/www/
WORKDIR /var/www

RUN set -eux && \
    # update package list
    apt update && \
    apt -y install wget zip unzip lsb-release apt-transport-https ca-certificates libsodium-dev zlib1g-dev libpng-dev libjpeg-dev libfreetype6-dev libzip-dev vim && \
    wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | tee /etc/apt/sources.list.d/php.list && \
    apt update && \
    # install required PHP modules and dependencies
    apt install -y libicu-dev imagemagick libmagickwand-dev && \
    pecl install xdebug imagick && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install intl pdo_mysql sodium gd zip && \
    docker-php-ext-enable xdebug imagick && \
    sed -i '/disable ghostscript format types/,+6d' /etc/ImageMagick-6/policy.xml && \
    # install Composer
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    # remove installation cache
    apt clean && \
    rm -rf /var/lib/apt/lists/*