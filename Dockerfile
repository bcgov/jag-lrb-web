# Dockerfile.development
FROM docker-remote.artifacts.developer.gov.bc.ca/php:7-apache
MAINTAINER JAG-LRB-WEB

# Setup Apache2 config
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

# use your users $UID and $GID below
RUN groupadd apache-www-volume -g 1000
RUN useradd apache-www-volume -u 1000 -g 1000

CMD ["apache2-foreground"]