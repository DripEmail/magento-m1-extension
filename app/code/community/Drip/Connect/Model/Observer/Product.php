<?php

class Drip_Connect_Model_Observer_Product
{
    /**
     * - check if product is new
     * - store old product data
     *
     * @param Varien_Event_Observer $observer
     */
    public function beforeSave($observer)
    {
        if (!Mage::helper('drip_connect')->isModuleActive()) {
            return;
        }
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

    /**
     * @param Varien_Event_Observer $observer
     */
    public function afterSave($observer)
    {
        if (!Mage::helper('drip_connect')->isModuleActive()) {
            return;
        }
        $product = $observer->getProduct();
        $product->load($product->getId());
        if (Mage::registry(Drip_Connect_Helper_Product::REGISTRY_KEY_IS_NEW)) {
            $this->proceedProductNew($product);
        } else {
            if ($this->isProductChanged($product)) {
                $this->proceedProduct($product);
            }
        }
        Mage::unregister(Drip_Connect_Helper_Product::REGISTRY_KEY_IS_NEW);
        Mage::unregister(Drip_Connect_Helper_Product::REGISTRY_KEY_OLD_DATA);
    }


    /**
     * drip actions for product create
     *
     * @param Mage_Catalog_Model_Product $product
     */
    protected function proceedProductNew($product)
    {
        Mage::helper('drip_connect/product')->proceedProductNew($product);
    }

    /**
     * drip actions for product change
     *
     * @param Mage_Catalog_Model_Product $product
     */
    protected function proceedProduct($product)
    {
        Mage::helper('drip_connect/product')->proceedProduct($product);
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

        return (serialize($oldData) != serialize($newData));
    }
}
