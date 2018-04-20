<?php

$installer = Mage::getResourceModel('sales/setup', 'core_setup');
$installer->startSetup();

$attributeCode = 'drip';

$entityTypes = array(
    'quote',
);
$options = array(
    'type' => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
    'visible' => false,
    'required' => false,
    'default' => 0,
);
foreach ($entityTypes as $entityType) {
    $installer->addAttribute($entityType, $attributeCode, $options);
}

$installer->endSetup();
