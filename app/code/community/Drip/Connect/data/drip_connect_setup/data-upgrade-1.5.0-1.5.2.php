<?php
$installer = $this;
$installer->startSetup();

$model = Mage::getModel('core/config');

$model->saveConfig('dripconnect_general/api_settings/timeout', 30000, 'default', 0);

$installer->endSetup();
