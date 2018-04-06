<?php
/**
 * Actions with customer account - create, change, delete
 */

class Drip_Connect_Model_Observer_Account
{
    const REGISTRY_KEY_IS_NEW = 'newcustomer';
    const REGISTRY_KEY_OLD_DATA = 'oldcustomerdata';
    const REGISTRY_KEY_OLD_ADDR = 'oldcustomeraddress';

    static $isAddressSaved = false;
    static $doNotUseAfterAddressSave = false;

    /**
     * - check if customer new
     * - store old customer data (which is used in drip) to compare with later
     *
     * @param Varien_Event_Observer $observer
     */
    public function checkIfCustomerNew($observer)
    {
        if (!Mage::helper('drip_connect')->isModuleActive()) {
            return;
        }
        $customer = $observer->getCustomer();
        Mage::register(self::REGISTRY_KEY_IS_NEW, (bool)$customer->isObjectNew());

        if (!$customer->isObjectNew()) {
            $orig = Mage::getModel('customer/customer')->load($customer->getId());
            $data = Drip_Connect_Helper_Data::prepareCustomerData($orig);
            Mage::register(self::REGISTRY_KEY_OLD_DATA, $data);
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function afterCustomerSave($observer)
    {
        if (!Mage::helper('drip_connect')->isModuleActive()) {
            return;
        }
        $customer = $observer->getCustomer();

        if (Mage::registry(self::REGISTRY_KEY_IS_NEW)) {
            $this->proceedAccountNew($customer);
        } else {
            if ($this->isCustomerChanged($customer)) {
                $this->proceedAccount($customer);
            }
        }
        Mage::unregister(self::REGISTRY_KEY_IS_NEW);
        Mage::unregister(self::REGISTRY_KEY_OLD_DATA);
    }

    /**
     * change address from admin area get processed in afterCustomerSave() method
     * this one used for user's actions with address on front
     *
     * @param Varien_Event_Observer $observer
     */
    public function beforeCustomerAddressSaveFront($observer)
    {
        if (!Mage::helper('drip_connect')->isModuleActive()) {
            return;
        }
        if (self::$isAddressSaved) {
            return;
        }
        $address = $observer->getDataObject();

        // if editing address going to be set as default shipping
        // do nothing after addres save. it will be updated on customer save
        if ($address->getIsDefaultShipping()) {
            self::$doNotUseAfterAddressSave = true;
            return;
        }

        $customer = Mage::getModel('customer/customer')->load($address->getCustomerId());

        // if editing address is already a default shipping one
        // get its old values
        if ($customer->getDefaultShippingAddress() && $address->getEntityId() == $customer->getDefaultShippingAddress()->getEntityId()) {
            Mage::register(self::REGISTRY_KEY_OLD_ADDR, $this->getAddressFields($customer->getDefaultShippingAddress()));
        }

        self::$isAddressSaved = true;
    }

    /**
     * change address from admin area get processed in afterCustomerSave() method
     * this one used for user's actions with address on front
     *
     * @param Varien_Event_Observer $observer
     */
    public function afterCustomerAddressSaveFront($observer)
    {
        if (!Mage::helper('drip_connect')->isModuleActive()) {
            return;
        }
        // will be processed with customer update
        if (self::$doNotUseAfterAddressSave) {
            return;
        }

        // change was not done in address we use in drip
        if (empty(Mage::registry(self::REGISTRY_KEY_OLD_ADDR))) {
            return;
        }

        $address = $observer->getDataObject();
        $customer = Mage::getModel('customer/customer')->load($address->getCustomerId());

        if ($this->isAddressChanged($address)) {
            $this->proceedAccount($customer);
        }

        Mage::unregister(self::REGISTRY_KEY_OLD_ADDR);
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function afterCustomerDelete($observer)
    {
        if (!Mage::helper('drip_connect')->isModuleActive()) {
            return;
        }
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
        $customerData = Drip_Connect_Helper_Data::prepareCustomerData($customer, false);
        Mage::getModel('drip_connect/ApiCalls_Helper_CreateUpdateSubscriber', $customerData)->call();

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
        $customerData = Drip_Connect_Helper_Data::prepareCustomerData($customer);

        Mage::getModel('drip_connect/ApiCalls_Helper_CreateUpdateSubscriber', $customerData)->call();

        $response = Mage::getModel('drip_connect/ApiCalls_Helper_RecordAnEvent', array(
            'email' => $customer->getEmail(),
            'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_CUSTOMER_UPDATED,
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
        ))->call();
    }

    /**
     * compare orig and new data
     *
     * @param Mage_Customer_Model_Customer $customer
     */
    protected function isCustomerChanged($customer)
    {
        $oldData = Mage::registry(self::REGISTRY_KEY_OLD_DATA);
        $newData = Drip_Connect_Helper_Data::prepareCustomerData($customer);

        return (serialize($oldData) != serialize($newData));
    }

    /**
     * compare orig and new data
     *
     * @param Mage_Customer_Model_Address $address
     */
    protected function isAddressChanged($address)
    {
        $oldData = Mage::registry(self::REGISTRY_KEY_OLD_ADDR);
        $newData = $this->getAddressFields($address);

        return (serialize($oldData) != serialize($newData));
    }

    /**
     * get address fields to compare
     *
     * @param Mage_Customer_Model_Address $address
     */
    protected function getAddressFields($address)
    {
        return array (
            'city' => $address->getCity(),
            'state' => $address->getRegion(),
            'zip_code' => $address->getPostcode(),
            'country' => $address->getCountry(),
            'phone_number' => $address->getTelephone(),
        );
    }
}
