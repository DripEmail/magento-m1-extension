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

        Mage::helper('drip_connect')->doWishlistEvent(
            Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_WISHLIST_ADD_PRODUCT,
            $customer,
            $product
        );
    }
}
