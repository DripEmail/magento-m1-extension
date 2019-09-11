<?php
/**
 * Actions with customer account - create, change, delete
 */

class Drip_Connect_Model_Observer_Account
{
    const REGISTRY_KEY_IS_NEW = 'newcustomer';
    const REGISTRY_KEY_OLD_DATA = 'oldcustomerdata';
    const REGISTRY_KEY_OLD_ADDR = 'oldcustomeraddress';
    const REGISTRY_KEY_SUBSCRIBER_PREV_STATE = 'oldsubscriptionstatus';
    const REGISTRY_KEY_NEW_GUEST_SUBSCRIBER = 'newguestsubscriber';

    static $isAddressSaved = false;
    static $doNotUseAfterAddressSave = false;

    /**
     * subscriber was removed
     *
     * @param Varien_Event_Observer $observer
     */
    public function afterSubscriberDelete($observer)
    {
        if (!Mage::helper('drip_connect')->isModuleActive()) {
            return;
        }
        $subscriber = $observer->getSubscriber();
        $this->proceedSubscriberDelete($subscriber);
    }

    /**
     * guest subscribe on site
     *
     * @param Varien_Event_Observer $observer
     */
    public function newGuestSubscriberAttempt($observer)
    {
        if (!Mage::helper('drip_connect')->isModuleActive()) {
            return;
        }
        $email = Mage::app()->getRequest()->getParam('email');
        $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);

        Mage::unregister(self::REGISTRY_KEY_NEW_GUEST_SUBSCRIBER);
        if (! $subscriber->getId()) {
            Mage::register(self::REGISTRY_KEY_NEW_GUEST_SUBSCRIBER, true);
        }
    }

    /**
     * guest subscribe on site
     *
     * @param Varien_Event_Observer $observer
     */
    public function newGuestSubscriberCreated($observer)
    {
        if (!Mage::helper('drip_connect')->isModuleActive()) {
            return;
        }

        if (! Mage::registry(self::REGISTRY_KEY_NEW_GUEST_SUBSCRIBER)) {
            return;
        }

        $email = Mage::app()->getRequest()->getParam('email');
        $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);

        $this->proceedGuestSubscriberNew($subscriber);
    }

    /**
     * save old customer subscription state
     *
     * @param Varien_Event_Observer $observer
     */
    public function saveSubscriptionState($observer)
    {
        if (!Mage::helper('drip_connect')->isModuleActive()) {
            return;
        }

        $customerEmail = Mage::getSingleton('customer/session')->getCustomer()
            ->setStoreId(Mage::app()->getStore()->getId())
            ->getEmail();

        $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($customerEmail);

        Mage::unregister(self::REGISTRY_KEY_SUBSCRIBER_PREV_STATE);
        Mage::register(self::REGISTRY_KEY_SUBSCRIBER_PREV_STATE, $subscriber->isSubscribed());
    }

    /**
     * - check if customer new
     * - store old customer data (which is used in drip) to compare with later
     *
     * @param Varien_Event_Observer $observer
     */
    public function beforeCustomerSave($observer)
    {
        if (!Mage::helper('drip_connect')->isModuleActive()) {
            return;
        }
        $customer = $observer->getCustomer();
        Mage::unregister(self::REGISTRY_KEY_IS_NEW);
        Mage::register(self::REGISTRY_KEY_IS_NEW, (bool)$customer->isObjectNew());

        if (!$customer->isObjectNew()) {
            $orig = Mage::getModel('customer/customer')->load($customer->getId());
            $data = Drip_Connect_Helper_Data::prepareCustomerData($orig);
            if (Mage::registry(self::REGISTRY_KEY_SUBSCRIBER_PREV_STATE) !== null) {
                $data['custom_fields']['accepts_marketing'] = Mage::registry(self::REGISTRY_KEY_SUBSCRIBER_PREV_STATE) ? 'yes' : 'no';
            }
            Mage::unregister(self::REGISTRY_KEY_OLD_DATA);
            Mage::register(self::REGISTRY_KEY_OLD_DATA, $data);
        } else {
            $customer->setDrip(1);
            Mage::helper('drip_connect/quote')->checkForEmptyQuoteCustomer($customer);
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
            $this->proceedAccount($customer, null, Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_CUSTOMER_NEW);
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
            Mage::unregister(self::REGISTRY_KEY_OLD_ADDR);
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
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @param bool $forceStatus
     */
    protected function proceedGuestSubscriberNew($subscriber, $forceStatus = false)
    {
        $email = $subscriber->getSubscriberEmail();
        if (!Mage::helper('drip_connect')->isEmailValid($email)) {
            $this->getLogger()->log("Skipping guest subscriber create due to unusable email", Zend_Log::NOTICE);
            return;
        }

        $data = Drip_Connect_Helper_Data::prepareGuestSubscriberData($subscriber, false, $forceStatus);
        Mage::getModel('drip_connect/ApiCalls_Helper_CreateUpdateSubscriber', $data)->call();

        $response = Mage::getModel('drip_connect/ApiCalls_Helper_RecordAnEvent', array(
            'email' => $email,
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
     * @param bool $acceptsMarketing whether the customer accepts marketing. Overrides the customer is_subscribed record.
     * @param string $event The updated/created/deleted event.
     * @param bool $forceStatus Whether the customer has changed marketing preferences which should be synced to Drip.
     */
    protected function proceedAccount($customer, $acceptsMarketing = null, $event = Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_CUSTOMER_UPDATED, $forceStatus = false)
    {
        $email = $customer->getEmail();
        if (!Mage::helper('drip_connect')->isEmailValid($email)) {
            $this->getLogger()->log("Skipping guest subscriber update due to unusable email", Zend_Log::NOTICE);
            return;
        }

        $customerData = Drip_Connect_Helper_Data::prepareCustomerData($customer, true, $forceStatus, $acceptsMarketing);

        Mage::getModel('drip_connect/ApiCalls_Helper_CreateUpdateSubscriber', $customerData)->call();

        $response = Mage::getModel('drip_connect/ApiCalls_Helper_RecordAnEvent', array(
            'email' => $customer->getEmail(),
            'action' => $event,
        ))->call();
    }

    /**
     * drip actions for subscriber record delete
     *
     * @param Mage_Newsletter_Model_Subscriber $ubscriber
     */
    protected function proceedSubscriberDelete($subscriber)
    {
        $data = Drip_Connect_Helper_Data::prepareGuestSubscriberData($subscriber);
        $data['custom_fields']['accepts_marketing'] = 'no';
        $data['status'] = 'unsubscribed';
        Mage::getModel('drip_connect/ApiCalls_Helper_CreateUpdateSubscriber', $data)->call();
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

        return (Mage::helper('core')->jsonEncode($oldData) != Mage::helper('core')->jsonEncode($newData));
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

        return (Mage::helper('core')->jsonEncode($oldData) != Mage::helper('core')->jsonEncode($newData));
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

    protected function getLogger() {
        return Mage::helper('drip_connect/logger')->logger();
    }
}
