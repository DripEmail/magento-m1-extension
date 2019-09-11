<?php

class Drip_Connect_Model_Observer_Product_SaveAfter extends Drip_Connect_Model_Observer_Base
{
    /**
     * @param Varien_Event_Observer $observer
     */
    protected function executeWhenEnabled($observer)
    {
        $product = $observer->getProduct();
        $product->load($product->getId());
        if (Mage::registry(Drip_Connect_Helper_Product::REGISTRY_KEY_IS_NEW)) {
            Mage::helper('drip_connect/product')->proceedProductNew($product);
        } else {
            if ($this->isProductChanged($product)) {
                Mage::helper('drip_connect/product')->proceedProduct($product);
            }
        }
        Mage::unregister(Drip_Connect_Helper_Product::REGISTRY_KEY_IS_NEW);
        Mage::unregister(Drip_Connect_Helper_Product::REGISTRY_KEY_OLD_DATA);
    }

    /**
     * compare orig and new data
     *
     * @param Mage_Catalog_Model_Product $product
     */
    protected function isProductChanged($product)
    {
        $oldData = Mage::registry(Drip_Connect_Helper_Product::REGISTRY_KEY_OLD_DATA);
        unset($oldData['occurred_at']);
        $newData = Mage::helper('drip_connect/product')->prepareData($product);
        unset($newData['occurred_at']);

        return (Mage::helper('core')->jsonEncode($oldData) != Mage::helper('core')->jsonEncode($newData));
    }
}
