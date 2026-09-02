#!/bin/sh
set -eu

mkdir -p /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage

attempt=1
max_attempts="${MIGRATION_MAX_ATTEMPTS:-30}"

until php /var/www/html/database/migrate.php; do
    if [ "$attempt" -ge "$max_attempts" ]; then
        echo "Database migrations did not complete after ${max_attempts} attempts." >&2
        exit 1
    fi

    echo "Database is not ready; retrying migration (${attempt}/${max_attempts})..." >&2
    attempt=$((attempt + 1))
    sleep 2
done

exec "$@"
