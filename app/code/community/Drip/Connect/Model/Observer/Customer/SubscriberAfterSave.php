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

        $config = Drip_Connect_Model_Configuration::forCurrentScope();

        // treate only massactions executed from newsletter grig
        // subscribe/unsubscribe massactions executed from customers grid get treated by customer's observers
        if ($controller === 'newsletter_subscriber' && $action === 'massUnsubscribe') {
            $subscriber = $observer->getSubscriber();
            $this->proceedSubscriberSave($subscriber, $config);
        }
    }

    /**
     * drip actions for subscriber save
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @param Drip_Connect_Model_Configuration $config
     */
    protected function proceedSubscriberSave(Mage_Newsletter_Model_Subscriber $subscriber, Drip_Connect_Model_Configuration $config)
    {
        $data = Drip_Connect_Helper_Data::prepareGuestSubscriberData($subscriber);
        $subscriberRequest = new Drip_Connect_Model_ApiCalls_Helper_CreateUpdateSubscriber($data, $config);
        $subscriberRequest->call();
    }
}
