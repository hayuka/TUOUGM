#!/usr/bin/env bash
set -euo pipefail

# Re-apply the normal localhost-only Compose configuration. Existing volumes remain attached.
docker compose -f docker-compose.yml up -d wordpress

echo "LAN test port removed; the normal localhost-only WordPress service is restored."
echo "Existing Docker volumes were not removed."
