#!/bin/bash
set -e
cd /opt/drupal

echo "Waiting for database..."
until mariadb-admin ping -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" --silent 2>/dev/null; do
  sleep 2
done

if ! drush status --field=bootstrap 2>/dev/null | grep -q "Successful"; then
  /hackathon-scripts/setup-hackathon.sh
fi

exec docker-php-entrypoint "$@"
