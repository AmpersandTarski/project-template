ARG PROTOTYPE_IMAGE_VERSION=latest
FROM docker.pkg.github.com/ampersandtarski/prototype/prototype-framework:${PROTOTYPE_IMAGE_VERSION}

ADD . /usr/local/project

ARG DB_HOST=db
ARG SCRIPT=script.adl

# Generate prototype application from folder
RUN ampersand /usr/local/project/${SCRIPT} --proto=/var/www --sqlHost=${DB_HOST} --verbose --skip-composer \
  && chown -R www-data:www-data /var/www \
  && cd /var/www \
  # && composer install --prefer-dist --no-dev --profile \
  # && npm install \
  # && gulp build-ampersand \
  # && gulp build-project