#!/bin/bash
set -e

DRIP_COMPOSE_ENV1=${DRIP_COMPOSE_ENV:-"test"}

docker-compose -p "devtools_m1_${DRIP_COMPOSE_ENV1}" -f docker-compose.base.yml -f "docker-compose.${DRIP_COMPOSE_ENV1}.yml" "$@"
