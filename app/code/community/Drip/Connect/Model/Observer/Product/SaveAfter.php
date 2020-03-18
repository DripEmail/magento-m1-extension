<?php

class Drip_Connect_Model_Observer_Product_SaveAfter extends Drip_Connect_Model_Observer_Product_ProductBase {
    /**
     * @param Varien_Event_Observer $observer
     */
    protected function executeWhenEnabled($observer)
    {
        $product = $observer->getProduct();  
        $isNewProduct = Mage::registry(Drip_Connect_Model_Observer_Base::REGISTRY_KEY_PRODUCT_IS_NEW);
        foreach ($product->getStoreIds() as $storeId) {
            $storeProduct = Mage::getModel('catalog/product')->setStoreId($storeId)->load($product->getId());
            $dripStoreConfig = new Drip_Connect_Model_Configuration($storeId);
            if( $storeProduct !== false && $dripStoreConfig->isEnabled()) {
                $productTransformer = new Drip_Connect_Model_Transformer_Product($storeProduct, $dripStoreConfig);
                if ($isNewProduct) {
                    $productTransformer->proceedProductNew();
                } else {
                    $productTransformer->proceedProductChanged();
                }
            }
        }
        Mage::unregister(Drip_Connect_Model_Observer_Base::REGISTRY_KEY_PRODUCT_IS_NEW);
    }
}
