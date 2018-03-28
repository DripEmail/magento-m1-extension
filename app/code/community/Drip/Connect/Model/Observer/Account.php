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
     * @param Varien_Event_Observer $observer
     *
     * change address from admin area get processed in afterCustomerSave() method
     * this one used for user's actions with address on front
     */
    public function afterCustomerAddressSaveFront($observer)
    {
        $address = $observer->getDataObject();
        $customer = Mage::getModel('customer/customer')->load($address->getCustomerId());
        // customer changed an address we use in drip - update drip data
        if ($customer->getDefaultShippingAddress() && $address->getEntityId() == $customer->getDefaultShippingAddress()->getEntityId()) {
            $this->proceedAccount($customer);
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function afterCustomerDelete($observer)
    {
        $customer = $observer->getCustomer();
        $this->proceedAccountDelete($customer);
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
                'city' => ($customer->getDefaultShippingAddress() ? $customer->getDefaultShippingAddress()->getCity() : ''),
                'state' => ($customer->getDefaultShippingAddress() ? $customer->getDefaultShippingAddress()->getRegion() : ''),
                'zip_code' => ($customer->getDefaultShippingAddress() ? $customer->getDefaultShippingAddress()->getPostcode() : ''),
                'country' => ($customer->getDefaultShippingAddress() ? $customer->getDefaultShippingAddress()->getCountry() : ''),
                'phone_number' => ($customer->getDefaultShippingAddress() ? $customer->getDefaultShippingAddress()->getTelephone() : ''),
                'magento_account_created' => $customer->getCreatedAt(),
                'magento_customer_group' => Mage::getModel('customer/group')->load($customer->getGroupId())->getCustomerGroupCode(),
                'magento_store' => $customer->getStoreId(),
                'accepts_marketing' => ($customer->getIsSubscribed() ? 'yes' : 'no'),
            ),
        ))->call();

        $response = Mage::getModel('drip_connect/ApiCalls_Helper_RecordAnEvent', array(
            'email' => $customer->getEmail(),
            'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_CUSTOMER_NEW,
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
        if ($customer->getData('email') != $customer->getOrigData('email')) {
            $newEmail = $customer->getData('email');
        } else {
            $newEmail = '';
        }
        $gender = $customer->getGender();
        if ($gender == 1) {
            $gender = 'Male';
        } else if ($gender == 2) {
            $gender = 'Female';
        }
        Mage::getModel('drip_connect/ApiCalls_Helper_CreateUpdateSubscriber', array(
            'email' => $customer->getOrigData('email'),
            'new_email' => ($newEmail ? $newEmail : ''),
            'user_id' => $customer->getEntityId(),
            'ip_address' => Mage::helper('core/http')->getRemoteAddr(),
            'custom_fields' => array(
                'first_name' => $customer->getFirstname(),
                'last_name' => $customer->getLastname(),
                'birthday' => $customer->getDob(),
                'gender' => $gender,
                'city' => ($customer->getDefaultShippingAddress() ? $customer->getDefaultShippingAddress()->getCity() : ''),
                'state' => ($customer->getDefaultShippingAddress() ? $customer->getDefaultShippingAddress()->getRegion() : ''),
                'zip_code' => ($customer->getDefaultShippingAddress() ? $customer->getDefaultShippingAddress()->getPostcode() : ''),
                'country' => ($customer->getDefaultShippingAddress() ? $customer->getDefaultShippingAddress()->getCountry() : ''),
                'phone_number' => ($customer->getDefaultShippingAddress() ? $customer->getDefaultShippingAddress()->getTelephone() : ''),
                'magento_account_created' => $customer->getCreatedAt(),
                'magento_customer_group' => Mage::getModel('customer/group')->load($customer->getGroupId())->getCustomerGroupCode(),
                'magento_store' => $customer->getStoreId(),
                'accepts_marketing' => ($customer->getIsSubscribed() ? 'yes' : 'no'),
            ),
        ))->call();

        $response = Mage::getModel('drip_connect/ApiCalls_Helper_RecordAnEvent', array(
            'email' => $customer->getEmail(),
            'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_CUSTOMER_UPDATED,
            'properties' => array(
                'source' => 'magento'
            ),
        ))->call();
    }

    /**
     * drip actions for customer account delete
     *
     * @param Mage_Customer_Model_Customer $customer
     */
    protected function proceedAccountDelete($customer)
    {
        $response = Mage::getModel('drip_connect/ApiCalls_Helper_RecordAnEvent', array(
            'email' => $customer->getEmail(),
            'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_CUSTOMER_DELETED,
            'properties' => array(
                'source' => 'magento'
            ),
        ))->call();
    }
}
