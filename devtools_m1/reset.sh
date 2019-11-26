#!/bin/bash
set -e

./docker_compose.sh exec -T -e MYSQL_PWD=magento db mysql -u magento magento -e "DROP DATABASE magento; CREATE DATABASE magento"
./docker_compose.sh exec -T -e MYSQL_PWD=magento db mysql -u magento magento < db_data/dump.sql

# Force the ids to be different so that we don't risk a test that passes because the IDs ended up matching.
./docker_compose.sh exec -T -e MYSQL_PWD=magento db mysql -u magento magento -e "ALTER TABLE core_website AUTO_INCREMENT = 100"
./docker_compose.sh exec -T -e MYSQL_PWD=magento db mysql -u magento magento -e "ALTER TABLE core_store_group AUTO_INCREMENT = 200"
./docker_compose.sh exec -T -e MYSQL_PWD=magento db mysql -u magento magento -e "ALTER TABLE core_store AUTO_INCREMENT = 300"
