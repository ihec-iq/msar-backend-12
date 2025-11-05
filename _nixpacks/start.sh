#!/bin/bash
node /_nixpacks/scripts/prestart.mjs /_nixpacks/nginx.template.conf /etc/nginx.conf
supervisord -c /etc/supervisord.conf -n
