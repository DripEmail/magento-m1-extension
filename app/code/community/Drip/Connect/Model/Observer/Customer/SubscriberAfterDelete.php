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
        $this->proceedSubscriberDelete($subscriber);
    }

    /**
     * drip actions for subscriber record delete
     *
     * @param Mage_Newsletter_Model_Subscriber $ubscriber
     */
    protected function proceedSubscriberDelete($subscriber)
    {
        $data = Drip_Connect_Helper_Data::prepareGuestSubscriberData($subscriber);
        $data['custom_fields']['accepts_marketing'] = 'no';
        $data['status'] = 'unsubscribed';
        Mage::getModel('drip_connect/ApiCalls_Helper_CreateUpdateSubscriber', array('data' => $data))->call();
    }
}
