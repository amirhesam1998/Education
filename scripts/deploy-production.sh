#!/usr/bin/env bash

set -euo pipefail

DEPLOY_BRANCH="${DEPLOY_BRANCH:-main}"

if [[ ! "$DEPLOY_BRANCH" =~ ^[A-Za-z0-9._/-]+$ ]]; then
    echo "Invalid deployment branch."
    exit 1
fi

if [[ ! -f .env ]]; then
    echo "Production .env is missing; deployment stopped."
    exit 1
fi

if [[ "$(git branch --show-current)" != "$DEPLOY_BRANCH" ]]; then
    echo "Server checkout must already be on $DEPLOY_BRANCH; deployment stopped."
    exit 1
fi

git fetch --prune origin "$DEPLOY_BRANCH"
git pull --ff-only origin "$DEPLOY_BRANCH"

composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build

php artisan migrate --force --no-interaction
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
