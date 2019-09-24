#!/bin/bash
set -e

# Spin up a new instance of Magento
# Add --build when you need to rebuild the Dockerfile.
./docker_compose.sh up -d

port=$(./docker_compose.sh port web 80 | cut -d':' -f2)
web_container=$(./docker_compose.sh ps -q web)

# Wait for the DB to be up.
./docker_compose.sh exec -T db /bin/bash -c 'while ! mysql --protocol TCP -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" -e "show databases;" > /dev/null 2>&1; do sleep 1; done'

magento_setup_script=$(cat <<SCRIPT
cd /var/www/html/magento/ && \
/usr/bin/php5.6 -f install.php -- \
--license_agreement_accepted 'yes' \
--locale 'en_US' \
--timezone 'America/Chicago' \
--default_currency 'USD' \
--db_host 'db' \
--db_name 'magento' \
--db_user 'magento' \
--db_pass 'magento' \
--url 'http://main.magento.localhost:$port' \
--skip_url_validation 'true' \
--use_rewrites 'yes' \
--use_secure 'no' \
--secure_base_url '' \
--use_secure_admin 'no' \
--admin_firstname 'FIRST_NAME' \
--admin_lastname 'LAST_NAME' \
--admin_email 'admin@example.com' \
--admin_username 'admin' \
--admin_password 'abc1234567890' && \
echo '<?xml version="1.0"?><config><modules><Mage_AdminNotification><active>false</active></Mage_AdminNotification></modules></config>' > /var/www/html/magento/app/etc/modules/Zzz.xml
SCRIPT
)

./docker_compose.sh exec -T -u www-data web /bin/bash -c "$magento_setup_script"

subdomain_setup=$(cat <<SCRIPT
FN="/var/www/html/magento/index.php"; NL=\$(wc -l "\$FN"); sed -i \${NL%% *}'i\switch(\$_SERVER["HTTP_HOST"]) { case "site1.magento.localhost:$port": \$mageRunCode = "site1_website"; \$mageRunType = "website"; break; }' "\$FN"
SCRIPT
)
./docker_compose.sh exec -T -u www-data web /bin/bash -c "$subdomain_setup"

# Backup for reset.
mkdir db_data/
./docker_compose.sh exec -e MYSQL_PWD=magento db mysqldump -u magento magento > db_data/dump.sql
