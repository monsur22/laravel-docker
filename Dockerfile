FROM php:8.1-fpm

# COPY composer.lock composer.json /var/www/
WORKDIR /var/www

# COPY src .

# Install dependencies
# RUN apt update \
#     apt install -y \
#     build-essential \
#     mysql-client \
#     libpng-dev \
#     libjpeg62-turbo-dev \
#     libfreetype6-dev \
#     locales \
#     zip \
#     jpegoptim optipng pngquant gifsicle \
#     vim \
#     unzip \
#     git \
#     curl

# # Clear cache
# RUN apt clean && rm -rf /var/lib/apt/lists/*

# # Install extensions
# RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl
# RUN docker-php-ext-configure gd --with-gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ --with-png-dir=/usr/include/
# RUN docker-php-ext-install gd

# # Install composer
# RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# # Add user for laravel
# RUN groupadd -g 1000 www
# RUN useradd -u 1000 -ms /bin/bash -g www www

# # Copy application folder
# COPY . /var/www

# # Copy existing permissions from folder to docker
# COPY --chown=www:www . /var/www
# RUN chown -R www-data:www-data /var/www

# # change current user to www
# USER www

# EXPOSE 9000
# CMD ["php-fpm"]
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