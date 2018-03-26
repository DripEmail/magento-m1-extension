<?php

class Drip_Connect_Model_Observer_Account
{
    const REGISTRY_KEY_IS_NEW = 'newcustomer';

    /**
     * @param Varien_Event_Observer $observer
     */
    public function checkIfCustomerNew($observer)
    {
        $customer = $observer->getCustomer();
        Mage::register(self::REGISTRY_KEY_IS_NEW, (bool)$customer->isObjectNew());
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function afterCustomerSave($observer)
    {
        $customer = $observer->getCustomer();

        if (Mage::registry(self::REGISTRY_KEY_IS_NEW)) {
            $this->proceedAccountNew($customer);
        } else {
            $this->proceedAccount($customer);
        }
        Mage::unregister(self::REGISTRY_KEY_IS_NEW);
    }

    /**
     * drip actions for customer account create
     *
     * @param Mage_Customer_Model_Customer $customer
     */
    protected function proceedAccountNew($customer)
    {
    }

    /**
     * drip actions for customer account change
     *
     * @param Mage_Customer_Model_Customer $customer
     */
    protected function proceedAccount($customer)
    {
    }
}
