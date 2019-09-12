<?php

class Drip_Connect_Model_Observer_Customer_BeforeAddressSave extends Drip_Connect_Model_Observer_Base
{
    static $isAddressSaved = false;

    /**
     * change address from admin area get processed in afterCustomerSave() method
     * this one used for user's actions with address on front
     *
     * @param Varien_Event_Observer $observer
     */
    protected function executeWhenEnabled($observer)
    {
        if (self::$isAddressSaved) {
            return;
        }
        $address = $observer->getDataObject();

        // if editing address going to be set as default shipping
        // do nothing after addres save. it will be updated on customer save
        if ($address->getIsDefaultShipping()) {
            return;
        }

        $customer = Mage::getModel('customer/customer')->load($address->getCustomerId());

        // if editing address is already a default shipping one
        // get its old values
        if ($customer->getDefaultShippingAddress() && $address->getEntityId() === $customer->getDefaultShippingAddress()->getEntityId()) {
            Mage::unregister(self::REGISTRY_KEY_CUSTOMER_OLD_ADDR);
            Mage::register(self::REGISTRY_KEY_CUSTOMER_OLD_ADDR, Mage::helper('drip_connect')->getAddressFields($customer->getDefaultShippingAddress()));
        }

        self::$isAddressSaved = true;
    }
}
