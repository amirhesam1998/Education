# Production deployment

This repository deploys from the `main` branch through GitHub Actions to
`https://moshaver-moradi.ir` (`62.60.128.175`). GitHub Actions packages only
application code and compiled Vite assets, then extracts them over SSH without
touching production `.env`, `storage`, or user uploads.

## One-time server setup

1. Create a non-root deployment user and create a permanent application path,
   for example `/var/www/moshaver-moradi.ir`.
2. On the server, create the production `.env`. Set at least:

   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://moshaver-moradi.ir
   ```

   Keep the existing production database credentials and `APP_KEY` in this
   file. It is never copied from GitHub and is not replaced during deploys.
3. Install PHP 8.3 (including SQLite/MySQL driver used by the production
   database) and Composer. Give the web-server user
   write access to `storage` and `bootstrap/cache`.
4. Configure Nginx/Apache so the document root is
   `/var/www/moshaver-moradi.ir/public`, then issue the TLS certificate for
   `moshaver-moradi.ir`.
5. Ensure the deployment user owns the application path. It does not need a
   GitHub token or deploy key: GitHub Actions sends the verified release package
   over SSH.

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

After CI validation and isolated MySQL test-database migrations/tests pass, GitHub Actions connects to
the server and runs:

1. Isolated CI migrations/tests, then a Vite build (creates `public/build/manifest.json`).
2. Production Composer install and stale-cache clear while in maintenance mode.
3. `php artisan migrate --force` for pending additive migrations only.
4. Run the explicit, add-only `ProductionSeeder` for missing permissions and
   default settings. It never invokes demo data or the study-program snapshot.
5. Laravel cache rebuild and queue worker restart.

`StudyProgramsSnapshotSeeder` is deliberately not part of automatic deploys.
It can be run manually only when the catalogue needs updating; it matches rows
by natural keys/identity hashes, preserves manual catalogue records, and never
deletes a table.

No reset, truncate, import replacement, or database-wipe command is part of
this deployment process.

## Optional pre-deploy backup

Take database backups through the server/hosting backup system before a
schema-changing release. For MySQL, a deployment user can run `mysqldump` with
the existing `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` from
the production environment, writing the timestamped compressed file outside
`public/` (for example `/var/backups/moshaver-moradi/`). Retain the latest 10
backups. This is intentionally an infrastructure step, not a required workflow
step: a missing backup utility must never cause GitHub Actions to handle or log
database credentials.
