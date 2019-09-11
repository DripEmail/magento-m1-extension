<?php

class Drip_Connect_Model_Observer_Order_AfterSave extends Drip_Connect_Model_Observer_Base
{
    /**
     * @param Varien_Event_Observer $observer
     */
    public function executeWhenEnabled($observer)
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

        if (! Mage::helper('drip_connect/order')->isCanBeSent($order)) {
            return;
        }

        if ($this->isOrderNew($order)) {
            if ($this->isSameState($order)) {
                return;
            }

            // if guest checkout and there is no such user and there is no such subscriber
            // create subscriber record
            if ($order->getCustomerIsGuest()
                && ! Mage::helper('drip_connect')->isCustomerExists($order->getCustomerEmail())
                && ! Mage::helper('drip_connect')->isSubscriberExists($order->getCustomerEmail())
            ) {
                $customerData = Mage::helper('drip_connect')->prepareCustomerDataForGuestCheckout($order);
                Mage::getModel('drip_connect/ApiCalls_Helper_CreateUpdateSubscriber', $customerData)->call();
            }

            // new order
            $response = Mage::getModel(
                'drip_connect/ApiCalls_Helper_CreateUpdateOrder',
                Mage::helper('drip_connect/order')->getOrderDataNew($order)
            )->call();

            $order->setIsAlreadyProcessed(true);

            return;
        }

        switch ($order->getState()) {
            case Mage_Sales_Model_Order::STATE_COMPLETE :
                if ($this->refundDiff($order)) {
                    // partial refund of completed order
                    $response = Mage::getModel(
                        'drip_connect/ApiCalls_Helper_CreateUpdateOrder',
                        Mage::helper('drip_connect/order')->getOrderDataRefund($order, $this->refundDiff($order))
                    )->call();
                } else {
                    if ($this->isSameState($order)) {
                        break;
                    }
                    // full complete order
                    $response = Mage::getModel(
                        'drip_connect/ApiCalls_Helper_CreateUpdateOrder',
                        Mage::helper('drip_connect/order')->getOrderDataCompleted($order)
                    )->call();
                }
                break;
            case Mage_Sales_Model_Order::STATE_CLOSED :
                if ($this->isSameState($order)) {
                    break;
                }
                // full refund
                $response = Mage::getModel(
                    'drip_connect/ApiCalls_Helper_CreateUpdateOrder',
                    Mage::helper('drip_connect/order')->getOrderDataRefund($order, $this->refundDiff($order))
                )->call();
                break;
            case Mage_Sales_Model_Order::STATE_PROCESSING :
                if ($this->refundDiff($order)) {
                    // partial refund of processing order
                    $response = Mage::getModel(
                        'drip_connect/ApiCalls_Helper_CreateUpdateOrder',
                        Mage::helper('drip_connect/order')->getOrderDataRefund($order, $this->refundDiff($order))
                    )->call();
                }
                break;
            case Mage_Sales_Model_Order::STATE_CANCELED :
                if ($this->isSameState($order)) {
                    break;
                }
                // cancel order
                $response = Mage::getModel(
                    'drip_connect/ApiCalls_Helper_CreateUpdateOrder',
                    Mage::helper('drip_connect/order')->getOrderDataCanceled($order)
                )->call();
                break;
            default :
                if ($this->isSameState($order)) {
                    break;
                }
                // other states
                $response = Mage::getModel(
                    'drip_connect/ApiCalls_Helper_CreateUpdateOrder',
                    Mage::helper('drip_connect/order')->getOrderDataOther($order)
                )->call();
        }

        $order->setIsAlreadyProcessed(true);
    }

    /**
     * check if current order is new
     *
     * @param  Mage_Sales_Model_Order $order
     *
     * @return int Refund value in cents
     */
    protected function isOrderNew($order)
    {
        if ($order->getState() == Mage_Sales_Model_Order::STATE_NEW) {
            return true;
        }

        $oldData = Mage::registry(self::REGISTRY_KEY_OLD_DATA);
        if (empty($oldData['state'])) {
            return true;
        }

        return false;
    }

    /**
     * check if order get changed with refund action
     *
     * @param  Mage_Sales_Model_Order $order
     *
     * @return int Refund value in cents
     */
    protected function refundDiff($order)
    {
        $oldData = Mage::registry(self::REGISTRY_KEY_OLD_DATA);
        $oldValue = Mage::helper('drip_connect')->priceAsCents($oldData['total_refunded']);
        $newValue = Mage::helper('drip_connect')->priceAsCents($order->getTotalRefunded());


        return ($newValue - $oldValue);
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
