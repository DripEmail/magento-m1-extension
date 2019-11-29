<?php

class Drip_Connect_Helper_Order extends Mage_Core_Helper_Abstract
{
    const FULFILLMENT_NO = 'not_fulfilled';
    const FULFILLMENT_PARTLY = 'partially_fulfilled';
    const FULFILLMENT_YES = 'fulfilled';

    /**
     * prepare array of order data
     *
     * @param Mage_Sales_Model_Order $order
     * @param Drip_Connect_Model_Configuration $config
     *
     * @return array
     */
    protected function getCommonOrderData(Mage_Sales_Model_Order $order, Drip_Connect_Model_Configuration $config)
    {
        $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($order->getCustomerEmail());

        $data = array(
            'provider' => (string) Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::PROVIDER_NAME,
            'email' => (string) $order->getCustomerEmail(),
            'initial_status' => ($subscriber->isSubscribed() ? 'active' : 'unsubscribed'),
            'order_id' => (string) $order->getIncrementId(),
            'order_public_id' => (string) $order->getIncrementId(),
            'grand_total' => Mage::helper('drip_connect')->priceAsCents($order->getGrandTotal()) / 100,
            'total_discounts' => Mage::helper('drip_connect')->priceAsCents($order->getDiscountAmount()) / 100,
            'total_taxes' => Mage::helper('drip_connect')->priceAsCents($order->getTaxAmount()) / 100,
            'total_shipping' => Mage::helper('drip_connect')->priceAsCents($order->getShippingAmount()) / 100,
            'currency' => (string) $order->getOrderCurrencyCode(),
            'occurred_at' => (string) Mage::helper('drip_connect')->formatDate($order->getUpdatedAt()),
            'items' => $this->getOrderItemsData($order, $config),
            'billing_address' => $this->getOrderBillingData($order),
            'shipping_address' => $this->getOrderShippingData($order),
            'items_count' => floatval($order->getTotalQtyOrdered()),
            'magento_source' => (string) Mage::helper('drip_connect')->getArea(),
        );

        return $data;
    }

    /**
     * prepare array of order data we use to send in drip for new orders
     *
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Sales_Model_Order $config
     *
     * @return array
     */
    public function getOrderDataNew(Mage_Sales_Model_Order $order, Drip_Connect_Model_Configuration $config)
    {
        $data = $this->getCommonOrderData($order, $config);
        $data['action'] = (string) Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::ACTION_NEW;

        return $data;
    }

    /**
     * prepare array of order data we use to send in drip for full/partly completed orders
     *
     * @param Mage_Sales_Model_Order $order
     * @param Drip_Connect_Model_Configuration $config
     *
     * @return array
     */
    public function getOrderDataCompleted(Mage_Sales_Model_Order $order, Drip_Connect_Model_Configuration $config)
    {
        $data = $this->getCommonOrderData($order, $config);
        $data['action'] = (string) Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::ACTION_FULFILL;

        return $data;
    }

    /**
     * prepare array of order data we use to send in drip for canceled orders
     *
     * @param Mage_Sales_Model_Order $order
     * @param Drip_Connect_Model_Configuration $config
     *
     * @return array
     */
    public function getOrderDataCanceled(Mage_Sales_Model_Order $order, Drip_Connect_Model_Configuration $config)
    {
        $data = $this->getCommonOrderData($order, $config);
        $data['action'] = (string) Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::ACTION_CANCEL;

        return $data;
    }

    /**
     * prepare array of order data we use to send in drip for full/partly refunded orders
     *
     * @param Mage_Sales_Model_Order $order
     * @param int $refundValue
     *
     * @return array
     */
    public function getOrderDataRefund($order, $refundValue)
    {
        $refunds = $order->getCreditmemosCollection();
        $refundId = $refunds->getLastItem()->getIncrementId();

        $data = array(
            'provider' => (string) Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::PROVIDER_NAME,
            'email' => (string) $order->getCustomerEmail(),
            'action' => (string) Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::ACTION_REFUND,
            'order_id' => (string) $order->getIncrementId(),
            'order_public_id' => (string) $order->getIncrementId(),
            'grand_total' => Mage::helper('drip_connect')->priceAsCents($order->getGrandTotal()) / 100,
            'refund_amount' => $refundValue / 100,
            'occurred_at' => (string) Mage::helper('drip_connect')->formatDate($order->getUpdatedAt()),
        );

        return $data;
    }

    /**
     * prepare array of order data we use to send in drip for all other states
     *
     * @param Mage_Sales_Model_Order $order
     * @param Drip_Connect_Model_Configuration $config
     *
     * @return array
     */
    public function getOrderDataOther(Mage_Sales_Model_Order $order, Drip_Connect_Model_Configuration $config)
    {
        $data = $this->getCommonOrderData($order);
        $data['action'] = (string) Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::ACTION_CHANGE;

        return $data;
    }

    /**
     * check fullfilment state of an order
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return string
     */
    protected function getOrderFulfillment($order)
    {
        if ($order->getState() == Mage_Sales_Model_Order::STATE_COMPLETE) {
            return self::FULFILLMENT_YES;
        }

        foreach ($order->getAllItems() as $item) {
            if ($item->getStatus() == 'Shipped') {
                return self::FULFILLMENT_PARTLY;
            }
        }

        return self::FULFILLMENT_NO;
    }

    /**
     * get order's billing address data
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    protected function getOrderBillingData($order)
    {
        $addressId = $order->getBillingAddressId();

        return $this->getOrderAddressData($addressId);
    }

    /**
     * get order's shipping address data
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    protected function getOrderShippingData($order)
    {
        $addressId = $order->getShippingAddressId();

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
     * @param Mage_Sales_Model_Order $order
     * @param Drip_Connect_Model_Configuration $config
     * @param  bool $isRefund
     *
     * @return array
     */
    protected function getOrderItemsData(Mage_Sales_Model_Order $order, Drip_Connect_Model_Configuration $config, $isRefund = false)
    {
        $childItems = array();
        foreach ($order->getAllItems() as $item) {
            if ($item->getParentItemId() === null) {
                continue;
            }

            $childItems[$item->getParentItemId()] = $item;
        }

        $data = array();
        foreach ($order->getAllVisibleItems() as $item) {
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
                $product->setStoreId($config->getStoreId());
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
     * check if given order can be sent to drip
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return bool
     */
    public function isCanBeSent($order)
    {
        return Mage::helper('drip_connect')->isEmailValid($order->getCustomerEmail());
    }
}
