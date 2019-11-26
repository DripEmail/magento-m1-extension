<?php

abstract class Drip_Connect_Model_Observer_Order_OrderBase extends Drip_Connect_Model_Observer_Base
{
    protected function isActive($observer) {
        // When running from the admin, we need to do some more digging to determine whether we are active.
        $order = $observer->getEvent()->getOrder();
        if (!$order->getId()) {
            return false;
        }

        return Mage::helper('drip_connect')->isModuleActive($order->getStoreId());
    }
}
