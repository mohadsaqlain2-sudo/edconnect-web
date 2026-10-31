#!/usr/bin/env bash
#
# EduConnect backup: MySQL/SQLite database + uploaded files + .env
#
# Usage:
#   ./scripts/backup.sh
#
# Environment overrides:
#   APP_DIR        application root (default: one level above this script)
#   BACKUP_DIR     where the archives are written (default: /var/backups/educonnect)
#   RETENTION_DAYS how long archives are kept (default: 14)
#
# The archive is a single .tar.gz containing database.sql.gz, storage/app and
# a copy of .env. It is chmod 600 because .env contains secrets.

set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/educonnect}"
RETENTION_DAYS="${RETENTION_DAYS:-14}"
STAMP="$(date +%Y%m%d_%H%M%S)"
WORK_DIR="$(mktemp -d)"
ARCHIVE="$BACKUP_DIR/educonnect_${STAMP}.tar.gz"

cleanup() {
    rm -rf "$WORK_DIR"
}
trap cleanup EXIT

if [ ! -f "$APP_DIR/.env" ]; then
    echo "ERROR: $APP_DIR/.env not found. Set APP_DIR to the application root." >&2
    exit 1
fi

mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"

cd "$APP_DIR"

# Read one KEY=value from .env (last occurrence wins, surrounding quotes removed).
env_value() {
    local key="$1"
    sed -n "s/^${key}=//p" .env | tail -n 1 | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//"
}

DB_CONNECTION="${DB_CONNECTION:-$(env_value DB_CONNECTION)}"
DB_CONNECTION="${DB_CONNECTION:-mysql}"

echo "Backing up $APP_DIR (connection: $DB_CONNECTION)"

if [ "$DB_CONNECTION" = "sqlite" ]; then
    DB_DATABASE="${DB_DATABASE:-$(env_value DB_DATABASE)}"

    if [ "$DB_DATABASE" = ":memory:" ] || [ ! -f "$DB_DATABASE" ]; then
        echo "ERROR: the SQLite database file '$DB_DATABASE' does not exist." >&2
        exit 1
    fi

    cp "$DB_DATABASE" "$WORK_DIR/database.sqlite"
    DB_DUMP_FILE="database.sqlite"
    echo "SQLite database copied."
else
    if ! command -v mysqldump >/dev/null 2>&1; then
        echo "ERROR: mysqldump is not installed (package mysql-client)." >&2
        exit 1
    fi

    DB_HOST="${DB_HOST:-$(env_value DB_HOST)}"
    DB_PORT="${DB_PORT:-$(env_value DB_PORT)}"
    DB_DATABASE="${DB_DATABASE:-$(env_value DB_DATABASE)}"
    DB_USERNAME="${DB_USERNAME:-$(env_value DB_USERNAME)}"
    DB_PASSWORD="${DB_PASSWORD:-$(env_value DB_PASSWORD)}"

    # A temporary defaults file keeps the password out of the process list.
    CNF="$WORK_DIR/my.cnf"
    umask 077
    ESCAPED_PASSWORD="${DB_PASSWORD//\\/\\\\}"
    ESCAPED_PASSWORD="${ESCAPED_PASSWORD//\"/\\\"}"

    cat > "$CNF" <<EOF
[client]
host=${DB_HOST:-127.0.0.1}
port=${DB_PORT:-3306}
user=${DB_USERNAME:-root}
password="${ESCAPED_PASSWORD}"
EOF

    mysqldump \
        --defaults-extra-file="$CNF" \
        --single-transaction \
        --quick \
        --routines \
        --events \
        --no-tablespaces \
        "$DB_DATABASE" | gzip > "$WORK_DIR/database.sql.gz"

    if [ ! -s "$WORK_DIR/database.sql.gz" ]; then
        echo "ERROR: mysqldump produced an empty file." >&2
        exit 1
    fi

    DB_DUMP_FILE="database.sql.gz"
    echo "MySQL database dumped."
fi

# Uploaded images (post images, profile photos). storage/logs and the framework
# cache are intentionally not backed up. tar cannot append to a compressed
# archive, so both roots are added in one command.
tar -czf "$ARCHIVE" \
    -C "$APP_DIR" storage/app .env \
    -C "$WORK_DIR" "$DB_DUMP_FILE"

chmod 600 "$ARCHIVE"

# Remove old archives.
find "$BACKUP_DIR" -maxdepth 1 -type f -name 'educonnect_*.tar.gz' \
    -mtime +"$RETENTION_DAYS" -print -delete

echo "Backup written: $ARCHIVE ($(du -h "$ARCHIVE" | cut -f1))"
