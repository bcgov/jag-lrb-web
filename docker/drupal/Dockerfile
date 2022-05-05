FROM drupal:9.3.12-apache

RUN apt-get update \
    && apt install git -y

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

WORKDIR /opt/drupal

ADD ./app .

RUN composer install

# Ensure the custom files location is writeable
RUN mkdir -p /opt/drupal/web/files \
    && chgrp -R 0 /opt/drupal/web/files \
    && chmod -R g+rwX /opt/drupal/web/files \
    && chown -LR www-data:root /opt/drupal/web/files

# Create empty, writeable .htpasswd
RUN touch /opt/drupal/web/.htpasswd \
    && chgrp 0 /opt/drupal/web/.htpasswd \
    && chmod g+rwX /opt/drupal/web/.htpasswd \
    && chown -L www-data:root /opt/drupal/web/.htpasswd

# Replace default port with a higher one to run in OpenShift as non-root user
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf \
    && sed -i 's/80/8080/g' /etc/apache2/sites-enabled/000-default.conf

EXPOSE 8080

# - In order to drop the root user, we have to make some directories writable
#   to the root group as OpenShift default security model is to run the container
#   under random UID.
RUN usermod -a -G 0 www-data

USER www-data
