<?php

class Drip_Connect_Model_Observer_Customer_AfterAddressSave extends Drip_Connect_Model_Observer_Base
{
    /**
     * change address from admin area get processed in afterCustomerSave() method
     * this one used for user's actions with address on front
     *
     * @param Varien_Event_Observer $observer
     */
    protected function executeWhenEnabled($observer)
    {
        // change was not done in address we use in drip
        if (empty(Mage::registry(self::REGISTRY_KEY_CUSTOMER_OLD_ADDR))) {
            return;
        }

        $address = $observer->getDataObject();
        $customer = Mage::getModel('customer/customer')->load($address->getCustomerId());

        if ($this->isAddressChanged($address)) {
            Mage::helper('drip_connect')->proceedAccount($customer);
        }

        Mage::unregister(self::REGISTRY_KEY_CUSTOMER_OLD_ADDR);
    }

    /**
     * compare orig and new data
     *
     * @param Mage_Customer_Model_Address $address
     */
    protected function isAddressChanged($address)
    {
        $oldData = Mage::registry(self::REGISTRY_KEY_CUSTOMER_OLD_ADDR);
        $newData = Mage::helper('drip_connect')->getAddressFields($address);

        return (Mage::helper('core')->jsonEncode($oldData) != Mage::helper('core')->jsonEncode($newData));
    }
}
