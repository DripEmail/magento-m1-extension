<?php

class Drip_Connect_Model_Observer_Product_SaveBefore extends Drip_Connect_Model_Observer_Base
{
    /**
     * - check if product is new
     * - store old product data
     *
     * @param Varien_Event_Observer $observer
     */
    protected function executeWhenEnabled($observer)
    {
        $product = $observer->getProduct();
        Mage::unregister(Drip_Connect_Helper_Product::REGISTRY_KEY_IS_NEW);
        Mage::register(Drip_Connect_Helper_Product::REGISTRY_KEY_IS_NEW, (bool) $product->isObjectNew());

        if (!$product->isObjectNew()) {
            $storeId = Mage::helper('drip_connect')->getAdminEditStoreId();
            $orig = Mage::getModel('catalog/product')->setStoreId($storeId)->load($product->getId());
            $data = Mage::helper('drip_connect/product')->prepareData($orig);
            Mage::unregister(Drip_Connect_Helper_Product::REGISTRY_KEY_OLD_DATA);
            Mage::register(Drip_Connect_Helper_Product::REGISTRY_KEY_OLD_DATA, $data);
        } else {
            //will be needed if we create historical sync for products
            //$product->setDrip(1);
        }
    }
}
