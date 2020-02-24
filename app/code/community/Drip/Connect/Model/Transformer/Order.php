<?php

class Drip_Connect_Model_Transformer_Order
{
    const FULFILLMENT_NO = 'not_fulfilled';
    const FULFILLMENT_PARTLY = 'partially_fulfilled';
    const FULFILLMENT_YES = 'fulfilled';

    /**
     * @var Mage_Sales_Model_Order $order
     */
    protected $order;

    /**
     * @var Drip_Connect_Model_Configuration $config
     */
    protected $config;

    /**
     * @param Mage_Sales_Model_Order $order
     * @param Drip_Connect_Model_Configuration $config
     */
    function __construct(Mage_Sales_Model_Order $order, Drip_Connect_Model_Configuration $config)
    {
        $this->order = $order;
        $this->config = $config;
    }

    /**
     * prepare array of order data
     *
     * @return array
     */
    protected function getCommonOrderData()
    {
        $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($this->order->getCustomerEmail());

        $data = array(
            'provider' => (string) Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::PROVIDER_NAME,
            'email' => (string) $this->order->getCustomerEmail(),
            'initial_status' => ($subscriber->isSubscribed() ? 'active' : 'unsubscribed'),
            'order_id' => (string) $this->order->getIncrementId(),
            'order_public_id' => (string) $this->order->getIncrementId(),
            'grand_total' => Mage::helper('drip_connect')->priceAsCents($this->order->getGrandTotal()) / 100,
            'total_discounts' => Mage::helper('drip_connect')->priceAsCents($this->order->getDiscountAmount()) / 100,
            'total_taxes' => Mage::helper('drip_connect')->priceAsCents($this->order->getTaxAmount()) / 100,
            'total_shipping' => Mage::helper('drip_connect')->priceAsCents($this->order->getShippingAmount()) / 100,
            'currency' => (string) $this->order->getOrderCurrencyCode(),
            'occurred_at' => (string) Mage::helper('drip_connect')->formatDate($this->order->getUpdatedAt()),
            'items' => $this->getOrderItemsData(),
            'billing_address' => $this->getOrderBillingData(),
            'items_count' => floatval($this->order->getTotalQtyOrdered()),
            'magento_source' => (string) Mage::helper('drip_connect')->getArea(),
        );

        $shipping = $this->getOrderShippingData();
        if ($shipping !== null) {
          $data['shipping_address'] = $shipping;
        }

        return $data;
    }

    /**
     * prepare array of order data we use to send in drip for new orders
     *
     * @return array
     */
    public function getOrderDataNew()
    {
        $data = $this->getCommonOrderData();
        $data['action'] = (string) Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::ACTION_NEW;

        return $data;
    }

    /**
     * prepare array of order data we use to send in drip for full/partly completed orders
     *
     * @return array
     */
    public function getOrderDataCompleted()
    {
        $data = $this->getCommonOrderData();
        $data['action'] = (string) Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::ACTION_FULFILL;

        return $data;
    }

    /**
     * prepare array of order data we use to send in drip for canceled orders
     *
     * @return array
     */
    public function getOrderDataCanceled()
    {
        $data = $this->getCommonOrderData();
        $data['action'] = (string) Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::ACTION_CANCEL;

        return $data;
    }

    /**
     * prepare array of order data we use to send in drip for full/partly refunded orders
     *
     * @param int $refundValue
     *
     * @return array
     */
    public function getOrderDataRefund($refundValue)
    {
        $refunds = $this->order->getCreditmemosCollection();
        $refundId = $refunds->getLastItem()->getIncrementId();

        $data = array(
            'provider' => (string) Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::PROVIDER_NAME,
            'email' => (string) $this->order->getCustomerEmail(),
            'action' => (string) Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::ACTION_REFUND,
            'order_id' => (string) $this->order->getIncrementId(),
            'order_public_id' => (string) $this->order->getIncrementId(),
            'grand_total' => Mage::helper('drip_connect')->priceAsCents($this->order->getGrandTotal()) / 100,
            'refund_amount' => $refundValue / 100,
            'occurred_at' => (string) Mage::helper('drip_connect')->formatDate($this->order->getUpdatedAt()),
        );

        return $data;
    }

    /**
     * prepare array of order data we use to send in drip for all other states
     *
     * @return array
     */
    public function getOrderDataOther()
    {
        $data = $this->getCommonOrderData();
        $data['action'] = (string) Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::ACTION_CHANGE;

        return $data;
    }

    /**
     * check fullfilment state of an order
     *
     * @return string
     */
    protected function getOrderFulfillment()
    {
        if ($this->order->getState() == Mage_Sales_Model_Order::STATE_COMPLETE) {
            return self::FULFILLMENT_YES;
        }

        foreach ($this->order->getAllItems() as $item) {
            if ($item->getStatus() == 'Shipped') {
                return self::FULFILLMENT_PARTLY;
            }
        }

        return self::FULFILLMENT_NO;
    }

    /**
     * get order's billing address data
     *
     * @return array
     */
    protected function getOrderBillingData()
    {
        $addressId = $this->order->getBillingAddressId();

        return $this->getOrderAddressData($addressId);
    }

    /**
     * get order's shipping address data
     *
     * @return array
     */
    protected function getOrderShippingData()
    {
        $addressId = $this->order->getShippingAddressId();

        return $this->getOrderAddressData($addressId);
    }

    /**
     * get address data
     *
     * @param int address id
     *
     * @return array
     */
    protected function getOrderAddressData($addressId)
    {
        if ($addressId === null) {
            return;
        }

        $address = Mage::getModel('sales/order_address')->load($addressId);

        return array(
            'first_name' => (string) $address->getFirstname(),
            'last_name' => (string) $address->getLastname(),
            'company' => (string) $address->getCompany(),
            'address_1' => (string) $address->getStreet1(),
            'address_2' => (string) $address->getStreet2(),
            'city' => (string) $address->getCity(),
            'state' => (string) $address->getRegion(),
            'postal_code' => (string) $address->getPostcode(),
            'country' => (string) $address->getCountryId(),
            'phone' => (string) $address->getTelephone(),
        );
    }

    /**
     * get order's items data
     *
     * @param  bool $isRefund
     *
     * @return array
     */
    protected function getOrderItemsData($isRefund = false)
    {
        $childItems = array();
        foreach ($this->order->getAllItems() as $item) {
            if ($item->getParentItemId() === null) {
                continue;
            }

            $childItems[$item->getParentItemId()] = $item;
        }

        $data = array();
        foreach ($this->order->getAllVisibleItems() as $item) {
            $productVariantItem = $item;
            if ($item->getProductType() === 'configurable' && array_key_exists($item->getId(), $childItems)) {
                $productVariantItem = $childItems[$item->getId()];
            }

            $group = array(
                'product_id' => (string) $item->getProductId(),
                'product_variant_id' => (string) $productVariantItem->getProductId(),
                'sku' => (string) $item->getSku(),
                'name' => (string) $item->getName(),
                'quantity' => (float) $item->getQtyOrdered(),
                'price' => Mage::helper('drip_connect')->priceAsCents($item->getPrice())/100,
                'discounts' => Mage::helper('drip_connect')->priceAsCents($item->getDiscountAmount())/100,
                'total' => Mage::helper('drip_connect')->priceAsCents(
                    (float)$item->getQtyOrdered() * (float)$item->getPrice()
                ) / 100,
                'taxes' => Mage::helper('drip_connect')->priceAsCents($item->getTaxAmount()) / 100,
            );
            if (!empty($item->getProduct()->getId())) {
                $product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
                // This causes some context-based things to behave correctly. Like URLs.
                $product->setStoreId($this->config->getStoreId());
                $productCategoryNames = Mage::helper('drip_connect')->getProductCategoryNames($product);
                $categories = explode(',', $productCategoryNames);
                if ($productCategoryNames === '' || empty($categories)) {
                    $categories = array();
                }

                $group['categories'] = $categories;
                $group['product_url'] = (string) $product->getProductUrl('');
                $group['image_url'] = (string) Mage::getModel('catalog/product_media_config')->getMediaUrl(
                    $product->getThumbnail()
                );
            }

            if ($isRefund) {
                $group['refund_amount'] = Mage::helper('drip_connect')->priceAsCents($item->getAmountRefunded());
                $group['refund_quantity'] = $item->getQtyRefunded();
            }

            $data[] = $group;
        }

        return $data;
    }

    /**
     * simple check for valid stringage
     * @param  mixed $stuff
     * @return bool
    */
    private function isNotEmpty($stuff) {
        return !empty(trim($stuff));
    }

    /**
     * check if given order can be sent to drip
     * 
     * @return bool
     */
    public function isCanBeSent()
    {
        /*for shopper activity, the following are required for minimum viability:
         * action, email -or- person_id, provider, order_id
         *   or
         * action, person_id, provider, order_id
         * 
         * person_id is never used in the plugin, so we don't need to worry about the conditional
        */
        $foundOrderId = $this->isNotEmpty((string) $this->order->getIncrementId());
        $foundProvider = $this->isNotEmpty((string) Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::PROVIDER_NAME);
        $validEmail = Mage::helper('drip_connect')->isEmailValid($this->order->getCustomerEmail());
        $foundActions = $this->isNotEmpty((string)Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::ACTION_CANCEL) &&
            $this->isNotEmpty((string)Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::ACTION_CHANGE) &&
            $this->isNotEmpty((string)Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::ACTION_FULFILL) &&
            $this->isNotEmpty((string)Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::ACTION_NEW) &&
            $this->isNotEmpty((string)Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::ACTION_REFUND);        
        return $foundOrderId && $foundProvider && $foundActions && $validEmail;
    }
}
