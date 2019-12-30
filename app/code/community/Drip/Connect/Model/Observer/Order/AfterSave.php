<?php

class Drip_Connect_Model_Observer_Order_AfterSave extends Drip_Connect_Model_Observer_Order_OrderBase
{
    /**
     * @param Varien_Event_Observer $observer
     */
    protected function executeWhenEnabled($observer)
    {
        $order = $observer->getEvent()->getOrder();
        if (!$order->getId()) {
            return;
        }

        // We base on order store ID so it works in Admin.
        $config = new Drip_Connect_Model_Configuration($order->getStoreId());

        $this->proceedOrder($order, $config);
        Mage::unregister(self::REGISTRY_KEY_ORDER_OLD_DATA);
    }

    /**
     * drip actions on order state events
     *
     * @param Mage_Sales_Model_Order $order
     * @param Drip_Connect_Model_Configuration $config
     */
    protected function proceedOrder(Mage_Sales_Model_Order $order, Drip_Connect_Model_Configuration $config)
    {
        // it is possible that we've already processed this order
        if ($order->getIsAlreadyProcessed()) {
            return;
        }

        $orderTransformer = new Drip_Connect_Model_Transformer_Order($order, $config);

        if (!$orderTransformer->isCanBeSent()) {
            return;
        }

        if ($this->isOrderNew($order)) {
            if ($this->isSameState($order)) {
                return;
            }

            // if guest checkout and there is no such user and there is no such subscriber
            // create subscriber record
            if ($order->getCustomerIsGuest()
                && ! Mage::helper('drip_connect')->isCustomerExists($order->getCustomerEmail(), $config)
                && ! Mage::helper('drip_connect')->isSubscriberExists($order->getCustomerEmail())
            ) {
                $customerData = Mage::helper('drip_connect')->prepareCustomerDataForGuestCheckout($order);
                $subscriberRequest = new Drip_Connect_Model_ApiCalls_Helper_CreateUpdateSubscriber($data, $config);
                $subscriberRequest->call();
            }

            // new order
            $apiCall = new Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder(
                $config,
                $orderTransformer->getOrderDataNew()
            );
            $response = $apiCall->call();

            $order->setIsAlreadyProcessed(true);

            return;
        }

        switch ($order->getState()) {
            case Mage_Sales_Model_Order::STATE_COMPLETE :
                if ($this->refundDiff($order)) {
                    // partial refund of completed order
                    $apiCall = new Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder(
                        $config,
                        $orderTransformer->getOrderDataRefund($this->refundDiff($order))
                    );
                    $response = $apiCall->call();
                } else {
                    if ($this->isSameState($order)) {
                        break;
                    }

                    // full complete order
                    $apiCall = new Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder(
                        $config,
                        $orderTransformer->getOrderDataCompleted()
                    );
                    $response = $apiCall->call();
                }
                break;
            case Mage_Sales_Model_Order::STATE_CLOSED :
                if ($this->isSameState($order)) {
                    break;
                }

                // full refund
                $apiCall = new Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder(
                    $config,
                    $orderTransformer->getOrderDataRefund($this->refundDiff($order))
                );
                $response = $apiCall->call();
                break;
            case Mage_Sales_Model_Order::STATE_PROCESSING :
                if ($this->refundDiff($order)) {
                    // partial refund of processing order
                    $apiCall = new Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder(
                        $config,
                        $orderTransformer->getOrderDataRefund($this->refundDiff($order))
                    );
                    $response = $apiCall->call();
                }
                break;
            case Mage_Sales_Model_Order::STATE_CANCELED :
                if ($this->isSameState($order)) {
                    break;
                }

                // cancel order
                $apiCall = new Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder(
                    $config,
                    $orderTransformer->getOrderDataCanceled()
                );
                $response = $apiCall->call();
                break;
            default :
                if ($this->isSameState($order)) {
                    break;
                }

                // other states
                $apiCall = new Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder(
                    $config,
                    $orderTransformer->getOrderDataOther()
                );
                $response = $apiCall->call();
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

        $oldData = Mage::registry(self::REGISTRY_KEY_ORDER_OLD_DATA);
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
        $oldData = Mage::registry(self::REGISTRY_KEY_ORDER_OLD_DATA);
        $oldValue = Mage::helper('drip_connect')->priceAsCents($oldData['total_refunded']);
        $newValue = Mage::helper('drip_connect')->priceAsCents($order->getTotalRefunded());


        return ($newValue - $oldValue);
    }

    /**
     * check if order state has not been changed
     */
    protected function isSameState($order)
    {
        $oldData = Mage::registry(self::REGISTRY_KEY_ORDER_OLD_DATA);
        $oldValue = $oldData['state'];
        $newValue = $order->getState();

        return ($oldValue == $newValue);
    }
}
