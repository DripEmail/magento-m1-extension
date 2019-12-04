<?php

class Drip_Connect_Helper_Quote extends Mage_Core_Helper_Abstract
{
    const REGISTRY_KEY_IS_NEW = 'newquote';
    const REGISTRY_KEY_OLD_DATA = 'oldquotedata';
    const REGISTRY_KEY_CUSTOMER_REGISTERED_OR_LOGGED_IN_WITH_EMTPY_QUOTE = 'customercreatedemptycart';

    // if/when we know the user's email, it will be saved here
    protected $_email;

    /**
     * If customer registers during checkout, they will login, but quote has not been updated with customer info yet
     * so we can't fire "checkout created" on the quote b/c it's not yet assigned to the customer.  Doesn't matter
     * anyway since they've already place an order.
     *
     * When customer logs in or registers, magento creates an empty quote right away.  We don't want to call
     * checkout created on this action, so we check the quote total to avoid firing any quote related events.
     *
     * @param Mage_Customer_Model_Customer $customer
     */
    public function checkForEmptyQuoteCustomer(Mage_Customer_Model_Customer $customer)
    {
        //gets active quote for customer, but troube is quote hasn't been updated with this customer info yet
        $quote = Mage::getModel('sales/quote')->loadByCustomer($customer);
        $this->checkForEmptyQuote($quote);
    }

    /**
     * Check whether quote has no value.
     *
     * @param Mage_Sales_Model_Quote $quote
     */
    public function checkForEmptyQuote(Mage_Sales_Model_Quote $quote)
    {
        if (Mage::helper('drip_connect')->priceAsCents($quote->getGrandTotal()) == 0) {
            $this->setEmptyQuoteFlag(true);
        }
    }

    /**
     * @param bool $state
     */
    public function setEmptyQuoteFlag($state)
    {
        Mage::unregister(self::REGISTRY_KEY_CUSTOMER_REGISTERED_OR_LOGGED_IN_WITH_EMTPY_QUOTE);
        Mage::register(self::REGISTRY_KEY_CUSTOMER_REGISTERED_OR_LOGGED_IN_WITH_EMTPY_QUOTE, $state);
    }

    /**
     * drip actions when send quote to drip 1st time
     *
     * @param Drip_Connect_Model_Configuration $config
     * @param Mage_Sales_Model_Quote $quote
     */
    public function proceedQuoteNew(Drip_Connect_Model_Configuration $config, Mage_Sales_Model_Quote $quote)
    {
        $data = $this->prepareQuoteData($quote);
        $data['action'] = Drip_Connect_Model_ApiCalls_Helper_CreateUpdateQuote::QUOTE_NEW;
        $data['occurred_at'] = (string) Mage::helper('drip_connect')->formatDate($quote->getUpdatedAt());
        if (!empty($data['items'])) {
            $apiCall = new Drip_Connect_Model_ApiCalls_Helper_CreateUpdateQuote($config, $data);
            $apiCall->call();
        }
    }

    /**
     * drip actions existing quote gets changed
     *
     * @param Drip_Connect_Model_Configuration $config
     * @param Mage_Sales_Model_Quote $quote
     */
    public function proceedQuote(Drip_Connect_Model_Configuration $config, Mage_Sales_Model_Quote $quote)
    {
        $data = $this->prepareQuoteData($quote);
        $data['action'] = Drip_Connect_Model_ApiCalls_Helper_CreateUpdateQuote::QUOTE_CHANGED;
        $data['occurred_at'] = (string) Mage::helper('drip_connect')->formatDate($quote->getUpdatedAt());
        $apiCall = new Drip_Connect_Model_ApiCalls_Helper_CreateUpdateQuote($config, $data);
        $apiCall->call();
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return array
     */
    public function prepareQuoteData(Mage_Sales_Model_Quote $quote)
    {
        $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($this->_email);

        $data = array (
            "provider" => Drip_Connect_Model_ApiCalls_Helper_CreateUpdateQuote::PROVIDER_NAME,
            "email" => $this->_email,
            "initial_status" => ($subscriber->isSubscribed() ? 'active' : 'unsubscribed'),
            "cart_id" => $quote->getId(),
            "grand_total" => Mage::helper('drip_connect')->priceAsCents($quote->getGrandTotal())/100,
            "total_discounts" => Mage::helper('drip_connect')->priceAsCents(
                (float)$quote->getSubtotal() - (float)$quote->getSubtotalWithDiscount()
            ) / 100,
            "currency" => $quote->getQuoteCurrencyCode(),
            "cart_url" => Mage::helper('drip_connect')->getAbandonedCartUrl($quote),
            'items' => $this->prepareQuoteItemsData($quote),
            'items_count' => floatval($quote->getItemsQty()),
            'magento_source' => Mage::helper('drip_connect')->getArea(),
        );

        return $data;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return array
     */
    protected function prepareQuoteItemsData(Mage_Sales_Model_Quote $quote)
    {
        $childItems = array();
        foreach ($quote->getAllItems() as $item) {
            if ($item->getParentItemId() === null) {
                continue;
            }

            $childItems[$item->getParentItemId()] = $item;
        }

        $data = array();
        foreach ($quote->getAllVisibleItems() as $item) {
            $product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());

            $productCategoryNames = Mage::helper('drip_connect')->getProductCategoryNames($product);
            $categories = explode(',', $productCategoryNames);
            if ($productCategoryNames === '' || empty($categories)) {
                $categories = array();
            }

            $productVariantItem = $item;
            $productVariantProduct = $product;
            if ($item->getProductType() === 'configurable' && $childItems[$item->getId()]) {
                $productVariantItem = $childItems[$item->getId()];
                $productVariantProduct = Mage::getModel('catalog/product')->load($productVariantItem->getProduct()->getId());
            }

            $productImage = $productVariantProduct->getImage();

            if (empty($productImage) || $productVariantProduct->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) {
              $productImage = $product->getImage();
            }

            if (!empty($productImage)) {
              $productImage = Mage::getModel('catalog/product_media_config')->getMediaUrl($productImage);
            }

            $group = array(
                'product_id' => $item->getProductId(),
                'product_variant_id' => $productVariantItem->getProductId(),
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'categories' => $categories,
                'quantity' => $item->getQty(),
                'price' => Mage::helper('drip_connect')->priceAsCents($item->getPrice())/100,
                'discounts' => Mage::helper('drip_connect')->priceAsCents($item->getDiscountAmount())/100,
                'total' => Mage::helper('drip_connect')->priceAsCents(
                    (float)$item->getQty() * (float)$item->getPrice()
                ) / 100,
                'product_url' => $product->getProductUrl(),
                'image_url' => $productImage,
            );

            $data[] = $group;
        }

        return $data;
    }


    /**
     * compare orig and new data
     * Data types of data must match or there will be a difference
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return bool
     */
    public function isQuoteChanged(Mage_Sales_Model_Quote $quote)
    {
        $oldData = Mage::registry(self::REGISTRY_KEY_OLD_DATA);
        $newData = Mage::helper('drip_connect/quote')->prepareQuoteData($quote);

        return (Mage::helper('core')->jsonEncode($oldData) != Mage::helper('core')->jsonEncode($newData));
    }

    /**
     * check if we know the user's email (need it to track in drip)
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return bool
     */
    public function isUnknownUser(Mage_Sales_Model_Quote $quote)
    {
        $this->_email = '';

        if ($quote->getCustomerEmail()) {
            $this->_email = $quote->getCustomerEmail();
        }

        return ! (bool) $this->_email;
    }

    /**
     * @param Mage_Sales_Model_Quote $oldQuote
     */
    public function recreateCartFromQuote(Mage_Sales_Model_Quote $oldQuote)
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $checkoutSession = Mage::getSingleton('checkout/session');
        $quote->removeAllItems();
        $quote->merge($oldQuote);
        $quote->collectTotals()->save();
        $checkoutSession->setQuoteId($quote->getId());
    }

    protected function getLogger()
    {
        return Mage::helper('drip_connect/logger')->logger();
    }
}
