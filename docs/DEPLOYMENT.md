# Deploying EduConnect

This guide takes a fresh Ubuntu/Debian server to a working HTTPS installation.
The same steps apply to a shared host with small adjustments (no root cron,
different web-server panel). Commands assume the application lives in
`/var/www/educonnect` and runs as the `www-data` user.

---

## 1. Server packages

The application targets **PHP 8.2 or newer** (tested on 8.3) and **MySQL 8**.
The package names below use the `php8.3-*` prefix; on a PHP 8.2 server replace
`8.3` with `8.2` everywhere (including `php8.2-fpm.sock` in the vhosts).

```bash
sudo apt update
sudo apt install -y nginx mysql-server php8.3-fpm php8.3-cli php8.3-mysql \
  php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-intl \
  unzip git nodejs npm certbot python3-certbot-nginx
```

Required PHP extensions: `openssl`, `pdo_mysql`, `mbstring`, `tokenizer`, `xml`,
`ctype`, `json`, `fileinfo`, `gd` (the upload pipeline uses GD to decode, resize
and re-encode every image; without `gd` uploads fail).

Check the versions:

```bash
php -v          # 8.2 or newer
mysql --version # 8.x
node -v         # 20 or newer
php -m | grep -E 'gd|fileinfo|pdo_mysql'
```

Install Composer from <https://getcomposer.org/download/> if it is not already
available.

---

## 2. Database

```bash
sudo mysql
```

```sql
CREATE DATABASE educonnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'educonnect'@'localhost' IDENTIFIED BY 'a-strong-database-password';
GRANT ALL PRIVILEGES ON educonnect.* TO 'educonnect'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Do not use the MySQL `root` account for the application.

---

## 3. Upload the code

```bash
sudo mkdir -p /var/www/educonnect
sudo chown -R "$USER":www-data /var/www/educonnect
git clone <repository-url> /var/www/educonnect
cd /var/www/educonnect
```

If the code is uploaded as an archive instead, unpack it in the same directory.

---

## 4. Environment file

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```dotenv
APP_NAME=EduConnect
APP_ENV=production
APP_DEBUG=false
APP_URL=https://educonnect.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=educonnect
DB_USERNAME=educonnect
DB_PASSWORD=a-strong-database-password

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=no-reply@educonnect.example.com
MAIL_PASSWORD=smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@educonnect.example.com
MAIL_FROM_NAME="${APP_NAME}"

# The admin account created by AdminUserSeeder. Use a long random value.
ADMIN_NAME="EduConnect Admin"
ADMIN_EMAIL=admin@educonnect.example.com
ADMIN_PASSWORD=a-long-random-admin-password

# Jitsi. Leave the default for meet.jit.si or point at your own server.
JITSI_DOMAIN=meet.jit.si
# JITSI_SCRIPT_URL=https://meet.jit.si/external_api.js
```

`APP_DEBUG` must stay `false` in production: debug pages expose environment
values and stack traces.

---

## 5. Install and build

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build

php artisan migrate --force
php artisan storage:link
```

Do **not** run `db:seed` on production. Create the admin account only:

```bash
php artisan db:seed --class=AdminUserSeeder --force
```

The seeder reads `ADMIN_NAME`, `ADMIN_EMAIL` and `ADMIN_PASSWORD` from `.env`
and refuses to run if the password is blank.

---

## 6. Permissions

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

The web server only needs read access to the rest of the code. Never point the
document root at the project root: it must be `public/`.

---

## 7. Cache the configuration

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Whenever `.env` or the routes change, rebuild the caches:

```bash
php artisan optimize:clear
php artisan optimize
```

---

## 8. Nginx virtual host

```nginx
server {
    listen 80;
    server_name educonnect.example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name educonnect.example.com;

    root /var/www/educonnect/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/educonnect.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/educonnect.example.com/privkey.pem;

    client_max_body_size 5M;

    add_header X-Content-Type-Options nosniff always;
    add_header Referrer-Policy strict-origin-when-cross-origin always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable it and obtain a certificate:

```bash
sudo ln -s /etc/nginx/sites-available/educonnect /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
sudo certbot --nginx -d educonnect.example.com
```

The application also sends its own security headers through
`App\Http\Middleware\SecurityHeaders`; the two `add_header` lines above are a
belt-and-braces measure for static files.

### Apache alternative

Enable the modules and point the vhost at `public/`:

```bash
sudo a2enmod rewrite headers expires deflate
sudo systemctl restart apache2
```

```apache
<VirtualHost *:80>
    ServerName educonnect.example.com
    Redirect permanent / https://educonnect.example.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName educonnect.example.com
    DocumentRoot /var/www/educonnect/public

    SSLEngine on
    SSLCertificateFile      /etc/letsencrypt/live/educonnect.example.com/fullchain.pem
    SSLCertificateKeyFile   /etc/letsencrypt/live/educonnect.example.com/privkey.pem

    <Directory /var/www/educonnect/public>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>

    # Static files: same hardening headers as Nginx.
    Header always set X-Content-Type-Options "nosniff"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set X-Frame-Options "SAMEORIGIN"

    # Do not serve dotfiles or the framework directory.
    <FilesMatch "^\.">
        Require all denied
    </FilesMatch>

    ErrorLog  ${APACHE_LOG_DIR}/educonnect-error.log
    CustomLog ${APACHE_LOG_DIR}/educonnect-access.log combined
</VirtualHost>
```

`public/.htaccess` (shipped with Laravel) handles the front controller and the
`Authorization` header. Obtain the certificate with
`sudo certbot --apache -d educonnect.example.com`.

The application sends its own headers through `SecurityHeaders`; the Apache
`Header` lines cover static files that bypass PHP. The Jitsi room removes
`X-Frame-Options` at the application level, so do not add a global
`Header always set X-Frame-Options` inside a `<Location>` that also matches
`/classes/*/room` if you customise this further.

---

## 8b. Shared hosting (cPanel / Plesk / no root)

Shared hosts work with the same application; only the server management steps
change:

1. **PHP version.** Select PHP 8.2+ in the host panel and enable `pdo_mysql`,
   `mbstring`, `gd`, `fileinfo`, `openssl`, `curl`, `zip`.
2. **Document root.** Point the domain's document root at
   `/home/ACCOUNT/educonnect/public`. If the panel cannot change it, upload the
   project one level above `public_html` and copy/redirect `public_html` to
   `public/` (or add a small `index.php` that requires
   `../educonnect/public/index.php`).
3. **Composer and Node.** Run `composer install --no-dev --optimize-autoloader`
   and build the assets locally (`npm ci && npm run build`) then upload
   `public/build/` if Node is unavailable on the host.
4. **Database.** Create the database and user in the panel; copy the generated
   name/user/password into `.env` (shared hosts prefix both, e.g.
   `account_educonnect`).
5. **`.env`.** Create it outside the document root if the panel supports it;
   otherwise ensure the file is not web-accessible. Set `APP_ENV=production`,
   `APP_DEBUG=false`, `APP_URL=https://your-domain`.
6. **Writable paths.** `storage/` and `bootstrap/cache/` must be writable by the
   PHP user (usually your account user, mode `775`).
7. **Cron.** Add one cron job in the panel:
   `* * * * * /usr/local/bin/php /home/ACCOUNT/educonnect/artisan schedule:run >> /dev/null 2>&1`
   Use the panel's PHP binary path, not the system one, so the CLI version
   matches the web version.
8. **Queue.** Without Supervisor, either keep `QUEUE_CONNECTION=sync` (mail and
   notifications are sent during the request) or run a cron-driven worker:
   `* * * * * /usr/local/bin/php /home/ACCOUNT/educonnect/artisan queue:work --stop-when-empty >> /dev/null 2>&1`.
   `sync` is the simpler and supported choice for small installations.
9. **Storage link.** Run `php artisan storage:link`; some panels forbid symlinks,
   in which case map a subdomain or an alias to `storage/app/public`.
10. **HTTPS.** Enable the host's free Let's Encrypt certificate and force HTTPS;
    keep `SESSION_SECURE_COOKIE=true`.

Shared hosting cannot run `apt`, Supervisor or `certbot` directly; the rest of
this guide (environment, migrations, caches, checks) applies unchanged.

---

## 9. Scheduler

The “class starting soon” reminder and the automatic end of a running class
(`classes:complete-finished`) both run every minute through Laravel's scheduler.
Add one cron entry for the application user:

```bash
sudo crontab -u www-data -e
```

```cron
* * * * * cd /var/www/educonnect && php artisan schedule:run >> /dev/null 2>&1
```

Test it once:

```bash
php artisan classes:notify-starting-soon
php artisan classes:complete-finished
```

---

## 10. Queue workers (required in production)

All five notification classes implement `ShouldQueue`. The production settings
above use `QUEUE_CONNECTION=database`, so a worker **must** run or the
notifications stay in the `jobs` table and users never see them.

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

Keep it alive with Supervisor (or systemd). A minimal Supervisor program:

```ini
[program:educonnect-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/educonnect/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/educonnect-worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start educonnect-worker:*
```

Restart workers after every deploy so they load the new code:

```bash
php artisan queue:restart
```

Check that the queue is healthy:

```bash
php artisan queue:monitor database:default --max=100
```

Local development and the demo use `QUEUE_CONNECTION=sync`, so no worker is
needed there.

---

## 11. Jitsi Meet

The room page loads the Jitsi External API from `JITSI_DOMAIN` and embeds the
conference in an iframe. For a school network, self-host Jitsi (Docker
installation from <https://jitsi.github.io/handbook/>) and set:

```dotenv
JITSI_DOMAIN=meet.example.com
```

Then `php artisan config:cache`. Nothing else changes: room names, moderator
rules and the page URL stay the same.

If a proxy serves the External API from another host, set
`JITSI_SCRIPT_URL=https://cdn.example.com/external_api.js` as well.

The server's `Content-Security-Policy` for the room page is produced by
`SecurityHeaders` and automatically allows framing only the configured Jitsi
domain.

---

## 12. Mail

Password reset (FR-3) needs a working mail transport. Keep `MAIL_MAILER=log` on
local machines and configure SMTP in production as shown in step 4.

Test it:

```bash
php artisan tinker
>>> Mail::raw('EduConnect mail test', fn ($m) => $m->to('you@example.com')->subject('Test'));
```

Password reset works with either transport:

- `QUEUE_CONNECTION=sync` sends the branded reset mail during the request (no
  worker needed),
- `QUEUE_CONNECTION=database` queues it; the Supervisor worker in step 10 sends
  it. The reset link is identical either way.

If mail silently fails, check `storage/logs/laravel.log` and, for `MAIL_MAILER=log`,
`storage/logs/laravel.log` again — the log driver writes the whole message there.

---

## 12b. Logging and error handling

| Setting | Production value | Why |
| --- | --- | --- |
| `APP_DEBUG` | `false` | debug pages leak `.env` values and stack traces |
| `LOG_CHANNEL` | `daily` | one file per day instead of one ever-growing file |
| `LOG_LEVEL` | `warning` | keeps info noise out, keeps errors and failures |
| `LOG_DAILY_DAYS` | `14` | Laravel prunes old daily files automatically |
| `APP_ENV` | `production` | disables verbose error output |

```dotenv
APP_DEBUG=false
LOG_CHANNEL=daily
LOG_LEVEL=warning
LOG_DAILY_DAYS=14
```

The application returns the branded pages in `resources/views/errors/` for
403, 404, 419, 500 and 503, so a visitor never sees a raw stack trace. The 500
page is used for every unhandled exception; `APP_DEBUG=false` is what activates
it instead of the Ignition/whoops trace.

### Log files to watch

```bash
tail -f /var/www/educonnect/storage/logs/laravel-$(date +%F).log
sudo tail -f /var/log/nginx/educonnect-error.log
sudo tail -f /var/log/educonnect-worker.log
sudo tail -f /var/log/educonnect-backup.log
```

### logrotate

Laravel's daily channel prunes itself; Nginx, Apache, Supervisor and the backup
log need logrotate. Minimal `/etc/logrotate.d/educonnect`:

```conf
/var/log/educonnect-*.log {
    daily
    rotate 14
    compress
    delaycompress
    missingok
    notifempty
    copytruncate
}
```

If the app runs under a non-standard user, add `su www-data www-data` at the top
of that block.

### Fail loudly enough

`LOG_LEVEL=warning` plus the log files above is the minimum. For a real
deployment also:

- enable the host's uptime monitor on `https://educonnect.example.com/up`
  (Laravel's health endpoint, configured in `bootstrap/app.php`),
- forward `laravel-*.log` to your central log service if you have one,
- alert on the backup log: a cron wrapper that mails when `scripts/backup.sh`
  exits non-zero (see `docs/BACKUP.md` §Failure handling).

---

## 13. Post-deploy checklist

### Server side

```bash
php artisan about                     # env, cache, drivers
php artisan migrate:status            # every migration "Ran?"
php artisan schedule:list             # both commands present
php artisan route:list | head         # app boots with cached routes
php artisan queue:monitor database:default --max=100
```

```bash
# caches were built, not stale
ls bootstrap/cache/config.php bootstrap/cache/routes-v7.php
# uploads are reachable
curl -I https://educonnect.example.com/storage/README.md 2>/dev/null || true
# health endpoint
curl -fsS https://educonnect.example.com/up && echo OK
# security headers
curl -sI https://educonnect.example.com/ | grep -Ei 'x-frame|x-content|referrer|permissions'
# TLS expiry (renewal is automatic, but confirm the timer)
sudo certbot certificates | grep -E 'Certificate Name|Expiry'
sudo systemctl list-timers | grep certbot
```

### Browser walkthrough

1. `https://educonnect.example.com` loads over HTTPS; the landing page shows the
   hero, features and CTAs; the padlock has no warnings.
2. Register a student **and** a teacher; both reach their own dashboard and the
   role badge is correct.
3. Log in with the admin account and open **Users**, **Posts**, **Classes**,
   **Reports** and the **style guide**.
4. Create a class as a teacher, enrol as a student, start the class and join the
   Jitsi room from two browsers (camera/microphone prompt appears only there).
5. Report a post as a student, then hide it, block the author and dismiss another
   report as the admin; confirm each action lands in the queue with the right
   status.
6. Publish a post with an image; confirm it is resized (inspect the network tab)
   and that a `.php` upload is rejected.
7. Request a password reset and confirm the branded email arrives; reset the
   password and log in with it.
8. Set the browser to 360 px and 768 px: the mobile nav, feed and dashboard must
   stay usable; toggle dark mode and check contrast.
9. Trigger a "class starting soon" reminder and confirm the notification bell
   updates (needs the scheduler + queue worker).
10. Run the automated suite once on the server (or in CI):

```bash
php artisan test
```

Record the release (commit/tag, date, who deployed, test result) in your ops log.

---

## 14. Updates and rollback

### Deploy a new release

```bash
cd /var/www/educonnect
php artisan down --retry=60

# Always take a backup first (see docs/BACKUP.md).
./scripts/backup.sh

git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear && php artisan optimize
php artisan queue:restart

php artisan up
```

Then re-run the post-deploy checklist. Keep the previous release tag and the
backup file name in the ops log; that is the rollback path.

### Roll back

```bash
cd /var/www/educonnect
php artisan down --retry=60

git checkout <previous-tag>
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Only if the new release added migrations that are safe to reverse:
php artisan migrate:rollback --step=1 --force

php artisan optimize:clear && php artisan optimize
php artisan queue:restart
php artisan up
```

If the migration cannot be reversed safely, stop and restore the database from
the backup taken before the deploy (`docs/BACKUP.md` §Restore). Do not try to
"fix forward" on a live database without a backup.

### Deploy log template

| Date | Release | Deployed by | Backup file | Tests | Notes |
| --- | --- | --- | --- | --- | --- |
| 2026-10-31 | v1.0.0 |  | `educonnect_20261031_023000.tar.gz` | 264 passed | first production release |

---

## 15. Production security checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] HTTPS with a valid certificate and `SESSION_SECURE_COOKIE=true`
- [ ] Strong, unique `ADMIN_PASSWORD` and database password
- [ ] Demo seeder **not** run in production
- [ ] `storage/` and `bootstrap/cache/` writable, everything else read-only
- [ ] `.env` not readable by the web server's public path or by other users
- [ ] `php artisan storage:link` done, uploads served through the app
- [ ] Scheduler cron installed
- [ ] Daily backups verified by restoring one into a scratch database
- [ ] Logs monitored (`storage/logs/laravel.log`, Nginx access/error logs)
- [ ] `composer audit` and `npm audit` reviewed after dependency updates
