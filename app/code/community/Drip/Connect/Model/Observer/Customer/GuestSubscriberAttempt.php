<?php

class Drip_Connect_Model_Observer_Customer_GuestSubscriberAttempt extends Drip_Connect_Model_Observer_Base
{
    /**
     * guest subscribe on site
     *
     * @param Varien_Event_Observer $observer
     */
    protected function executeWhenEnabled($observer)
    {
        $email = Mage::app()->getRequest()->getParam('email');
        $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);

        Mage::unregister(self::REGISTRY_KEY_NEW_GUEST_SUBSCRIBER);
        if (! $subscriber->getId()) {
            Mage::register(self::REGISTRY_KEY_NEW_GUEST_SUBSCRIBER, true);
        }
    }
}
