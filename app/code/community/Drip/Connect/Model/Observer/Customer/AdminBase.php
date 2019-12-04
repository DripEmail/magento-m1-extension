<?php

abstract class Drip_Connect_Model_Observer_Customer_AdminBase extends Drip_Connect_Model_Observer_Base
{
    protected function isActive($observer) {
        // When running from the admin, we need to do some more digging to determine whether we are active.
        $customer = $observer->getCustomer();

        $storeId = Mage::helper('drip_connect/customer')->getCustomerStoreId($customer);
        $config = new Drip_Connect_Model_Configuration($storeId);

        return $config->isEnabled();
    }
}
