# Backup and restore

EduConnect stores data in two places:

1. the **database** (users, posts, classes, reports, notifications),
2. **uploaded files** on the `public` disk (`storage/app/public`: post images and
   profile photos).

A backup that only copies the database therefore restores posts with broken
images. `scripts/backup.sh` captures both, plus `.env` (which contains the
database password, mail password and admin password).

---

## What the script does

```bash
./scripts/backup.sh
```

1. Reads the connection settings from `.env` (or from exported environment
   variables, which take precedence).
2. Dumps MySQL with `mysqldump --single-transaction --quick --routines --events`
   and gzips the result. The password is passed through a temporary
   `--defaults-extra-file`, so it never appears in `ps`.
3. For SQLite it copies the database file instead.
4. Archives `storage/app` and `.env` together with the dump into
   `educonnect_YYYYMMDD_HHMMSS.tar.gz`.
5. Sets `chmod 600` on the archive and deletes archives older than
   `RETENTION_DAYS` (default 14).

Overrides:

| Variable | Default | Meaning |
| --- | --- | --- |
| `APP_DIR` | parent of `scripts/` | application root |
| `BACKUP_DIR` | `/var/backups/educonnect` | destination directory |
| `RETENTION_DAYS` | `14` | delete archives older than this |
| `DB_CONNECTION`, `DB_HOST`, … | values from `.env` | connection override |

Example with an explicit destination and 30-day retention:

```bash
sudo -u www-data APP_DIR=/var/www/educonnect \
  BACKUP_DIR=/mnt/backups/educonnect RETENTION_DAYS=30 \
  /var/www/educonnect/scripts/backup.sh
```

### Schedule

Laravel also dumps the database every night at 02:30 through
`php artisan backup:database` (see `routes/console.php`). That dump lands in
`storage/app/backups/` and is the portable option when `mysqldump` is available
to PHP. The shell script below is still the **full** backup (database + uploads
+ `.env`) and should stay on the server crontab as well:

```cron
# Every day at 02:30, as the web user so file ownership stays consistent.
30 2 * * * APP_DIR=/var/www/educonnect /var/www/educonnect/scripts/backup.sh >> /var/log/educonnect-backup.log 2>&1
```

One-off dump without the shell script:

```bash
php artisan backup:database --keep=14
```

Keep at least one copy **off the server** (object storage, a NAS or another
machine). A backup that lives only on the machine that failed is not a backup.

### Verify that the cron job works

```bash
sudo tail -n 20 /var/log/educonnect-backup.log
sudo ls -lh /var/backups/educonnect | tail
```

A successful run prints `Backup written to ...` and exits `0`. Check the exit
status in a script rather than by eye:

```bash
sudo -u www-data APP_DIR=/var/www/educonnect /var/www/educonnect/scripts/backup.sh
echo "exit=$?"
```

---

## Failure handling and alerting

A backup job that fails silently is worse than no backup, because nobody
notices until a restore is needed. The script exits non-zero on any failure
(missing `mysqldump`, unreadable `.env`, full disk, archive error), so wrap it:

```bash
#!/usr/bin/env bash
# /usr/local/bin/educonnect-backup
set -euo pipefail

LOG=/var/log/educonnect-backup.log
APP_DIR=/var/www/educonnect
BACKUP_DIR=/var/backups/educonnect

if ! APP_DIR="$APP_DIR" BACKUP_DIR="$BACKUP_DIR" \
     "$APP_DIR/scripts/backup.sh" >>"$LOG" 2>&1; then
    # Alert somebody. Replace with your mail/curl/Slack command.
    printf 'EduConnect backup FAILED at %s\n' "$(date -Is)" \
        | mail -s 'ALERT: EduConnect backup failed' ops@example.com
    exit 1
fi

# Fail if the newest archive is older than 26 hours (cron did not run).
newest=$(find "$BACKUP_DIR" -name 'educonnect_*.tar.gz' -mmin -1560 | head -1)
if [ -z "$newest" ]; then
    printf 'No fresh EduConnect backup at %s\n' "$(date -Is)" \
        | mail -s 'ALERT: EduConnect backup stale' ops@example.com
    exit 1
fi

echo "$(date -Is) backup ok: $newest"
```

Cron then calls the wrapper:

```cron
30 2 * * * /usr/local/bin/educonnect-backup
```

Alternatives to `mail`: `curl` a healthchecks.io ping URL on success and its
`/fail` URL on failure, or post to a Slack webhook. Any of them gives you an
out-of-band signal when the server itself is the thing that broke.

### Log rotation

The backup log grows by a few lines per day; still rotate it to keep `/var/log`
predictable. `/etc/logrotate.d/educonnect-backup`:

```conf
/var/log/educonnect-backup.log {
    weekly
    rotate 8
    compress
    missingok
    notifempty
    copytruncate
}
```

Laravel's own `daily` log channel prunes itself (`LOG_DAILY_DAYS`, default 14);
the deployment guide covers the application, Nginx, Apache and worker logs.

### Monitoring checklist

- [ ] Cron entry installed for the same user that owns `storage/`
- [ ] Wrapper alert tested once by forcing a failure (e.g. bogus `DB_PASSWORD`)
- [ ] Backup log shows a successful run every day
- [ ] Off-site copy runs after the local one (rsync/rclone/S3 lifecycle)
- [ ] Disk space on `BACKUP_DIR` above 20% (`df -h`)
- [ ] Monthly restore into a scratch database (next section)
- [ ] Retention deletes old archives as expected (`RETENTION_DAYS`)

---

## Restore

Restoring overwrites live data. Put the application in maintenance mode first.

```bash
cd /var/www/educonnect
php artisan down
```

### 1. Unpack the archive

```bash
RESTORE_DIR="$(mktemp -d)"
tar -xzf /var/backups/educonnect/educonnect_20261029_023000.tar.gz -C "$RESTORE_DIR"
ls -la "$RESTORE_DIR"
```

You should see `database.sql.gz` (or `database.sqlite`), `storage/` and `.env`.

### 2. Restore the database (MySQL)

```bash
gunzip -c "$RESTORE_DIR/database.sql.gz" | mysql \
  --host=127.0.0.1 --user=educonnect --password educonnect
```

Use the same database name as `.env`. If the database does not exist yet,
create it first (see `docs/DEPLOYMENT.md`, step 2).

For SQLite:

```bash
cp "$RESTORE_DIR/database.sqlite" /var/www/educonnect/database/database.sqlite
sudo chown www-data:www-data /var/www/educonnect/database/database.sqlite
```

### 3. Restore uploads

```bash
sudo rsync -a "$RESTORE_DIR/storage/app/" /var/www/educonnect/storage/app/
sudo chown -R www-data:www-data /var/www/educonnect/storage/app
```

### 4. Restore `.env` only if needed

The archive contains the `.env` that was active when the backup ran. If the
current server configuration is still valid, **keep the current `.env`** and do
not overwrite it. If you are rebuilding a lost server:

```bash
cp "$RESTORE_DIR/.env" /var/www/educonnect/.env
php artisan key:generate   # only if APP_KEY was lost, then sessions/logins reset
```

### 5. Rebuild caches and go live

```bash
cd /var/www/educonnect
php artisan optimize:clear
php artisan storage:link
php artisan migrate --force     # applies any migration newer than the backup
php artisan optimize
php artisan up
```

### 6. Check

- Log in as the admin and open the dashboard.
- Open a post with an image and confirm the image loads.
- Open **Reports** and confirm the queue is intact.
- Join a class room as an enrolled student.

---

## Test a restore regularly

A monthly restore into a scratch database proves the archive is usable:

```bash
# 1. Structural check: the archive and the gzip stream are not truncated.
tar -tzf /var/backups/educonnect/educonnect_20261029_023000.tar.gz > /dev/null
echo "tar exit=$?"

# 2. Real restore into a scratch database.
mysql -e "CREATE DATABASE educonnect_restore_test"
RESTORE_DIR="$(mktemp -d)"
tar -xzf /var/backups/educonnect/educonnect_20261029_023000.tar.gz -C "$RESTORE_DIR"
gunzip -c "$RESTORE_DIR/database.sql.gz" | mysql educonnect_restore_test
mysql educonnect_restore_test -e "SELECT COUNT(*) FROM users; SELECT COUNT(*) FROM posts;"
mysql -e "DROP DATABASE educonnect_restore_test"
```

Record the date of the last successful restore test in the operations log.

---

## Encryption and access

The archive contains `.env`, which includes secrets. Therefore:

- keep `BACKUP_DIR` mode `700` and archives mode `600` (the script does both),
- encrypt off-site copies, for example with `gpg`:

  ```bash
  gpg --encrypt --recipient ops@example.com educonnect_20261029_023000.tar.gz
  ```

- never commit backups or `.env` to Git (`.gitignore` already excludes `.env`),
- rotate the database, mail and admin passwords after a restore onto a machine
  you do not fully trust.

---

## When a backup is not enough

The database dump does not include the Jitsi server. Jitsi rooms are ephemeral
and carry no persistent class data, so a fresh Jitsi installation is enough.
If you self-host Jitsi, back up its own configuration separately (Prosody,
Jicofo and JVB volumes).
