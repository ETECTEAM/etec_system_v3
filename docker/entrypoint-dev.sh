#!/bin/sh
#
# Dev container entrypoint.
#
# The composer/npm cache and the vendor/node_modules directories are Docker
# named volumes. Docker seeds a fresh named volume from the image as root, so
# before composer/npm run we hand ownership of those paths to the app user
# (uid 1000 = host user). Without this step `composer install` fails writing
# to vendor/ or ~/.composer and the container exits immediately (crash loop).
set -e

chown -R 1000:1000 /var/www/vendor /var/www/node_modules /home/app 2>/dev/null || true

exec setpriv --reuid 1000 --regid 1000 --init-groups /bin/sh -c '
    set -e
    cd /var/www
    composer install --no-interaction --prefer-dist
    npm install
    composer run dev
'
