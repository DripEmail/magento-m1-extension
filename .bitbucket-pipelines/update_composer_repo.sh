#!/bin/bash

set -e
# Setup SSH
if [[ ! -d ~/.ssh ]]; then
    mkdir ~/.ssh;
fi
(umask  077 ; echo $COMPOSER_SSH_KEY | base64 --decode > ~/.ssh/id_rsa);
(umask  077 ; echo $COMPOSER_KNOWN_HOST | base64 --decode >> ~/.ssh/known_hosts);

# Run Satis on Robofirm Composer
ssh -q webuser@composer.rmgmedia.com "
    cd /var/www/composer &&
    php vendor/composer/satis/bin/satis build magento-module.json htdocs/magento/module robofirm/drip_m1connect
"
