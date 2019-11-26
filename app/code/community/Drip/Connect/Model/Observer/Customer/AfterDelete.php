<?php

class Drip_Connect_Model_Observer_Customer_AfterDelete extends Drip_Connect_Model_Observer_Customer_AdminBase
{
    /**
     * @param Varien_Event_Observer $observer
     */
    protected function executeWhenEnabled($observer)
    {
        $customer = $observer->getCustomer();
        $this->proceedAccountDelete($customer);
    }

    /**
     * drip actions for customer account delete
     *
     * @param Mage_Customer_Model_Customer $customer
     */
    protected function proceedAccountDelete($customer)
    {
        $storeId = Mage::helper('drip_connect/customer')->getCustomerStoreId($customer);
        $config = new Drip_Connect_Model_Configuration($storeId);
        $apiCall = new Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent($config, array(
            'email' => $customer->getEmail(),
            'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_CUSTOMER_DELETED,
        ));
        $response = $apiCall->call();
    }
}
