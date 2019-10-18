<?php

class Drip_Connect_Model_Observer_Customer_BeforeSave extends Drip_Connect_Model_Observer_Base
{
    /**
     * - check if customer new
     * - store old customer data (which is used in drip) to compare with later
     *
     * @param Varien_Event_Observer $observer
     */
    protected function executeWhenEnabled($observer)
    {
        $customer = $observer->getCustomer();
        Mage::unregister(self::REGISTRY_KEY_CUSTOMER_IS_NEW);
        Mage::register(self::REGISTRY_KEY_CUSTOMER_IS_NEW, (bool)$customer->isObjectNew());

        if (!$customer->isObjectNew()) {
            $orig = Mage::getModel('customer/customer')->load($customer->getId());
            $data = Drip_Connect_Helper_Data::prepareCustomerData($orig);
            if (Mage::registry(self::REGISTRY_KEY_SUBSCRIBER_PREV_STATE) !== null) {
                $data['custom_fields']['accepts_marketing'] =
                    Mage::registry(self::REGISTRY_KEY_SUBSCRIBER_PREV_STATE) ? 'yes' : 'no';
            }

            Mage::unregister(self::REGISTRY_KEY_CUSTOMER_OLD_DATA);
            Mage::register(self::REGISTRY_KEY_CUSTOMER_OLD_DATA, $data);
        } else {
            $customer->setDrip(1);
            Mage::helper('drip_connect/quote')->checkForEmptyQuoteCustomer($customer);
        }
    }
}
