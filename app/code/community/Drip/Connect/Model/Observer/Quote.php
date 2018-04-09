<?php
/**
 * Actions with quote
 */

class Drip_Connect_Model_Observer_Quote
{
    const REGISTRY_KEY_IS_NEW = 'newquote';
    const REGISTRY_KEY_OLD_DATA = 'oldquotedata';

    // if/when we know the user's email, it will be saved here
    protected $email;

    /**
     * @param Varien_Event_Observer $observer
     */
    public function beforeQuoteSaved($observer)
    {
        if (!Mage::helper('drip_connect')->isModuleActive()) {
            return;
        }

        $quote = $observer->getEvent()->getQuote();

        if ($this->isUnknownUser($quote)) {
            return;
        }

        if (!$quote->isObjectNew()) {
            $orig = Mage::getModel('sales/quote')->load($quote->getId());
            $data = $this->prepareQuoteData($orig);
            Mage::register(self::REGISTRY_KEY_OLD_DATA, $data);
        }

        if (!$quote->getDrip()) {
            Mage::register(self::REGISTRY_KEY_IS_NEW, true);
            $quote->setDrip(true);
        } else {
            Mage::register(self::REGISTRY_KEY_IS_NEW, false);
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function afterQuoteSaved($observer)
    {
        if (!Mage::helper('drip_connect')->isModuleActive()) {
            return;
        }

        $quote = $observer->getEvent()->getQuote();

        if ($this->isUnknownUser($quote)) {
            return;
        }

        if (Mage::registry(self::REGISTRY_KEY_IS_NEW)) {
            $this->proceedQuoteNew($quote);
        } else {
            if ($this->isQuoteChanged($quote)) {
                $this->proceedQuote($quote);
            }
        }
        Mage::unregister(self::REGISTRY_KEY_IS_NEW);
        Mage::unregister(self::REGISTRY_KEY_OLD_DATA);
    }

    /**
     * drip actions when send quote to drip 1st time
     *
     * @param Mage_Sales_Model_Quote $quote
     */
    protected function proceedQuoteNew($quote)
    {
        Mage::getModel('drip_connect/ApiCalls_Helper_RecordAnEvent', array(
            'email' => $this->email,
            'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_QUOTE_NEW,
            'properties' => $this->prepareQuoteData($quote),
        ))->call();
    }

    /**
     * drip actions existing quote gets changed
     *
     * @param Mage_Sales_Model_Quote $quote
     */
    protected function proceedQuote($quote)
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
    protected function prepareQuoteData($quote)
    {
        $data = array (
            'amount' => Mage::helper('drip_connect')->formatPriceCents($quote->getGrandTotal()),
            'tax' => Mage::helper('drip_connect')->formatPriceCents($quote->getShippingAddress()->getTaxAmount()),
            'fees' => Mage::helper('drip_connect')->formatPriceCents($quote->getShippingAddress()->getShippingAmount()),
            'discounts' => Mage::helper('drip_connect')->formatPriceCents((100*$quote->getSubtotal() - 100*$quote->getSubtotalWithDiscount())/100),
            'currency' => $quote->getQuoteCurrencyCode(),
            'items_count' => count($quote->getAllItems()),
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
            try {
                $image = (string)Mage::helper('catalog/image')->init($product, 'thumbnail')->resize(160, 160);
            } catch (Exception $e) {
                $image = '';
            }

            $group = array(
                'product_id' => $item->getProductId(),
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'categories' => implode(',', $product->getCategoryIds()),
                'quantity' => $item->getQty(),
                'price' => Mage::helper('drip_connect')->formatPriceCents($item->getPrice()),
                'amount' => Mage::helper('drip_connect')->formatPriceCents(($item->getQty() * $item->getPrice())),
                'tax' => Mage::helper('drip_connect')->formatPriceCents($item->getTaxAmount()),
                'taxable' => (preg_match('/[123456789]/', $item->getTaxAmount()) ? 'true' : 'false'),
                'discount' => Mage::helper('drip_connect')->formatPriceCents($item->getDiscountAmount()),
                'currency' => $quote->getQuoteCurrencyCode(),
                'product_url' => $item->getProduct()->getProductUrl(),
                'image_url' => $image,
            );
            $data[] = $group;
        }

        return $data;
    }

    /**
     * compare orig and new data
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return bool
     */
    protected function isQuoteChanged($quote)
    {
        $oldData = Mage::registry(self::REGISTRY_KEY_OLD_DATA);
        $newData = $this->prepareQuoteData($quote);

        return (serialize($oldData) != serialize($newData));
    }

    /**
     * check if we know the user's email (need it to track in drip)
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return bool
     */
    protected function isUnknownUser($quote)
    {
        $this->email = '';

        if ($quote->getCustomerEmail()) {
            $this->email = $quote->getCustomerEmail();
        }

        return ! (bool) $this->email;
    }
}
