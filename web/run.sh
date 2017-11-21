#!/bin/bash
set -e

# Enter directory
cd  /var/www/html

/usr/local/bin/composer install

if [ ! -e public/assets ]; then
    # Create symbolic links to images folder
    ln -s /var/www/html/storage/app/public public/assets
fi

# Change folder permission
chmod -R 0777 storage/
#chmod -R 0777 public/uploads/
chmod -R 0777 public/assets/

chown -R www-data:www-data /var/www/html

#install ssl (letsencrypt)
#Start Apache2 service

: "${APACHE_CONFDIR:=/etc/apache2}"
: "${APACHE_ENVVARS:=$APACHE_CONFDIR/envvars}"
if test -f "$APACHE_ENVVARS"; then
    . "$APACHE_ENVVARS"
fi

# Apache gets grumpy about PID files pre-existing
: "${APACHE_PID_FILE:=${APACHE_RUN_DIR:=/var/run/apache2}/apache2.pid}"
rm -f "$APACHE_PID_FILE"

#install ssl (letsencrypt)
if [ ! -e /etc/letsencrypt/live/guideup.com.br/privkey.pem ]
then
  echo "installing ssl"
  a2dismod ssl
  apache2ctl start
  certbot-auto -n certonly --no-self-upgrade --agree-tos --standalone --no-bootstrap -t -m "romuloprandini@gmail.com" -d guideup.com.br
  ln -s /etc/letsencrypt/live/guideup.com.br /etc/letsencrypt/certs
  apache2ctl stop
  a2enmod ssl
else
  certbot-auto renew -n --no-self-upgrade --no-bootstrap --force-renew
fi

exec apache2 -DFOREGROUND "$@"
