#!/bin/bash

set -e

./docker_compose.sh exec web tail -f -n100 /var/www/html/magento/var/log/drip.log
