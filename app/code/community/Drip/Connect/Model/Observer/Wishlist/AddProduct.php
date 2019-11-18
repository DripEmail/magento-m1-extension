<?php

class Drip_Connect_Model_Observer_Wishlist_AddProduct extends Drip_Connect_Model_Observer_Base
{
    /**
     * Call rest api endpoint with info about customer and product added
     * @param $observer
     */
    protected function executeWhenEnabled($observer)
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $product = $observer->getProduct();

        $config = Drip_Connect_Model_Configuration::forCurrentScope();

        Mage::helper('drip_connect/wishlist')->doWishlistEvent(
            $config,
            Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_WISHLIST_ADD_PRODUCT,
            $customer,
            $product
        );
    }
}
