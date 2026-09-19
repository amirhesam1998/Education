# Production deployment

This repository deploys from the `main` branch through GitHub Actions to
`https://moshaver-moradi.ir` (`62.60.128.175`). The application stays on the
server; the workflow connects with SSH and runs `scripts/deploy-production.sh`.

## One-time server setup

1. Create a non-root deployment user and clone this repository to a permanent
   path, for example `/var/www/moshaver-moradi.ir`.
2. On the server, create the production `.env`. Set at least:

   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://moshaver-moradi.ir
   ```

   Keep the existing production database credentials and `APP_KEY` in this
   file. It is never copied from GitHub and is not replaced during deploys.
3. Install PHP 8.3 (including SQLite/MySQL driver used by the production
   database), Composer, Git, Node.js 20, and NPM. Give the web-server user
   write access to `storage` and `bootstrap/cache`.
4. Configure Nginx/Apache so the document root is
   `/var/www/moshaver-moradi.ir/public`, then issue the TLS certificate for
   `moshaver-moradi.ir`.
5. Give the deployment user read access to this GitHub repository (normally a
   repository deploy key on the server), so `git pull` can run there.

The deploy script uses `git pull --ff-only`; it intentionally stops if the
server checkout has local changes instead of overwriting them.

## GitHub Actions secrets

In **GitHub repository → Settings → Secrets and variables → Actions**, add:

| Secret | Value |
| --- | --- |
| `DEPLOY_HOST` | `62.60.128.175` |
| `DEPLOY_PORT` | SSH port, usually `22` |
| `DEPLOY_USER` | Non-root deployment user |
| `DEPLOY_PATH` | Application path, e.g. `/var/www/moshaver-moradi.ir` |
| `DEPLOY_SSH_KEY` | Private SSH key for `DEPLOY_USER` |
| `DEPLOY_KNOWN_HOSTS` | Pinned host key for the server |

Generate the last value from a trusted machine and verify its fingerprint with
your hosting provider before saving it:

```bash
ssh-keyscan -H 62.60.128.175
```

Do not use `ssh-keyscan` inside the workflow: a pinned `DEPLOY_KNOWN_HOSTS`
secret avoids accepting a changed server key automatically.

## What each deployment does

After CI validation and isolated SQLite tests pass, GitHub Actions connects to
the server and runs:

1. Fast-forward update from `main`.
2. Production Composer install and Vite build (creates `public/build/manifest.json`).
3. `php artisan migrate --force` for pending additive migrations only.
4. Laravel cache rebuild and queue worker restart.

No seed, reset, truncate, import, or database-wipe command is part of this
deployment process.
