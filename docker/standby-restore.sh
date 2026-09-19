#!/bin/sh
# Installed on the STANDBY host at /usr/local/bin/standby-restore.
# Forced command for the backup SSH key: reads a gzipped mysqldump on stdin
# and loads it into the Coolify-managed db container. Nothing else is reachable with that key.
set -eu

db="$(docker ps -q --filter 'name=^db-' | head -n1)"
[ -n "$db" ] || { echo "no db container running" >&2; exit 1; }

gunzip -c | docker exec -i "$db" sh -c 'exec mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'
echo "restore ok $(date -u +%FT%TZ)" >> /var/log/standby-restore.log
