<?php

class Drip_Connect_Model_Observer_Customer_NewsletterSave extends Drip_Connect_Model_Observer_Base
{
    /**
     * save old customer subscription state
     *
     * @param Varien_Event_Observer $observer
     */
    protected function executeWhenEnabled($observer)
    {
        $customerEmail = Mage::getSingleton('customer/session')->getCustomer()
            ->setStoreId(Mage::app()->getStore()->getId())
            ->getEmail();

        $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($customerEmail);

        Mage::unregister(self::REGISTRY_KEY_SUBSCRIBER_PREV_STATE);
        Mage::register(self::REGISTRY_KEY_SUBSCRIBER_PREV_STATE, $subscriber->isSubscribed());

        Mage::unregister(self::REGISTRY_KEY_SUBSCRIBER_SUBSCRIBE_INTENT);
        Mage::register(
            self::REGISTRY_KEY_SUBSCRIBER_SUBSCRIBE_INTENT,
            Mage::app()->getRequest()->getparam('is_subscribed', false)
        );
    }
}
