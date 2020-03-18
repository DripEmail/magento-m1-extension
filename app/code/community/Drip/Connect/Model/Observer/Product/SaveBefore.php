<?php

class Drip_Connect_Model_Observer_Product_SaveBefore extends Drip_Connect_Model_Observer_Product_ProductBase
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
        $isNewProduct = (bool) $product->isObjectNew();
        Mage::unregister(Drip_Connect_Model_Observer_Base::REGISTRY_KEY_PRODUCT_IS_NEW);
        Mage::register(Drip_Connect_Model_Observer_Base::REGISTRY_KEY_PRODUCT_IS_NEW, $isNewProduct);
        if( $isNewProduct ) {
            // will be needed if we create historical sync for products
            $product->setDrip(1);
        }
    }
}
