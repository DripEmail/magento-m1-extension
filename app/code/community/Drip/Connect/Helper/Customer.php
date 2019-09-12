<?php

class Drip_Connect_Helper_Customer extends Mage_Core_Helper_Abstract
{
    /**
     * drip actions for customer account change
     *
     * @param Mage_Customer_Model_Customer $customer
     * @param bool $acceptsMarketing whether the customer accepts marketing. Overrides the customer is_subscribed record.
     * @param string $event The updated/created/deleted event.
     * @param bool $forceStatus Whether the customer has changed marketing preferences which should be synced to Drip.
     */
    public function proceedAccount($customer, $acceptsMarketing = null, $event = Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_CUSTOMER_UPDATED, $forceStatus = false)
    {
        $email = $customer->getEmail();
        if (!Mage::helper('drip_connect')->isEmailValid($email)) {
            $this->getLogger()->log("Skipping guest subscriber update due to unusable email", Zend_Log::NOTICE);
            return;
        }

        $customerData = Drip_Connect_Helper_Data::prepareCustomerData($customer, true, $forceStatus, $acceptsMarketing);

        Mage::getModel('drip_connect/ApiCalls_Helper_CreateUpdateSubscriber', $customerData)->call();

        $response = Mage::getModel('drip_connect/ApiCalls_Helper_RecordAnEvent', array(
            'email' => $customer->getEmail(),
            'action' => $event,
        ))->call();
    }

    /**
     * get address fields to compare
     *
     * @param Mage_Customer_Model_Address $address
     */
    public function getAddressFields($address)
    {
        return array (
            'city' => $address->getCity(),
            'state' => $address->getRegion(),
            'zip_code' => $address->getPostcode(),
            'country' => $address->getCountry(),
            'phone_number' => $address->getTelephone(),
        );
    }
}
