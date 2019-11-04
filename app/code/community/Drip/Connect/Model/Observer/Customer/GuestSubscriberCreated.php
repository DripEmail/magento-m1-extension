<?php

class Drip_Connect_Model_Observer_Customer_GuestSubscriberCreated extends Drip_Connect_Model_Observer_Base
{
    /**
     * guest subscribe on site
     *
     * @param Varien_Event_Observer $observer
     */
    protected function executeWhenEnabled($observer)
    {
        if (! Mage::registry(self::REGISTRY_KEY_NEW_GUEST_SUBSCRIBER)) {
            return;
        }

        $email = Mage::app()->getRequest()->getParam('email');
        $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);

        $this->proceedGuestSubscriberNew($subscriber, $subscriber->isSubscribed());
    }

    /**
     * drip actions for customer account create
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @param bool $forceStatus
     */
    protected function proceedGuestSubscriberNew($subscriber, $forceStatus = false)
    {
        // M2 DIFFERENCE: This is in the customer helper.
        $email = $subscriber->getSubscriberEmail();
        if (!Mage::helper('drip_connect')->isEmailValid($email)) {
            $this->getLogger()->log("Skipping guest subscriber create due to unusable email", Zend_Log::NOTICE);
            return;
        }

        $data = Drip_Connect_Helper_Data::prepareGuestSubscriberData($subscriber, false, $forceStatus);
        Mage::getModel('drip_connect/ApiCalls_Helper_CreateUpdateSubscriber', array('data' => $data))->call();

        $response = Mage::getModel(
            'drip_connect/ApiCalls_Helper_RecordAnEvent',
            array(
                'email' => $email,
                'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_CUSTOMER_NEW,
                'properties' => array(
                    'source' => 'magento'
                ),
            )
        )->call();
    }
}
