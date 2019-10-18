<?php

class Drip_Connect_Model_Observer_Order_BeforeSave extends Drip_Connect_Model_Observer_Base
{
    /**
     * store some current params we may need to compare with themselves later
     *
     * @param Varien_Event_Observer $observer
     */
    protected function executeWhenEnabled($observer)
    {
        $order = $observer->getEvent()->getOrder();
        if (!$order->getId()) {
            return;
        }

        $data = array(
            'total_refunded' => $order->getOrigData('total_refunded'),
            'state' => $order->getOrigData('state'),
        );
        Mage::unregister(self::REGISTRY_KEY_ORDER_OLD_DATA);
        Mage::register(self::REGISTRY_KEY_ORDER_OLD_DATA, $data);
    }
}
