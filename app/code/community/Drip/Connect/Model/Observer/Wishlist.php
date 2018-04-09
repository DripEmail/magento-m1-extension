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
     * @param $action
     * @param $customer
     * @param $product
     *
     * @return mixed
     */
    private function doWishlistEvent($action, $customer, $product) {
        try {
            $image = (string)Mage::helper('catalog/image')->init($product, 'thumbnail')->resize(160, 160);
        } catch (Exception $e) {
            $image = '';
        }

        return Mage::getModel('drip_connect/ApiCalls_Helper_RecordAnEvent', array(
            'email' => $customer->getEmail(),
            'action' => $action,
            'properties' => array(
                'product_id' => $product->getId(),
                'categories' => Mage::helper('drip_connect')->getProductCategoryNames($product),
                'brand' => $product->getAttributeText('manufacturer'),
                'name' => $product->getName(),
                'price' => Mage::helper('drip_connect')->formatPrice($product->getFinalPrice()),
                'currency' => Mage::app()->getStore()->getCurrentCurrencyCode(),
                'image_url' => $image,
                'source' => 'magento'
            ),
        ))->call();
    }

}