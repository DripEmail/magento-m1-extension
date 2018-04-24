<?php

$customerInstaller = Mage::getResourceModel('customer/setup', 'customer_setup');
$customerInstaller->startSetup();

$attributes = array(
    'drip' => array(
        'data' => array(
            'label' => 'Drip',
            'type' => 'int',
            'default' => '0',
            'visible' => false,
            'required' => false,
            'system' => false,
            'user_defined' => true,
        ),
        'entityType' => 'customer',
    ),
);

foreach ($attributes as $attributeCode => $attribute) {
    if ($customerInstaller->getAttributeId($attribute['entityType'], $attributeCode)) {
        $customerInstaller->removeAttribute($attribute['entityType'], $attributeCode);
    }
    $customerInstaller
        ->addAttribute($attribute['entityType'], $attributeCode, $attribute['data']);
}
