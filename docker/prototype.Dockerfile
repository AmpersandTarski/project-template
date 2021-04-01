FROM ampersandtarski/prototype-framework:latest
#FROM ssif-base-image

COPY . /usr/local/project/

# Copy shared templates before running Ampersand compiler
RUN cp -r -v /usr/local/project/templates /var/www/

# Lines to add specific compiler version (from Github releases)
# ADD https://github.com/AmpersandTarski/Ampersand/releases/download/Ampersand-v4.1.0/ampersand /usr/local/bin/ampersand
# RUN chmod +x /usr/local/bin/ampersand
# Line to add specific compiler version (from Ampersand image)
# COPY --from=ampersandtarski/ampersand:development /bin/ampersand /usr/local/bin

# Generate prototype application from folder
RUN ampersand proto /usr/local/project/project.adl \
      --proto-dir /var/www \
      --verbose
      
# Copy shared customizations after running Ampersand compiler
RUN cp -r -v /usr/local/project/customizations/. /var/www

RUN chown -R www-data:www-data /var/www/log /var/www/data /var/www/generics \
 && cd /var/www
 # uncomment lines below if customizations are added to default prototype framework
 # && composer install --prefer-dist --no-dev --optimize-autoloader --profile \
 # && npm install \
 # && gulp build-ampersand \
 # && gulp build-project
