<?php

class Drip_Connect_Model_Observer_Customer_AfterSave extends Drip_Connect_Model_Observer_Base
{
    /**
     * @param Varien_Event_Observer $observer
     */
    protected function executeWhenEnabled($observer)
    {
        $customer = $observer->getCustomer();

        if (Mage::registry(self::REGISTRY_KEY_CUSTOMER_IS_NEW)) {
            // M2 DIFFERENCE: $customer->getIsSubscribed() is actually meaningful in M1, so we can use it here instead
            // of the registry.
            $acceptsMarketing = $customer->getIsSubscribed();
            Mage::helper('drip_connect/customer')->proceedAccount(
                $customer,
                $acceptsMarketing,
                Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_CUSTOMER_NEW,
                $acceptsMarketing
            );
        } else {
            if ($this->isCustomerChanged($customer)) {
                Mage::helper('drip_connect/customer')->proceedAccount(
                    $customer,
                    Mage::registry(self::REGISTRY_KEY_SUBSCRIBER_SUBSCRIBE_INTENT),
                    Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_CUSTOMER_UPDATED,
                    $this->isCustomerStatusChanged($customer)
                );
            }
        }

        Mage::unregister(self::REGISTRY_KEY_CUSTOMER_IS_NEW);
        Mage::unregister(self::REGISTRY_KEY_CUSTOMER_OLD_DATA);
    }

    /**
     * compare orig and new data
     *
     * @param Mage_Customer_Model_Customer $customer
     */
    protected function isCustomerChanged($customer)
    {
        $oldData = Mage::registry(self::REGISTRY_KEY_CUSTOMER_OLD_DATA);
        $newData = Drip_Connect_Helper_Data::prepareCustomerData($customer);

        return (Mage::helper('core')->jsonEncode($oldData) != Mage::helper('core')->jsonEncode($newData));
    }

    protected function isCustomerStatusChanged($customer)
    {
        $newStatus = Mage::registry(self::REGISTRY_KEY_SUBSCRIBER_SUBSCRIBE_INTENT);
        if ($newStatus === null) {
            $subscriber = Mage::getModel('newsletter/subscriber')->loadByCustomer($customer);
            $newStatus = $subscriber->isSubscribed();
        }

        $oldData = Mage::registry(self::REGISTRY_KEY_CUSTOMER_OLD_DATA);
        // TODO: Refactor away stringly typed boolean.
        $oldStatus = $oldData['custom_fields']['accepts_marketing'] == 'yes';
        return $oldStatus !== $newStatus;
    }
}
