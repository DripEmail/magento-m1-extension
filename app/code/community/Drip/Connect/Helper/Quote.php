<?php

class Drip_Connect_Helper_Quote extends Mage_Core_Helper_Abstract
{
    const REGISTRY_KEY_IS_NEW = 'newquote';
    const REGISTRY_KEY_OLD_DATA = 'oldquotedata';

    // if/when we know the user's email, it will be saved here
    protected $email;

    /**
     * If customer registers during checkout, they will login, but quote has not been updated with customer info yet
     * so we can't fire "checkout created" on the quote b/c it's not yet assigned to the customer.  Doesn't matter
     * anyway since they've already place an order.
     *
     *
     * @param $customer
     */
    public function checkIfQuoteCreated($customer)
    {
        //gets active quote for customer, but troube is quote hasn't been updated with this customer info yet
        $quote = Mage::getModel('sales/quote')->loadByCustomer($customer);

        //check if quote has been sent to drip as new already
        if ($quote->customer_email && !$quote->getDrip()) {
            //Mage::register(self::REGISTRY_KEY_IS_NEW, true);
            $quote->setDrip(true);
            $quote->save();

            //call drip api with checkout created event
            $this->proceedQuoteNew($quote);
        } else {
            //Mage::register(self::REGISTRY_KEY_IS_NEW, false);
        }

    }


    /**
     * drip actions when send quote to drip 1st time
     *
     * @param Mage_Sales_Model_Quote $quote
     */
    public function proceedQuoteNew($quote)
    {
        Mage::getModel('drip_connect/ApiCalls_Helper_RecordAnEvent', array(
            'email' => $quote->customer_email,
            'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_QUOTE_NEW,
            'properties' => $this->prepareQuoteData($quote),
        ))->call();
    }

    /**
     * drip actions existing quote gets changed
     *
     * @param Mage_Sales_Model_Quote $quote
     */
    public function proceedQuote($quote)
    {
        Mage::getModel('drip_connect/ApiCalls_Helper_RecordAnEvent', array(
            'email' => $this->email,
            'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_QUOTE_CHANGED,
            'properties' => $this->prepareQuoteData($quote),
        ))->call();
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return array
     */
    public function prepareQuoteData($quote)
    {
        $data = array (
            'amount' => Mage::helper('drip_connect')->priceAsCents($quote->getGrandTotal()),
            'tax' => Mage::helper('drip_connect')->priceAsCents($quote->getShippingAddress()->getTaxAmount()),
            'fees' => Mage::helper('drip_connect')->priceAsCents($quote->getShippingAddress()->getShippingAmount()),
            'discounts' => Mage::helper('drip_connect')->priceAsCents((100*$quote->getSubtotal() - 100*$quote->getSubtotalWithDiscount())/100),
            'currency' => $quote->getQuoteCurrencyCode(),
            'items_count' => $quote->getItemsQty(),
            'abandoned_cart_url' => Mage::helper('checkout/cart')->getCartUrl(),
            'line_items' => $this->prepareQuoteItemsData($quote),
        );
        return $data;
    }
    /**
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return array
     */
    protected function prepareQuoteItemsData($quote)
    {
        $data = array ();
        foreach ($quote->getAllItems() as $item) {
            $product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());

            $group = array(
                'product_id' => $item->getProductId(),
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'categories' => Mage::helper('drip_connect')->getProductCategoryNames($product),
                'quantity' => $item->getQty(),
                'price' => Mage::helper('drip_connect')->priceAsCents($item->getPrice()),
                'amount' => Mage::helper('drip_connect')->priceAsCents(($item->getQty() * $item->getPrice())),
                'tax' => Mage::helper('drip_connect')->priceAsCents($item->getTaxAmount()),
                'taxable' => (preg_match('/[123456789]/', $item->getTaxAmount()) ? 'true' : 'false'),
                'discount' => Mage::helper('drip_connect')->priceAsCents($item->getDiscountAmount()),
                'currency' => $quote->getQuoteCurrencyCode(),
                'product_url' => $product->getProductUrl(),
                'image_url' => Mage::getModel('catalog/product_media_config') ->getMediaUrl($product->getThumbnail()),
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
    public function isQuoteChanged($quote)
    {
        $oldData = Mage::registry(self::REGISTRY_KEY_OLD_DATA);
        $newData = Mage::helper('drip_connect/quote')->prepareQuoteData($quote);

        return (serialize($oldData) != serialize($newData));
    }

    /**
     * check if we know the user's email (need it to track in drip)
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return bool
     */
    public function isUnknownUser($quote)
    {
        $this->email = '';

        if ($quote->getCustomerEmail()) {
            $this->email = $quote->getCustomerEmail();
        }

        return ! (bool) $this->email;
    }

}