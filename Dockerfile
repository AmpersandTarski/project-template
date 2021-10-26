FROM ampersandtarski/prototype-framework:v1.10.3

# Copy Ampersand compiler
COPY --from=ampersandtarski/ampersand:2021-10-22 /bin/ampersand /usr/local/bin
RUN chmod +x /usr/local/bin/ampersand

# Copy the content of the current working directory from which docker was called
COPY . /usr/local/project/

WORKDIR /usr/local/project

# Generate prototype application from folder
RUN ampersand proto Enrollment.adl \
  --proto-dir /var/www \
  --verbose

WORKDIR /var/www
RUN chown -R www-data:www-data data log generics

# RUN composer install --prefer-dist --no-dev --profile --optimize-autoloader \
#   && npm update \
#   && npm audit fix \
#   && npm install \
#   && gulp build-ampersand \
#   && gulp build-project