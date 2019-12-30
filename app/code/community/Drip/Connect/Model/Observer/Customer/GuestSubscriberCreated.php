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
        $email = Mage::app()->getRequest()->getParam('email');

        $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);

        $config = Drip_Connect_Model_Configuration::forCurrentScope();

        if (! Mage::registry(self::REGISTRY_KEY_NEW_GUEST_SUBSCRIBER)) {
            $customer = Mage::helper('drip_connect')->getCustomerByEmail($email, $config);
            if ($customer->getId() !== null) {
              Mage::helper('drip_connect/customer')->proceedAccount(
                $customer,
                $config,
                true,
                Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_CUSTOMER_UPDATED,
                true
              );
            }
        } else {
           $this->proceedGuestSubscriberNew($subscriber, $config, $subscriber->isSubscribed());
        }
    }

    /**
     * drip actions for customer account create
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @param Drip_Connect_Model_Configuration $config
     * @param bool $forceStatus
     */
    protected function proceedGuestSubscriberNew(Mage_Newsletter_Model_Subscriber $subscriber, Drip_Connect_Model_Configuration $config, $forceStatus = false)
    {
        // M2 DIFFERENCE: This is in the customer helper.
        $email = $subscriber->getSubscriberEmail();
        if (!Mage::helper('drip_connect')->isEmailValid($email)) {
            $this->getLogger()->log("Skipping guest subscriber create due to unusable email", Zend_Log::NOTICE);
            return;
        }

        $data = Drip_Connect_Helper_Data::prepareGuestSubscriberData($subscriber, false, $forceStatus);
        $subscriberRequest = new Drip_Connect_Model_ApiCalls_Helper_CreateUpdateSubscriber($data, $config);
        $subscriberRequest->call();

        $apiCall = new Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent($config, array(
            'email' => $email,
            'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_CUSTOMER_NEW,
            'properties' => array(
                'source' => 'magento'
            ),
        ));
        $response = $apiCall->call();
    }
}
