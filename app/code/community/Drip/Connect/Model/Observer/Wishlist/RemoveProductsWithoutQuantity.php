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

        $postDescription = filter_input(INPUT_POST, 'description', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        if (filter_input_array(INPUT_POST) && isset($post_description) && is_array($post_description)) {

            foreach ($post_description as $itemId => $description) {
                $item = Mage::getModel('wishlist/item')->load($itemId);
                if ($item->getWishlistId() !== $wishlist->getId()) {
                    continue;
                }
                $post_quantity = filter_input(INPUT_POST, 'qty', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY)[$itemId];
                if (isset($post_quantity) && $post_quantity == 0) {
                    $customer = Mage::getSingleton('customer/session')->getCustomer();
                    $product = Mage::getModel('catalog/product')->load($item->getProductId());
                    Mage::helper('drip_connect/wishlist')->doWishlistEvent(
                        Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_WISHLIST_REMOVE_PRODUCT,
                        $customer,
                        $product
                    );
                }
            }
        }
    }
}
