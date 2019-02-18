<?php

$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn(
    $installer->getTable('newsletter/subscriber'), 'drip', "int AFTER `subscriber_confirm_code`"
);

$installer->endSetup();
