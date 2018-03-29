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
                'email' => $order->getCustomer()->getEmail(),
                'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_ORDER_CREATED,
                'properties' => array(
                    'source' => 'magento'
                ),
            ))->call();
        }

        $order->setIsAlreadyProcessed(true);
    }
}
