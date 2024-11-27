FROM ampersandtarski/prototype-framework:main

COPY . /usr/local/project/

# Run ampersand compiler to generated backend json model files (in generics folder)
RUN ampersand proto --no-frontend /usr/local/project/main.adl \
  --proto-dir /var/www/backend \
  --crud-defaults cRud \
  --verbose

# Run ampersand compiler to generated new frontend
RUN ampersand proto --frontend-version Angular --no-backend /usr/local/project/main.adl \
  --proto-dir /var/www/frontend/src/app/generated \
  --crud-defaults cRud \
  --verbose

## At this place you can copy any frontend customizations (if applicable)
## Do this before running `npx ng build` below
## You can also copy here any project specific backend code

WORKDIR /var/www/frontend

# Build + bundle Angular frontend
RUN npx ng build

# Copy Angular frontend to public folder in web server
RUN cp -r /var/www/frontend/dist/prototype-frontend/* /var/www/html/