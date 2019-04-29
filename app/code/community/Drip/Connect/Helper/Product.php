<?php

class Drip_Connect_Helper_Product extends Mage_Core_Helper_Abstract
{
    const REGISTRY_KEY_IS_NEW = 'newproduct';
    const REGISTRY_KEY_OLD_DATA = 'oldproductdata';
    const SUCCESS_RESPONSE_CODE = 202;

    /**
     * @param Mage_Catalog_Model_Product
     *
     * @return array
     */
    public function prepareData($product)
    {
        $categories = explode(',', Mage::helper('drip_connect')->getProductCategoryNames($product));
        $data = array (
            "provider" => Drip_Connect_Model_ApiCalls_Helper_CreateUpdateProduct::PROVIDER_NAME,
            "occurred_at" => Mage::helper('drip_connect')->formatDate($product->getUpdatedAt()),
            "product_id" => $product->getId(),
            "sku" => $product->getSku(),
            "name" => $product->getName(),
            "price" => Mage::helper('drip_connect')->priceAsCents($product->getFinalPrice())/100,
            "inventory" => (float) $this->getStockQty($product),
            "product_url" => $this->getProductUrl($product),
            "image_url" => $this->getProductImageUrl($product),
        );
        if (count($categories) && !empty($categories[0])) {
            $data["categories"] = $categories;
        }
        if ($brand = $this->getBrandName($product)) {
            $data["brand"] = $brand;
        }

        return $data;
    }

    /**
     * drip actions when send product to drip 1st time
     *
     * @param Mage_Catalog_Model_Product $product
     */
    public function proceedProductNew($product)
    {
        $data = $this->prepareData($product);
        $data['action'] = Drip_Connect_Model_ApiCalls_Helper_CreateUpdateProduct::PRODUCT_NEW;
        //Mage::getModel('drip_connect/ApiCalls_Helper_CreateUpdateProduct', $data)->call();
    }

    /**
     * drip actions product getc changed
     *
     * @param Mage_Catalog_Model_Product $product
     */
    public function proceedProduct($product)
    {
        $data = $this->prepareData($product);
        $data['action'] = Drip_Connect_Model_ApiCalls_Helper_CreateUpdateProduct::PRODUCT_CHANGED;
        //Mage::getModel('drip_connect/ApiCalls_Helper_CreateUpdateProduct', $data)->call();
    }

    /**
     * return brand name for the given product
     *
     * @param Magento_Catalog_Model_Product $product
     *
     * @return string
     */
    public function getBrandName($product)
    {
        try {
            $brandName = $product->getAttributeText('manufacturer');
        } catch (\Exception $e) {
            // attribute does not exist
            $brandName = '';
        }

        return $brandName;
    }

    /**
     * return product qty
     * note: if product comes frome collection, the collection should has stock flag
     *       ->setFlag('require_stock_items', true);
     *
     * @param Magento_Catalog_Model_Product $product
     *
     * @return float
     */
    public function getStockQty($product)
    {
        try {
            $qty = (float) $product->getStockItem()->getQty();
        } catch (\Throwable $e) {
            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
            $qty = (float) $stock->getQty();
        }

        return $qty;
    }

    /**
     * @param Magento_Catalog_Model_Product $product
     *
     * @return string
     */
    public function getProductUrl($product)
    {
        $needRevert = false;
        $defaultStoreCode = false;

        if (empty($product->getStoreId())) {
            // if editing is for default scope,
            // temporarily set default store's id to get proper url
            $websites = Mage::getModel('core/website')
                            ->getCollection()
                            ->addFieldToFilter('is_default', 1);
            foreach ($websites as $website) {
                $defaultStoreId = $website->getDefaultStore()->getId();
                $defaultStoreCode = $website->getDefaultStore()->getCode();
                $product->setStoreId($defaultStoreId);
                $needRevert = true;
            }
        }
        $url = $product->getProductUrl(false);

        if ($defaultStoreCode) {
            $url = preg_replace('|\?___store='.$defaultStoreCode.'|', '', $url);
        }

        if ($needRevert) {
            // revert id back to admin's store
            $product->setStoreId(0);
        }

        return $url;
    }

    /**
     * @param Magento_Catalog_Model_Product $product
     *
     * @return string
     */
    public function getProductImageUrl($product)
    {
        return Mage::getModel('catalog/product_media_config')->getMediaUrl($product->getThumbnail());
    }
}
