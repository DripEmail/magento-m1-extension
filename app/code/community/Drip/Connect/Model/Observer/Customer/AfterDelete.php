<?php

class Drip_Connect_Model_Observer_Customer_AfterDelete extends Drip_Connect_Model_Observer_Base
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
        $response = Mage::getModel(
            'drip_connect/ApiCalls_Helper_RecordAnEvent',
            array(
                'email' => $customer->getEmail(),
                'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_CUSTOMER_DELETED,
            )
        )->call();
    }
}
