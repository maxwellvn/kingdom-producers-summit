#!/bin/sh
set -eu

mkdir -p /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage

attempt=1
max_attempts="${MIGRATION_MAX_ATTEMPTS:-30}"
output_file="$(mktemp)"

while :; do
    set +e
    php /var/www/html/database/migrate.php 2>&1 | tee "$output_file"
    status=$?
    set -e

    if [ "$status" -eq 0 ]; then
        break
    fi

    if [ "$attempt" -ge "$max_attempts" ]; then
        echo "Database migrations did not complete after ${max_attempts} attempts." >&2
        echo "--- Last migration output ---" >&2
        cat "$output_file" >&2
        rm -f "$output_file"
        exit 1
    fi

    echo "Database is not ready; retrying migration (${attempt}/${max_attempts})..." >&2

    attempt=$((attempt + 1))
    sleep 2
done

rm -f "$output_file"
exec "$@"
