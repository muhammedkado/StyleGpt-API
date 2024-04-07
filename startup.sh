#!/bin/bash

sed -i 's|root /home/site/wwwroot/;|root /home/site/wwwroot/public;|' /etc/nginx/sites-available/default
sed -i '/location \/ {/ {n;s/.*/try_files $uri $uri\/ \/index.php?$args;/}' /etc/nginx/sites-available/default

service nginx restart
