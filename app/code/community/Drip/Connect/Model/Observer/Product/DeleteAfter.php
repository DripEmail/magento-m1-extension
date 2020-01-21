<?php

class Drip_Connect_Model_Observer_Product_DeleteAfter extends Drip_Connect_Model_Observer_Base
{
     /**
     * @param Varien_Event_Observer $observer
     */
    protected function isActive($observer) {
        // after the save, there are no store IDs that are associated with the product,
        // so we depend on DeleteBefore to setup a registry value on this request that
        // flags us as being "active"
        $flag = Mage::registry(Drip_Connect_Model_Observer_Base::REGISTRY_KEY_PRODUCT_DELETED_FROM_STORES);
        if ( $flag ) {
            return (bool) count(json_decode($flag));
        }
        return false;
    }

    
    /**
     * @param Varien_Event_Observer $observer
     */
    protected function executeWhenEnabled($observer)
    {
        // after the save, there are no product to load for each store (it's been delted)
        // so we depend on DeleteBefore to setup a registry value on this request that
        // has all the information necessary to delete the request
        $product = $observer->getProduct();
        $dripProductStore = json_decode(Mage::registry(Drip_Connect_Model_Observer_Base::REGISTRY_KEY_PRODUCT_DELETED_FROM_STORES));
        foreach ($dripProductStore as $productStoreInfo) {
            $dripStoreConfig = new Drip_Connect_Model_Configuration($productStoreInfo->store_id);
            $productTransformer = new Drip_Connect_Model_Transformer_Product($observer->getProduct(), $dripStoreConfig);
            $productTransformer->proceedProductDelete($productStoreInfo);
        }
        Mage::unregister(Drip_Connect_Model_Observer_Base::REGISTRY_KEY_PRODUCT_DELETED_FROM_STORES);
    }
}
