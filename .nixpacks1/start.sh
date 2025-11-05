#!/bin/bash

mkdir -p /run/php

cp /assets/nginx.template.conf /etc/nginx.conf

php-fpm -y /assets/php-fpm.conf -D

nginx -g "daemon off;"
