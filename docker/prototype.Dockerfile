ARG AMPERSAND_IMAGE_VERSION=latest
FROM docker.pkg.github.com/ampersandtarski/ampersand/ampersand:${AMPERSAND_IMAGE_VERSION} as ampersand-builder

# somehow needed
ARG DEBIAN_FRONTEND=noninteractive
RUN apt update && apt install -y netbase ca-certificates

# copy the entire build context into /usr/local/project in this container (the "build-container")
ADD . /usr/local/project


# Generate prototype application from folder
RUN ampersand proto /usr/local/project/JustGegevens.adl \
      --output-directory=/build \
      --sqlHost=db \
      --verbose \
      --skip-composer \
      --prototype-framework-version=development \
      --customizations=customizations

 
# Build stage 2
FROM composer:1.8 AS composer-builder

RUN apk update && apk add --no-cache libzip-dev
RUN docker-php-ext-install mysqli zip

COPY --from=ampersand-builder /build /app
RUN composer update --prefer-dist --no-dev --profile

# Build stage 3
FROM node:12 as frontend-builder

RUN npm i -g gulp-cli

# Copy output files of ampersand builder
COPY --from=composer-builder /app /src

WORKDIR /src
RUN npm update --loglevel silent \
 && gulp build-ampersand \
 && gulp build-project

# Image to run
FROM php:7.3-apache

# Change doc root. Let's move to apache conf file when more configuration is needed
ENV APACHE_DOCUMENT_ROOT /var/www/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN apt-get update \
 && apt-get install -y \
       curl \
       libzip-dev \
       zlib1g-dev \
       vim \
       apt-transport-https \
       ca-certificates \
       gnupg2 \
       software-properties-common

# Install additional php/apache extensions
# enable ZipArchive for importing .xlsx files on runtime
RUN docker-php-ext-install mysqli zip\
 && a2enmod rewrite

# Install composer (php's package manager)
RUN php  -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
 && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
 && php -r "unlink('composer-setup.php');" \
 && rm -rf /var/lib/apt/lists/*
ENV COMPOSER_HOME /usr/local/bin/

# To install docker so RAP can use docker:
# I used the instruction on docs.docker.com/engine/reference/commandline/run/#mount-volume--v---read-only
# However, this requires a volume link to the static docker binary, i.c. /usr/local/bin.
# It effectively gives RAP access to many binaries on the host machine, which might increase the security risk.
# If that is a problem, you can alternatively remove that volume link from docker-compose.yml
# and install docker-ce in the image instead.
# Details are described on https://getintodevops.com/blog/the-simple-way-to-run-docker-in-docker-for-ci.
# Uncomment the following to do that installation:
# RUN curl -fsSL https://download.docker.com/linux/$(. /etc/os-release; echo "$ID")/gpg > /tmp/dkey; apt-key add /tmp/dkey \
#  && add-apt-repository \
#        "deb [arch=amd64] https://download.docker.com/linux/$(. /etc/os-release; echo "$ID") \
#        $(lsb_release -cs) \
#        stable" \
#  && apt-get update \
#  && apt-get -y install docker-ce

# Copy output files of frontend builder
COPY --from=frontend-builder /src /var/www

RUN mkdir -p /var/www/data \
 && mkdir -p /var/www/log \
 && chown -R www-data:www-data /var/www/data /var/www/log /var/www/generics
