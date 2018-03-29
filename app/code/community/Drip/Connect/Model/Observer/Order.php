<?php
/**
 * Actions with orders - place, change, finish..
 */

class Drip_Connect_Model_Observer_Order
{
    /**
     * @param Varien_Event_Observer $observer
     */
    public function afterOrderSave($observer)
    {
        $order = $observer->getEvent()->getOrder();
        if (!$order->getId()) {
            return;
        }
        $this->proceedOrder($order);
    }

    /**
     * drip actions on 'order placed' event
     *
     * @param Mage_Sales_Model_Order $order
     */
    protected function proceedOrder($order)
    {
        // it is possible that we've already processed this order
        if ($order->getIsAlreadyProcessed()) {
            return;
        }

        if ($order->getState() == Mage_Sales_Model_Order::STATE_NEW) {
            $response = Mage::getModel('drip_connect/ApiCalls_Helper_RecordAnEvent', array(
                'email' => $order->getCustomerEmail(),
                'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_ORDER_CREATED,
                'properties' => $this->getOrderData($order),
            ))->call();
        } else if ($order->getState() == Mage_Sales_Model_Order::STATE_COMPLETE) {
            $response = Mage::getModel('drip_connect/ApiCalls_Helper_RecordAnEvent', array(
                'email' => $order->getCustomerEmail(),
                'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_ORDER_COMPLETED,
                'properties' => $this->getOrderData($order),
            ))->call();
        }

        $order->setIsAlreadyProcessed(true);
    }

    /**
     * get order's data we want send to drip
     *
     * @param  Mage_Sales_Model_Order $order
     * @return array
     */
    protected function getOrderData($order)
    {
        $data = array(
            'source' => 'magento',
            'amount' => $order->getGrandTotal(),
            'tax' => $order->getTaxAmount(),
            'fees' => $order->getShippingAmount(),
            'discounts' => $order->getDiscountAmount(),
            'currency' => $order->getOrderCurrencyCode(),
            'items_count' => $order->getTotalQtyOrdered(),
            'order_id' => $order->getIncrementId(),
            'order_status' => $order->getState(),
            'line_items' => $this->getItemsGroups($order),
        );

        return $data;
    }

    /**
     * get order's items as groups with equal attr values
     *
     * @param  Mage_Sales_Model_Order $order
     * @return array
     */
    protected function getItemsGroups($order)
    {
        $data = array();
        foreach ($order->getAllItems() as $item) {
            $product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
            $group = array(
                'product_id' => $item->getProductId(),
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'brand' => $item->getBrand(),
                'categories' => implode(',', $product->getCategoryIds()),
                'quantity' => $item->getQtyOrdered(),
                'price' => $item->getPrice(),
                'amount' => ($item->getQtyOrdered() * $item->getPrice()),
                'tax' => $item->getTaxAmount(),
                'taxable' => (preg_match('/[123456789]/', $item->getTaxAmount()) ? 'true' : 'false'),
                'discounts' => $item->getDiscountAmount(),
                'discount_codes' => $order->getCouponCode(),
                'currency' => $order->getOrderCurrencyCode(),
                'product_url' => $item->getProduct()->getProductUrl(),
                'image_url' => (string)Mage::helper('catalog/image')->init($product, 'image'),
            );
            $data[] = $group;
        }
        return $data;
    }

}
