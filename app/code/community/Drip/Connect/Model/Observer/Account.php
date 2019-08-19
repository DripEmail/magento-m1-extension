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
     * subscriber was saved
     *
     * @param Varien_Event_Observer $observer
     */
    public function afterSubscriberSave($observer)
    {
        if (!Mage::helper('drip_connect')->isModuleActive()) {
            return;
        }
        $request = Mage::app()->getRequest();
        $controller = $request->getControllerName();
        $action = $request->getActionName();

        // treate only massactions executed from newsletter grig
        // subscribe/unsubscribe massactions executed from customers grid get treated by customer's observers
        if ($controller == 'newsletter_subscriber' && $action == 'massUnsubscribe') {
            $subscriber = $observer->getSubscriber();
            $this->proceedSubscriberSave($subscriber);
        }
    }

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

        if (! $subscriber->getId()) {
            $acceptsMarketing = 'no';
        } else {
            if ($subscriber->getSubscriberStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
                $acceptsMarketing = 'yes';
            } else {
                $acceptsMarketing = 'no';
            }
        }

        Mage::unregister(self::REGISTRY_KEY_SUBSCRIBER_PREV_STATE);
        Mage::register(self::REGISTRY_KEY_SUBSCRIBER_PREV_STATE, $acceptsMarketing);
    }

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
        Mage::unregister(self::REGISTRY_KEY_IS_NEW);
        Mage::register(self::REGISTRY_KEY_IS_NEW, (bool)$customer->isObjectNew());

        if (!$customer->isObjectNew()) {
            $orig = Mage::getModel('customer/customer')->load($customer->getId());
            $data = Drip_Connect_Helper_Data::prepareCustomerData($orig);
            if (Mage::registry(self::REGISTRY_KEY_SUBSCRIBER_PREV_STATE)) {
                $data['custom_fields']['accepts_marketing'] = Mage::registry(self::REGISTRY_KEY_SUBSCRIBER_PREV_STATE);
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
            $this->proceedAccountNew($customer);
            if (! $customer->getIsSubscribed()) {
                $this->unsubscribe($customer->getEmail());
            }
        } else {
            if ($this->isCustomerChanged($customer)) {
                $this->proceedAccount($customer);
            }
            if ($this->isUnsubscribeCallRequired($customer)) {
                $this->unsubscribe($customer->getEmail());
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
     * @param Mage_Newsletter_Model_Subscriber $ubscriber
     */
    protected function proceedGuestSubscriberNew($subscriber)
    {
        $data = Drip_Connect_Helper_Data::prepareGuestSubscriberData($subscriber, false);
        Mage::getModel('drip_connect/ApiCalls_Helper_CreateUpdateSubscriber', $data)->call();

        $response = Mage::getModel('drip_connect/ApiCalls_Helper_RecordAnEvent', array(
            'email' => $subscriber->getSubscriberEmail(),
            'action' => Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_CUSTOMER_NEW,
            'properties' => array(
                'source' => 'magento'
            ),
        ))->call();
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
     * drip unsubscribe action
     *
     * @param string $email
     */
    protected function unsubscribe($email)
    {
        Mage::getModel('drip_connect/ApiCalls_Helper_UnsubscribeSubscriber', array(
            'email' => $email,
        ))->call();
    }

    /**
     * drip actions for subscriber save
     *
     * @param Mage_Newsletter_Model_Subscriber $ubscriber
     */
    protected function proceedSubscriberSave($subscriber)
    {
        $data = Drip_Connect_Helper_Data::prepareGuestSubscriberData($subscriber);
        Mage::getModel('drip_connect/ApiCalls_Helper_CreateUpdateSubscriber', $data)->call();

        if ($subscriber->getSubscriberStatus() != Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
            $this->unsubscribe($subscriber->getEmail());
        }
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
        Mage::getModel('drip_connect/ApiCalls_Helper_CreateUpdateSubscriber', $data)->call();

        $this->unsubscribe($subscriber->getEmail());
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
     * check if we need to send additional api call to cancel all subscriptions
     * (true if status change from yes to no)
     *
     * @param Mage_Customer_Model_Customer $customer
     *
     * @return bool
     */
    protected function isUnsubscribeCallRequired($customer)
    {
        $oldData = Mage::registry(self::REGISTRY_KEY_OLD_DATA);
        $newData = Drip_Connect_Helper_Data::prepareCustomerData($customer);

        return ($newData['custom_fields']['accepts_marketing'] == 'no'
            && $oldData['custom_fields']['accepts_marketing'] != 'no');
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
}
