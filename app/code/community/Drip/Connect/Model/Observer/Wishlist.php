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

        try {
            $image = (string)Mage::helper('catalog/image')->init($product, 'thumbnail')->resize(160, 160);
        } catch (Exception $e) {
            $image = '';
        }

        $response = Mage::getModel('drip_connect/ApiCalls_Helper_RecordAnEvent', array(
            'email' => $customer->getEmail(),
            'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_WISHLIST_ADD_PRODUCT,
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