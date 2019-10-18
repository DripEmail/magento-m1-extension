<?php

class Drip_Connect_Model_Observer_Wishlist_RemoveProductsWithoutQuantity extends Drip_Connect_Model_Observer_Base
{
    /**
     * Customer can set wishlist quantity to zero on products from their wishlist and then update entire wishlist.
     * We need to call drip api and send wishlist removed event for products having quantity zero.
     *
     * @param $observer
     */
    protected function executeWhenEnabled($observer)
    {
        //get the wishlist
        $wishlistId = filter_var(Mage::app()->getRequest()->getParam('wishlist_id'), FILTER_SANITIZE_NUMBER_INT);
        $wishlist = Mage::getModel('wishlist/wishlist');
        if ($wishlistId) {
            $wishlist->load($wishlistId);
        }

        if (!$wishlist->getId()) {
            return;
        }

        //loop through each product and check quantity
        if (filter_input_array(INPUT_POST) &&
            (null !== filter_input(INPUT_POST, 'description')) &&
            is_array(filter_input(INPUT_POST, 'description'))) {
            foreach (filter_input(INPUT_POST, 'description') as $itemId => $description) {
                $item = Mage::getModel('wishlist/item')->load($itemId);
                if ($item->getWishlistId() !== $wishlist->getId()) {
                    continue;
                }

                //item qty set to zero
                if (isset(filter_input(INPUT_POST, 'qty')[$itemId]) && filter_input(INPUT_POST, 'qty')[$itemId] == 0) {
                    $customer = Mage::getSingleton('customer/session')->getCustomer();
                    $product = Mage::getModel('catalog/product')->load($item->getProductId());

                    Mage::helper('drip_connect')->doWishlistEvent(
                        Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_WISHLIST_REMOVE_PRODUCT,
                        $customer,
                        $product
                    );
                }
            }
        }
    }
}
