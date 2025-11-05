#!/bin/bash
node /.nixpacks/scripts/prestart.mjs /.nixpacks/nginx.template.conf /etc/nginx.conf
supervisord -c /etc/supervisord.conf -n
