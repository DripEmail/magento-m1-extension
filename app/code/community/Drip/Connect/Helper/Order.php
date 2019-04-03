<?php

class Drip_Connect_Helper_Order extends Mage_Core_Helper_Abstract
{
    const FULFILLMENT_NO = 'not_fulfilled';
    const FULFILLMENT_PARTLY = 'partially_fulfilled';
    const FULFILLMENT_YES = 'fulfilled';

    /**
     * prepare array of order data we use to send in drip for new orders
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    public function getOrderDataNew($order)
    {
        $data = array(
            'provider' => Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::PROVIDER_NAME,
            'email' => $order->getCustomerEmail(),
            'action' => Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::ACTION_NEW,
            'order_id' => $order->getIncrementId(),
            'order_public_id' => $order->getIncrementId(),
            'grand_total' => Mage::helper('drip_connect')->priceAsCents($order->getGrandTotal()) / 100,
            'total_discounts' => Mage::helper('drip_connect')->priceAsCents($order->getDiscountAmount()) / 100,
            'total_taxes' => Mage::helper('drip_connect')->priceAsCents($order->getTaxAmount()) / 100,
            'total_shipping' => Mage::helper('drip_connect')->priceAsCents($order->getShippingAmount()) / 100,
            'currency' => $order->getOrderCurrencyCode(),
            'occurred_at' => Mage::helper('drip_connect')->formatDate($order->getUpdatedAt()),

            'items' => $this->getOrderItemsData($order),

            'billing_address' => $this->getOrderBillingData($order),
            'shipping_address' => $this->getOrderShippingData($order),

            'properties' => array(
                'magento_source' => Mage::helper('drip_connect')->getArea(),
            ),
        );

        return $data;
    }

    /**
     * prepare array of order data we use to send in drip for full/partly completed orders
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    public function getOrderDataCompleted($order)
    {
        $data = array(
            'provider' => Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::PROVIDER_NAME,
            'email' => $order->getCustomerEmail(),
            'action' => Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::ACTION_FULFILL,
            'grand_total' => Mage::helper('drip_connect')->priceAsCents($order->getGrandTotal()) / 100,
            'order_id' => $order->getIncrementId(),
            'order_public_id' => $order->getIncrementId(),
            'occurred_at' => Mage::helper('drip_connect')->formatDate($order->getUpdatedAt()),
            'billing_address' => $this->getOrderBillingData($order),
            'shipping_address' => $this->getOrderShippingData($order),
            'properties' => array(
                'fulfillment_state' => $this->getOrderFulfillment($order),
            ),
        );

        return $data;
    }

    /**
     * prepare array of order data we use to send in drip for canceled orders
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    public function getOrderDataCanceled($order)
    {
        $data = array(
            'provider' => Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::PROVIDER_NAME,
            'email' => $order->getCustomerEmail(),
            'action' => Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::ACTION_CANCEL,
            'order_id' => $order->getIncrementId(),
            'order_public_id' => $order->getIncrementId(),
            'occurred_at' => Mage::helper('drip_connect')->formatDate($order->getUpdatedAt()),
        );

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
            'provider' => Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::PROVIDER_NAME,
            'email' => $order->getCustomerEmail(),
            'action' => Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::ACTION_REFUND,
            'order_id' => $order->getIncrementId(),
            'order_public_id' => $order->getIncrementId(),
            'refund_amount' => $refundValue / 100,
            'occurred_at' => Mage::helper('drip_connect')->formatDate($order->getUpdatedAt()),
        );

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
     * @param  bool $isRefund
     *
     * @return array
     */
    protected function getOrderItemsData($order, $isRefund = false)
    {
        $data = array();
        foreach ($order->getAllItems() as $item) {
            $group = array(
                'product_id' => $item->getProductId(),
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'quantity' => (float) $item->getQtyOrdered(),
                'price' => Mage::helper('drip_connect')->priceAsCents($item->getPrice())/100,
                'discounts' => Mage::helper('drip_connect')->priceAsCents($item->getDiscountAmount())/100,
                'total' => Mage::helper('drip_connect')->priceAsCents((float)$item->getQtyOrdered() * (float)$item->getPrice()) / 100,
                'taxes' => Mage::helper('drip_connect')->priceAsCents($item->getTaxAmount()) / 100,
            );
            if (!empty($item->getProduct()->getId())) {
                $product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
                $categories = explode(',', Mage::helper('drip_connect')->getProductCategoryNames($product));
                if (empty($categories)) {
                    $categories = [];
                }
                $group['categories'] = $categories;
                $group['product_url'] = $item->getProduct()->getProductUrl();
                $group['image_url'] = Mage::getModel('catalog/product_media_config') ->getMediaUrl($product->getThumbnail());
            }
            if ($isRefund) {
                $group['refund_amount'] = Mage::helper('drip_connect')->priceAsCents($item->getAmountRefunded());
                $group['refund_quantity'] = $item->getQtyRefunded();
            }
            $data[] = $group;
        }

        return $data;
    }
}
