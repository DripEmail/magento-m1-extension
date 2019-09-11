<?php

class Drip_Connect_Model_Observer_Customer_SubscriberAfterSave extends Drip_Connect_Model_Observer_Base
{
    /**
     * subscriber was saved
     *
     * @param Varien_Event_Observer $observer
     */
    protected function executeWhenEnabled($observer)
    {
        $request = Mage::app()->getRequest();
        $controller = $request->getControllerName();
        $action = $request->getActionName();

        // treate only massactions executed from newsletter grig
        // subscribe/unsubscribe massactions executed from customers grid get treated by customer's observers
        if ($controller == 'newsletter_subscriber' && $action == 'massUnsubscribe') {
            $subscriber = $observer->getSubscriber();
            $this->proceedSubscriberSave($subscriber);
        }
    }

    /**
     * drip actions for subscriber save
     *
     * @param Mage_Newsletter_Model_Subscriber $ubscriber
     */
    protected function proceedSubscriberSave($subscriber)
    {
        $data = Drip_Connect_Helper_Data::prepareGuestSubscriberData($subscriber);
        Mage::getModel('drip_connect/ApiCalls_Helper_CreateUpdateSubscriber', $data)->call();
    }
}
