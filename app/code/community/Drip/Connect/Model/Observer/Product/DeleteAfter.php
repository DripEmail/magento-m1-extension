<?php

class Drip_Connect_Model_Observer_Product_DeleteAfter extends Drip_Connect_Model_Observer_Base
{
    /**
     * @param Varien_Event_Observer $observer
     */
    protected function executeWhenEnabled($observer)
    {
        $product = $observer->getProduct();

        Mage::helper('drip_connect/product')->proceedProductDelete($product);
    }
}
