#!/usr/bin/env bash
set -euo pipefail

lan_ip="${1:-}"
if [[ -z "$lan_ip" ]]; then
  echo "Usage: $0 <Mac private LAN IPv4 address>" >&2
  exit 2
fi

python3 - "$lan_ip" <<'PY'
import ipaddress
import subprocess
import sys

try:
    address = ipaddress.IPv4Address(sys.argv[1])
except ipaddress.AddressValueError:
    raise SystemExit("Expected a private IPv4 address assigned to this Mac.")

private_ranges = (
    ipaddress.IPv4Network("10.0.0.0/8"),
    ipaddress.IPv4Network("172.16.0.0/12"),
    ipaddress.IPv4Network("192.168.0.0/16"),
)
if not any(address in network for network in private_ranges):
    raise SystemExit("Refusing non-RFC1918 address; LAN publishing must stay private.")

interfaces = subprocess.run(["ifconfig"], check=True, capture_output=True, text=True).stdout
if not any(line.strip().split()[0:2] == ["inet", str(address)] for line in interfaces.splitlines()):
    raise SystemExit("The supplied address is not assigned to a local Mac interface.")
PY

export LAN_TEST_IP="$lan_ip"
docker compose -f docker-compose.yml -f docker-compose.iphone.yml config --quiet
docker compose -f docker-compose.yml -f docker-compose.iphone.yml up -d wordpress
docker compose -f docker-compose.yml -f docker-compose.iphone.yml exec -T wordpress apache2ctl -M | grep -q substitute

echo "iPhone test URL: http://${lan_ip}:8089/"
echo "LAN access is bound to this private Mac IP. No tunnel or router port-forward is used."
echo "End the session with: scripts/stop-iphone-test.sh"
