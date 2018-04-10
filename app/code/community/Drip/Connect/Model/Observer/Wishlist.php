<?php
/**
 * Wishlist actions
 */

class Drip_Connect_Model_Observer_Wishlist
{

    /**
     * Call rest api endpoint with info about customer and product added
     * @param $observer
     */
    public function addProduct($observer)
    {
        if(!Mage::helper('drip_connect')->isModuleActive()) {
            return;
        }

        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $product = $observer->getProduct();

        $this->doWishlistEvent(
            Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_WISHLIST_ADD_PRODUCT,
            $customer,
            $product
        );
    }

    /**
     * Call rest api endpoint with info about customer and product removed
     * @param $observer
     */
    public function removeProduct($observer) {
        if(!Mage::helper('drip_connect')->isModuleActive()) {
            return;
        }


        $wishlistItemId = filter_var(Mage::app()->getRequest()->getParam('item'), FILTER_SANITIZE_NUMBER_INT);
        if($wishlistItemId){

            $wishlistItem = Mage::getModel('wishlist/item')->load($wishlistItemId);
            $product = Mage::getModel('catalog/product')->load($wishlistItem->getProductId());
            $customer = Mage::getSingleton('customer/session')->getCustomer();

            $this->doWishlistEvent(
                Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_WISHLIST_REMOVE_PRODUCT,
                $customer,
                $product
            );
        }
    }

    /**
     * Customer can set wishlist quantity to zero on products from their wishlist and then update entire wishlist.
     * We need to call drip api and send wishlist removed event for products having quantity zero.
     *
     * @param $observer
     */
    public function removeProductsWithoutQuantity($observer) {
        if(!Mage::helper('drip_connect')->isModuleActive()) {
            return;
        }

        //get the wishlist
        $wishlistId = filter_var(Mage::app()->getRequest()->getParam('wishlist_id'), FILTER_SANITIZE_NUMBER_INT);
        $wishlist = Mage::getModel('wishlist/wishlist');
        if ($wishlistId) {
            $wishlist->load($wishlistId);
        }

        if(!$wishlist->getId()) {
            return;
        }
        
        //loop through each product and check quantity
        if ($_POST && isset($_POST['description']) && is_array($_POST['description'])) {
            foreach ($_POST['description'] as $itemId => $description) {
                $item = Mage::getModel('wishlist/item')->load($itemId);
                if ($item->getWishlistId() != $wishlist->getId()) {
                    continue;
                }

                //item qty set to zero
                if (isset($_POST['qty'][$itemId]) && $_POST['qty'][$itemId] == 0) {
                    $customer = Mage::getSingleton('customer/session')->getCustomer();
                    $product = Mage::getModel('catalog/product')->load($item->getProductId());

                    $this->doWishlistEvent(
                        Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_WISHLIST_REMOVE_PRODUCT,
                        $customer,
                        $product
                    );
                }
            }
        }
    }

    /**
     * @param $action
     * @param $customer
     * @param $product
     *
     * @return mixed
     */
    private function doWishlistEvent($action, $customer, $product) {
          return Mage::getModel('drip_connect/ApiCalls_Helper_RecordAnEvent', array(
            'email' => $customer->getEmail(),
            'action' => $action,
            'properties' => array(
                'product_id' => $product->getId(),
                'categories' => Mage::helper('drip_connect')->getProductCategoryNames($product),
                'brand' => $product->getAttributeText('manufacturer'),
                'name' => $product->getName(),
                'price' => Mage::helper('drip_connect')->priceAsCents($product->getFinalPrice()),
                'currency' => Mage::app()->getStore()->getCurrentCurrencyCode(),
                'image_url' => Mage::getModel('catalog/product_media_config') ->getMediaUrl($product->getThumbnail()),
                'source' => 'magento'
            ),
        ))->call();
    }

}