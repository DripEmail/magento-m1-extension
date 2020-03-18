<?php

abstract class Drip_Connect_Model_Observer_Product_ProductBase extends Drip_Connect_Model_Observer_Base
{
    protected function isActive($observer) {
        // So the problem is products are global, but their appearance
        // per store
        // So to figure out if this isActive, we have to match a product's
        // appearance in a store that has an enabled Drip configuration.
        $product = $observer->getProduct();
        foreach ($product->getStoreIds() as $storeId) {
            $store = Mage::getModel('core/store')->load($storeId);
            $dripStoreConfig = new Drip_Connect_Model_Configuration($storeId);
            if($dripStoreConfig->isEnabled()) {
                // we found an store that this product is "presented" with
                // Drip enabled
                return true;
            }
        }
        return false;
    }
}
