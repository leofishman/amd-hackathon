FROM drupal:11-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    mariadb-client \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Bake every PHP dependency into the image: the judges' environment must
# never need composer or network access at runtime.
WORKDIR /opt/drupal
RUN composer require --no-interaction \
      drush/drush \
      drupal/ai \
      drupal/key \
      drupal/eca \
      drupal/search_api \
      drupal/ai_provider_universal:1.0.x-dev
ENV PATH="/opt/drupal/vendor/bin:${PATH}"

RUN { \
      echo "memory_limit=512M"; \
      echo "opcache.enable=1"; \
      echo "opcache.memory_consumption=192"; \
    } > /usr/local/etc/php/conf.d/hackathon.ini

COPY modules/amd_hackathon /opt/drupal/web/modules/custom/amd_hackathon
COPY scripts /hackathon-scripts
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh /hackathon-scripts/*.sh

EXPOSE 80
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
