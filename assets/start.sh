#!/bin/bash
node /assets/scripts/prestart.mjs /assets/nginx.template.conf /etc/nginx.conf
supervisord -c /etc/supervisord.conf -n
