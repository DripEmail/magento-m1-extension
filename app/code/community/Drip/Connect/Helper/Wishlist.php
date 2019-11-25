<?php

class Drip_Connect_Helper_Wishlist extends Mage_Core_Helper_Abstract
{
    /**
     * @param Drip_Connect_Model_Configuration $config
     * @param string $action
     * @param Mage_Customer_Model_Customer $customer
     * @param $product
     *
     * @return mixed
     */
    public function doWishlistEvent(Drip_Connect_Model_Configuration $config, $action, Mage_Customer_Model_Customer $customer, $product)
    {
        $apiCall = new Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent($config, array(
            'email' => $customer->getEmail(),
            'action' => $action,
            'properties' => array(
                'product_id' => $product->getId(),
                'categories' => Mage::helper('drip_connect')->getProductCategoryNames($product),
                'brand' => $product->getAttributeText('manufacturer'),
                'name' => $product->getName(),
                'price' => Mage::helper('drip_connect')->priceAsCents($product->getFinalPrice()),
                'currency' => Mage::app()->getStore()->getCurrentCurrencyCode(),
                'image_url' => Mage::getModel('catalog/product_media_config')->getMediaUrl($product->getThumbnail()),
                'source' => 'magento'
            ),
        ));
        return $apiCall->call();
    }
}
