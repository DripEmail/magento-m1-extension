<?php

class Drip_Connect_Model_Observer_Customer_SubscriberAfterDelete extends Drip_Connect_Model_Observer_Base
{
    /**
     * subscriber was removed
     *
     * @param Varien_Event_Observer $observer
     */
    protected function executeWhenEnabled($observer)
    {
        $subscriber = $observer->getSubscriber();

        $config = Drip_Connect_Model_Configuration::forCurrentScope();

        $this->proceedSubscriberDelete($subscriber, $config);
    }

    /**
     * drip actions for subscriber record delete
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @param Drip_Connect_Model_Configuration $config
     */
    protected function proceedSubscriberDelete(Mage_Newsletter_Model_Subscriber $subscriber, Drip_Connect_Model_Configuration $config)
    {
        $data = Drip_Connect_Helper_Data::prepareGuestSubscriberData($subscriber);
        $data['custom_fields']['accepts_marketing'] = 'no';
        $data['status'] = 'unsubscribed';
        $subscriberRequest = new Drip_Connect_Model_ApiCalls_Helper_CreateUpdateSubscriber($data, $config);
        $subscriberRequest->call();
    }
}
