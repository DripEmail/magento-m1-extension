<?php

class Drip_Connect_Model_Observer_Product_DeleteBefore extends Drip_Connect_Model_Observer_Base
{
    /**
     * @param Varien_Event_Observer $observer
     */
    protected function isActive($observer) { return true; }

    /**
     * @param Varien_Event_Observer $observer
     */
    protected function executeWhenEnabled($observer)
    {
        $productStoreIds = $observer->getProduct()->getStoreIds();
        $dripStoreProductInfo = array();
        foreach ($productStoreIds as $storeId) {
            $dripStoreConfig = new Drip_Connect_Model_Configuration($storeId);
            $storeProduct = Mage::getModel('catalog/product')->setStoreId($storeId)->load($observer->getProduct()->getId());
            if($storeProduct && $dripStoreConfig->isEnabled()) {
                $productInfo = array();
                $productInfo['store_id'] = $storeId;
                $productInfo['product_id'] = $storeProduct->getId();
                $productInfo['product_sku'] = $storeProduct->getSku();
                $productInfo['product_name'] = $storeProduct->getName();
                $productInfo['product_price'] = Mage::helper('drip_connect')->priceAsCents($storeProduct->getFinalPrice()) / 100;
                array_push($dripStoreProductInfo, $productInfo);
            }
        }

        if( count($dripStoreProductInfo) > 0 ) {
            Mage::unregister(Drip_Connect_Model_Observer_Base::REGISTRY_KEY_PRODUCT_DELETED_FROM_STORES);
            Mage::register(Drip_Connect_Model_Observer_Base::REGISTRY_KEY_PRODUCT_DELETED_FROM_STORES, json_encode($dripStoreProductInfo));
        }
    }
}
