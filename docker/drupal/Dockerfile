FROM drupal:9.0.11-apache

RUN apt-get update \
    && apt install git -y

WORKDIR /opt/drupal

ADD . .

RUN composer self-update --2 \
    && composer update -W \
    && composer install

RUN mkdir -p /opt/drupal/web/sites/default/files \
    && chgrp -R 0 /opt/drupal/web/sites/default \
    && chmod -R g+rwX /opt/drupal/web/sites/default \
    && chown -LR www-data:root /opt/drupal/web/sites/default

# - In order to drop the root user, we have to make some directories writable
#   to the root group as OpenShift default security model is to run the container
#   under random UID.
RUN usermod -a -G 0 www-data

USER www-data
