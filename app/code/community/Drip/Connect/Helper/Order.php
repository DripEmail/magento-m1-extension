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
            'email' => $order->getCustomerEmail(),
            'provider' => Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder::PROVIDER_NAME,
            'upstream_id' => $order->getIncrementId(),
            'identifier' => $order->getIncrementId(),
            'amount' => ($order->getGrandTotal()*100),
            'tax' => ($order->getTaxAmount()*100),
            'fees' => ($order->getShippingAmount()*100),
            'discount' => ($order->getDiscountAmount()*100),
            'currency_code' => $order->getOrderCurrencyCode(),
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
            'upstream_id' => $order->getIncrementId(),
            'fulfillment_state' => $this->getOrderFulfillment($order),
            'billing_address' => $this->getOrderBillingData($order),
            'shipping_address' => $this->getOrderShippingData($order),
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
        $street = $address->getStreet();
        return array(
            'name' => $address->getName(),
            'first_name' => $address->getFirstname(),
            'last_name' => $address->getLastname(),
            'company' => $address->getCompany(),
            'address_1' => $street[0],
            'address_2' => $street[1],
            'city' => $address->getCity(),
            'state' => $address->getRegion(),
            'zip' => $address->getPostcode(),
            'country' => $address->getCountryId(),
            'phone' => $address->getTelephone(),
            'email' => $address->getEmail(),
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
            $product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
            $product->setStore(1);
            try {
                $image = (string)Mage::helper('catalog/image')->init($product, 'thumbnail')->resize(160, 160);
            } catch (Exception $e) {
                $image = '';
            }
            $group = array(
                'product_id' => $item->getProductId(),
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'quantity' => $item->getQtyOrdered(),
                'price' => ($item->getPrice()*100),
                'amount' => ($item->getQtyOrdered() * $item->getPrice() * 100),
                'tax' => ($item->getTaxAmount()*100),
                'taxable' => (preg_match('/[123456789]/', $item->getTaxAmount()) ? 'true' : 'false'),
                'discount' => ($item->getDiscountAmount()*100),
                'properties' => array(
                    'product_url' => $item->getProduct()->getProductUrl(),
                    'product_image_url' => $image,
                ),
            );
            if ($isRefund) {
                $group['refund_amount'] = ($item->getAmountRefunded()*100);
                $group['refund_quantity'] = $item->getQtyRefunded();
            }
            $data[] = $group;
        }

        return $data;
    }
}
