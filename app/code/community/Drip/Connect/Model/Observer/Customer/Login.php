<?php

class Drip_Connect_Model_Observer_Customer_Login extends Drip_Connect_Model_Observer_Base
{
    /**
     * @param Varien_Event_Observer $observer
     */
    protected function executeWhenEnabled($observer)
    {
        $customer = $observer->getCustomer();
        $this->proceedCustomerLogin($customer);

        //Check for active quote
        Mage::helper('drip_connect/quote')->checkForEmptyQuoteCustomer($customer);
    }

    /**
     * drip actions for customer log in
     *
     * @param Mage_Customer_Model_Customer $customer
     */
    protected function proceedCustomerLogin($customer)
    {
        // This is pertinant to a customer, who are scoped to websites, but it
        // is in the context of a store view, so we'll use the current scope.
        $config = Drip_Connect_Model_Configuration::forCurrentScope();
        $apiCall = new Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent($config, array(
            'email' => $customer->getEmail(),
            'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_CUSTOMER_LOGIN,
        ));
        $response = $apiCall->call();
    }
}
