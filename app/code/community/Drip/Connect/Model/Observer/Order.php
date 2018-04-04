<?php
/**
 * Actions with orders - place, change, finish..
 */

class Drip_Connect_Model_Observer_Order
{
    const REGISTRY_KEY_OLD_DATA = 'orderoldvalues';

    /**
     * store some current params we may need to compare with themselves later
     *
     * @param Varien_Event_Observer $observer
     */
    public function beforeOrderSave($observer)
    {
        $order = $observer->getEvent()->getOrder();
        if (!$order->getId()) {
            return;
        }
        $data = array(
            'total_refunded' => $order->getOrigData('total_refunded'),
            'state' => $order->getOrigData('state'),
        );
        Mage::register(self::REGISTRY_KEY_OLD_DATA, $data);
    }

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
        Mage::unregister(self::REGISTRY_KEY_OLD_DATA);
    }

    /**
     * drip actions on order state events
     *
     * @param Mage_Sales_Model_Order $order
     */
    protected function proceedOrder($order)
    {
        // it is possible that we've already processed this order
        if ($order->getIsAlreadyProcessed()) {
            return;
        }

        switch ($order->getState()) {
            case Mage_Sales_Model_Order::STATE_NEW :
                if ($this->isSameState($order)) {
                    break;
                }
                // new order
                $response = Mage::getModel('drip_connect/ApiCalls_Helper_RecordAnEvent', array(
                    'email' => $order->getCustomerEmail(),
                    'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_ORDER_CREATED,
                    'properties' => $this->getOrderData($order),
                ))->call();
                break;
            case Mage_Sales_Model_Order::STATE_COMPLETE :
                if ($this->checkIsRefund($order)) {
                    // partial refund of completed order
                    $response = Mage::getModel('drip_connect/ApiCalls_Helper_RecordAnEvent', array(
                        'email' => $order->getCustomerEmail(),
                        'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_ORDER_REFUNDED,
                        'properties' => $this->getOrderData($order, true),
                    ))->call();
                } else {
                    if ($this->isSameState($order)) {
                        break;
                    }
                    // complete order
                    $response = Mage::getModel('drip_connect/ApiCalls_Helper_RecordAnEvent', array(
                        'email' => $order->getCustomerEmail(),
                        'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_ORDER_COMPLETED,
                        'properties' => $this->getOrderData($order),
                    ))->call();
                }
                break;
            case Mage_Sales_Model_Order::STATE_CLOSED :
                if ($this->isSameState($order)) {
                    break;
                }
                // full refund
                $response = Mage::getModel('drip_connect/ApiCalls_Helper_RecordAnEvent', array(
                    'email' => $order->getCustomerEmail(),
                    'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_ORDER_REFUNDED,
                    'properties' => $this->getOrderData($order, true),
                ))->call();
                break;
            case Mage_Sales_Model_Order::STATE_PROCESSING :
                if ($this->checkIsRefund($order)) {
                    // partial refund of processing order
                    $response = Mage::getModel('drip_connect/ApiCalls_Helper_RecordAnEvent', array(
                        'email' => $order->getCustomerEmail(),
                        'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_ORDER_REFUNDED,
                        'properties' => $this->getOrderData($order, true),
                    ))->call();
                }
                break;
            case Mage_Sales_Model_Order::STATE_CANCELED :
                if ($this->isSameState($order)) {
                    break;
                }
                // cancel order
                $response = Mage::getModel('drip_connect/ApiCalls_Helper_RecordAnEvent', array(
                    'email' => $order->getCustomerEmail(),
                    'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_ORDER_CANCELED,
                    'properties' => $this->getOrderData($order),
                ))->call();
                break;
        }

        $order->setIsAlreadyProcessed(true);
    }

    /**
     * get order's data we want send to drip
     *
     * @param  Mage_Sales_Model_Order $order
     * @param  bool $isRefund
     * @return array
     */
    protected function getOrderData($order, $isRefund = false)
    {
        $data = array(
            'source' => 'magento',
            'amount' => ($isRefund ? $order->getTotalRefunded() : $order->getGrandTotal()),
            'tax' => $order->getTaxAmount(),
            'fees' => $order->getShippingAmount(),
            'discounts' => $order->getDiscountAmount(),
            'currency' => $order->getOrderCurrencyCode(),
            'items_count' => $order->getTotalQtyOrdered(),
            'order_id' => $order->getIncrementId(),
            'order_status' => $order->getState(),
            'line_items' => $this->getItemsGroups($order, $isRefund),
        );

        return $data;
    }

    /**
     * get order's items as groups with equal attr values
     *
     * @param  Mage_Sales_Model_Order $order
     * @param  bool $isRefund
     * @return array
     */
    protected function getItemsGroups($order, $isRefund = false)
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
            if ($isRefund) {
                $group['refund_amount'] = $item->getAmountRefunded();
                $group['refund_quantity'] = $item->getQtyRefunded();
            }
            $data[] = $group;
        }
        return $data;
    }

    /**
     * check if order get changed with refund action
     *
     * @param  Mage_Sales_Model_Order $order
     *
     * @return bool
     */
    protected function checkIsRefund($order)
    {
        $oldData = Mage::registry(self::REGISTRY_KEY_OLD_DATA);
        $oldValue = trim($oldData['total_refunded'], "0");
        $newValue = trim($order->getTotalRefunded(), "0");

        return ($oldValue != $newValue);
    }

    /**
     * check if order state has not been changed
     */
    protected function isSameState($order)
    {
        $oldData = Mage::registry(self::REGISTRY_KEY_OLD_DATA);
        $oldValue = $oldData['state'];
        $newValue = $order->getState();

        return ($oldValue == $newValue);
    }
}
