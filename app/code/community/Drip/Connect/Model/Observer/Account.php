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
        $gender = $customer->getGender();
        if ($gender == 1) {
            $gender = 'Male';
        } else if ($gender == 2) {
            $gender = 'Female';
        }
        Mage::getModel('drip_connect/ApiCalls_Helper_CreateUpdateSubscriber', array(
            'email' => $customer->getEmail(),
            'user_id' => $customer->getEntityId(),
            'ip_address' => Mage::helper('core/http')->getRemoteAddr(),
            'custom_fields' => array(
                'first_name' => $customer->getFirstname(),
                'last_name' => $customer->getLastname(),
                'birthday' => $customer->getDob(),
                'gender' => $gender,
                'city' => $customer->getDefaultShippingAddress()->getCity(),
                'state' => $customer->getDefaultShippingAddress()->getRegion(),
                'zip_code' => $customer->getDefaultShippingAddress()->getPostcode(),
                'country' => $customer->getDefaultShippingAddress()->getCountry(),
                'phone_number' => $customer->getDefaultShippingAddress()->getTelephone(),
                'magento_account_created' => $customer->getCreatedAt(),
                'magento_customer_group' => Mage::getModel('customer/group')->load($customer->getGroupId())->getCustomerGroupCode(),
                'magento_store' => $customer->getStoreId(),
                'accepts_marketing' => ($customer->getIsSubscribed() ? 'yes' : 'no'),
            ),
        ))->call();

        $response = Mage::getModel('drip_connect/ApiCalls_Helper_RecordAnEvent', array(
            'email' => $customer->getEmail(),
            'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_NEW_CUSTOMER,
            'properties' => array(
                'source' => 'magento'
            ),
        ))->call();
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
