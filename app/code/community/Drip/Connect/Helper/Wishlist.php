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
        return Mage::getModel(
            'drip_connect/ApiCalls_Helper_RecordAnEvent',
            array(
                'data' => array(
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
                ),
                'store' => $customer->getStoreId(),
            )
        )->call();
    }
}
