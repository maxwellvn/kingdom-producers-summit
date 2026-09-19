#!/bin/sh
# Push a full MySQL dump from this deployment into the standby server's db container.
# Runs as a Coolify Scheduled Task (hourly, plus "Run now" for a manual sync).
# No-op unless BACKUP_SSH_HOST is set, so the standby never tries to sync into itself.
set -eu

[ -n "${BACKUP_SSH_HOST:-}" ] || { echo "BACKUP_SSH_HOST unset; skipping sync"; exit 0; }
: "${BACKUP_SSH_KEY_B64:?base64 private key for the standby}"

key="$(mktemp)"
trap 'rm -f "$key"' EXIT
printf '%s' "$BACKUP_SSH_KEY_B64" | base64 -d > "$key"
chmod 600 "$key"

# ponytail: full logical dump, hourly. Switch to binlog replication if the DB outgrows a few GB.
mysqldump -h "$DB_HOST" -P "${DB_PORT:-3306}" -u "$DB_USER" -p"$DB_PASS" \
    --single-transaction --quick --add-drop-table --routines --triggers "$DB_NAME" \
  | gzip \
  | ssh -i "$key" -o StrictHostKeyChecking=accept-new -o ConnectTimeout=15 \
        "${BACKUP_SSH_USER:-standby}@$BACKUP_SSH_HOST" restore

echo "Synced $DB_NAME to $BACKUP_SSH_HOST at $(date -u +%FT%TZ)"
