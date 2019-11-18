<?php

class Drip_Connect_Model_Observer_Wishlist_PredispatchWishlistIndexRemove extends Drip_Connect_Model_Observer_Base
{
    /**
     * Call rest api endpoint with info about customer and product removed
     * @param $observer
     */
    protected function executeWhenEnabled($observer)
    {
        $wishlistItemId = filter_var(Mage::app()->getRequest()->getParam('item'), FILTER_SANITIZE_NUMBER_INT);
        if ($wishlistItemId) {
            $wishlistItem = Mage::getModel('wishlist/item')->load($wishlistItemId);
            $product = Mage::getModel('catalog/product')->load($wishlistItem->getProductId());
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $config = Drip_Connect_Model_Configuration::forCurrentScope();

            Mage::helper('drip_connect/wishlist')->doWishlistEvent(
                $config,
                Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_WISHLIST_REMOVE_PRODUCT,
                $customer,
                $product
            );
        }
    }
}
