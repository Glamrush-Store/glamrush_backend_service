#!/usr/bin/env sh
set -eu

config=/etc/supervisord-worker.conf

/usr/bin/supervisorctl -c "$config" status queue-worker | grep -q 'RUNNING'
/usr/bin/supervisorctl -c "$config" status scheduler | grep -q 'RUNNING'
