<?php

class Drip_Connect_Model_Transformer_Product
{
    /**
     * @var Mage_Catalog_Model_Product $product
     */
    protected $product;

    /**
     * @var Drip_Connect_Model_Configuration $config
     */
    protected $config;

    /**
     * @param Mage_Sales_Model_Order $order
     * @param Drip_Connect_Model_Configuration $config
     */
    function __construct(Mage_Catalog_Model_Product $product, Drip_Connect_Model_Configuration $config)
    {
        $this->product = $product;
        $this->config = $config;
    }

    public function proceedProductNew()
    {
        $data = $this->prepareData();
        $data['action'] = Drip_Connect_Model_ApiCalls_Helper_CreateUpdateProduct::PRODUCT_NEW;
        $data['product_variant_id'] = $this->product->getId();
        $this->sendProductData($data);
    }

    public function proceedProductChanged()
    {
        $data = $this->prepareData();
        $data['action'] =  Drip_Connect_Model_ApiCalls_Helper_CreateUpdateProduct::PRODUCT_CHANGED;
        $data['product_variant_id'] = $this->product->getId();
        $this->sendProductData($data);
    }


    /**
     * drip actions when product is deleted
     *
     * @param Mage_Catalog_Model_Product $product
     */
    public function proceedProductDelete($productInfo)
    {
        $data = array(
            "provider" => (string) Drip_Connect_Model_ApiCalls_Helper_CreateUpdateProduct::PROVIDER_NAME,
            "action" => Drip_Connect_Model_ApiCalls_Helper_CreateUpdateProduct::PRODUCT_DELETED,
            "product_id" => $productInfo->product_id,
            "product_variant_id" => $productInfo->product_id,
            "sku" => $productInfo->product_sku,
            "name" => $productInfo->product_name,
            "price" => $productInfo->product_price
        );
        $this->sendProductData($data);
    }

    /** 
     * @param array
     */
    private function sendProductData($data)
    {
        $apiCall = new Drip_Connect_Model_ApiCalls_Helper_CreateUpdateProduct($this->config, $data);
        $apiCall->call();
    }

    /**
     * @param boolean
     *
     * @return array
     */
    private function prepareData()
    {
        $productVariant = $this->product;
        if ($this->product->getProductType() === 'configurable' && array_key_exists($this->product->getId(), $childItems)) {
            $productVariantItem = $childItems[$item->getId()];
        }
        
        $data = array(
            "provider" => (string) Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::PROVIDER_NAME,
            "product_id" => $this->product->getId(),
            "sku" => $this->product->getSku(),
            "name" => $this->product->getName(),
            "price" => Mage::helper('drip_connect')->priceAsCents($this->product->getFinalPrice()) / 100,
            "inventory" => (float) $this->getStockQty(),
            "product_url" => $this->product->getProductUrl(false),
            "occurred_at" => Mage::helper('drip_connect')->formatDate($this->product->getUpdatedAt())
        );
        
        if ($imageUrl = $this->getProductImageUrl()) {
            $data["image_url"] = $imageUrl;
        }

        $categories = explode(',', Mage::helper('drip_connect')->getProductCategoryNames($this->product));
        if (!empty($categories) && !empty($categories[0])) {
            $data["categories"] = $categories;
        }

        if ($brand = $this->getBrandName()) {
            $data["brand"] = $brand;
        }

        return $data;
    }

    /**
     * return product qty
     * note: if product comes frome collection, the collection should has stock flag
     *       ->setFlag('require_stock_items', true);
     *
     * @return float
     */
    private function getStockQty()
    {
        try {
            return (float) $this->product->getStockItem()->getQty();
        } catch (\Exception $e) {
        }

        try {
            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($this->product);
            return (float) $stock->getQty();
        } catch (\Exception $e) {
        }

        return (float) 0;
    }

    /**
     *
     * @return string
     */
    private function getProductImageUrl()
    {
        $imageUrl = '';
        if ($this->product->getThumbnail() != 'no_selection') {
            $imageUrl = Mage::getModel('catalog/product_media_config')->getMediaUrl($this->product->getThumbnail());
        }

        return $imageUrl;
    }

    /**
     * return brand name for the given product
     *
     * @param Magento_Catalog_Model_Product $product
     *
     * @return string
     */
    private function getBrandName($product)
    {
        try {
            return $this->product->getAttributeText('manufacturer');
        } catch (\Exception $e) {
        }

        return '';
    }
}
