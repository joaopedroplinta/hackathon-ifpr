#!/bin/sh
# Railway mounts volumes owned by root. Hand the private upload volume to
# www-data, then drop root for the real process. Already non-root (docker
# run -u, RAILWAY_RUN_UID): just run the command.
set -e

if [ "$(id -u)" = 0 ]; then
    mkdir -p /var/www/html/storage/app/private
    chown -R www-data:www-data /var/www/html/storage/app/private
    exec su-exec www-data "$@"
fi

exec "$@"
