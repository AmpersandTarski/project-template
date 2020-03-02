ARG PROTOTYPE_IMAGE_VERSION=latest
FROM docker.pkg.github.com/ampersandtarski/prototype/prototype-framework:${PROTOTYPE_IMAGE_VERSION}

COPY . /usr/local/project/

# Generate prototype application from folder
RUN ampersand proto /usr/local/project/project.adl \
      --output-directory /var/www \
      --verbose \
      --skip-composer

RUN chown -R www-data:www-data /var/www/log /var/www/data /var/www/generics \
 && cd /var/www \
 # uncomment lines below if customizations are added to default prototype framework
 # && composer install --prefer-dist --no-dev --optimize-autoloader --profile \
 # && npm install \
 # && gulp build-ampersand \
 # && gulp build-project