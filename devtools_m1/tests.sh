#!/bin/bash
set -e

DRIP_COMPOSE_ENV=test ./setup.sh
$(npm bin)/cypress run # --record
DRIP_COMPOSE_ENV=test ./docker_compose.sh down
