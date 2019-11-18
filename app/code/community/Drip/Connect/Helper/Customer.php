<?php

class Drip_Connect_Helper_Customer extends Mage_Core_Helper_Abstract
{
    /**
     * drip actions for customer account change
     *
     * @param Mage_Customer_Model_Customer $customer
     * @param Drip_Connect_Model_Configuration $config
     * @param bool $acceptsMarketing whether the customer accepts marketing. Overrides the customer is_subscribed
     *                               record.
     * @param string $event The updated/created/deleted event.
     * @param bool $forceStatus Whether the customer has changed marketing preferences which should be synced to Drip.
     */
    public function proceedAccount(
        $customer,
        $config,
        $acceptsMarketing = null,
        $event = Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_CUSTOMER_UPDATED,
        $forceStatus = false
    ) {
        $email = $customer->getEmail();
        if (!Mage::helper('drip_connect')->isEmailValid($email)) {
            $this->getLogger()->log("Skipping guest subscriber update due to unusable email", Zend_Log::NOTICE);
            return;
        }
        $customerData = Drip_Connect_Helper_Data::prepareCustomerData($customer, true, $forceStatus, $acceptsMarketing);
        $subscriberRequest = new Drip_Connect_Model_ApiCalls_Helper_CreateUpdateSubscriber($data, $config);
        $subscriberRequest->call();
        $apiCall = new Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent($config, array(
            'email' => $customer->getEmail(),
            'action' => $event,
        ));
        $response = $apiCall->call();
    }

    /**
     * Gets the first store when a customer is in website scope.
     * @param Mage_Customer_Model_Customer $customer
     * @return string Customer ID
     */
    public function firstStoreIdForCustomer($customer) {
        // Pilfered/adapted from Mage_Customer_Model_Customer#_getWebsiteStoreId

        $storeId = $customer->getStoreId();
        // When the store ID is null or admin, just get the first one for the website.
        if ((int)$storeId === 0) {
            $storeIds = Mage::app()->getWebsite($customer->getWebsiteId())->getStoreIds();
            reset($storeIds);
            $storeId = current($storeIds);
        }

        return $storeId;
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

    protected function getLogger()
    {
        return Mage::helper('drip_connect/logger')->logger();
    }
}
