<?php

class Drip_Connect_Helper_Wishlist extends Mage_Core_Helper_Abstract
{
    /**
     * @param $action
     * @param $customer
     * @param $product
     *
     * @return mixed
     */
    public function doWishlistEvent($action, $customer, $product)
    {
        // TODO: Pass this in instead of generating it here.
        $config = new Drip_Connect_Model_Configuration($customer->getStoreId());
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
