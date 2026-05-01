#!/bin/bash
cd /var/www/trustai
git pull origin main
chown -R www-data:www-data /var/www/trustai
chmod -R 755 /var/www/trustai
systemctl reload nginx
