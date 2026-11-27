#!/usr/bin/env bash
set -euo pipefail

lan_host="${TUOUGM_LAN_TEST_HOST:?TUOUGM_LAN_TEST_HOST is required}"
if [[ ! "$lan_host" =~ ^(10\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}|172\.(1[6-9]|2[0-9]|3[01])\.[0-9]{1,3}\.[0-9]{1,3}|192\.168\.[0-9]{1,3}\.[0-9]{1,3}):8089$ ]]; then
  echo "Refusing non-private or unexpected LAN test host." >&2
  exit 1
fi

a2enmod substitute >/dev/null

cat > /etc/apache2/conf-enabled/zz-tuougm-lan-css-substitute.conf <<APACHE
<LocationMatch "^/wp-content/cache/autoptimize/css/.*\\.css$">
  <If "%{HTTP_HOST} == '${lan_host}'">
    AddOutputFilterByType SUBSTITUTE;DEFLATE text/css
    Substitute "s|http://localhost:8088||ni"
    Substitute "s|https://localhost:8088||ni"
    Substitute "s|//localhost:8088||ni"
  </If>
</LocationMatch>
APACHE

apache2ctl -t
exec apache2-foreground
