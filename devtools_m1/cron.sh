#!/bin/bash
set -e

# Reset cron
./docker_compose.sh exec -T db mysql -u magento -pmagento magento -e "DELETE FROM cron_schedule WHERE job_code LIKE 'drip_%' AND status = 'running';"

# Nuke all existing cron runs for this plugin and create a new one.
./docker_compose.sh exec -T db mysql -u magento -pmagento magento -e "DELETE FROM cron_schedule WHERE job_code IN ('drip_connect_sync_customers', 'drip_connect_sync_customers');"
./docker_compose.sh exec -T db mysql -u magento -pmagento magento -e "INSERT INTO cron_schedule (job_code, status, created_at, scheduled_at) VALUES ('drip_connect_sync_customers', 'pending', NOW(), NOW()), ('drip_connect_sync_orders', 'pending', NOW(), NOW());"

[[ $CONTINUOUS_INTEGRATION = "true" ]] && WEB="web-travis" || WEB="web-local"
./docker_compose.sh exec -T -u www-data $WEB /bin/bash -c "cd /var/www/html/magento/ && php cron.php -mdefault"
./docker_compose.sh exec -T -u www-data $WEB /bin/bash -c "cd /var/www/html/magento/ && php cron.php -malways"
