<?php
/**
 * Customer actions without data change - login, logout, visit some pages, etc
 */

class Drip_Connect_Model_Observer_Customer
{
    /**
     * @param Varien_Event_Observer $observer
     */
    public function customerLogin($observer)
    {
        $customer = $observer->getCustomer();
        $this->proceedCustomerLogin($customer);
    }

    /**
     * drip actions for customer log in
     *
     * @param Mage_Customer_Model_Customer $customer
     */
    protected function proceedCustomerLogin($customer)
    {
        $response = Mage::getModel('drip_connect/ApiCalls_Helper_RecordAnEvent', array(
            'email' => $customer->getEmail(),
            'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_CUSTOMER_LOGIN,
            'properties' => array(
                'source' => 'magento'
            ),
        ))->call();
    }
}
