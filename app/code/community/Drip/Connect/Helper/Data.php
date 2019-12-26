<?php

class Drip_Connect_Helper_Data extends Mage_Core_Helper_Abstract
{
    const QUOTE_KEY = 'q';
    const STORE_KEY = 's';
    const SECURE_KEY = 'k';
    const SALT = 'kjs5hds%$#zgf';

    /**
     * prepare array of guest subscriber data
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @param bool $updatableOnly leave only those fields which are used in update action
     * @param bool $statusChanged whether the status has changed and should be updated in Drip
     *
     * @return array
     */
    static public function prepareGuestSubscriberData($subscriber, $updatableOnly = true, $statusChanged = false)
    {
        $acceptsMarketing = $subscriber->isSubscribed();

        $data = array (
            'email' => (string) $subscriber->getSubscriberEmail(),
            'ip_address' => (string) Mage::helper('core/http')->getRemoteAddr(),
            'initial_status' => $acceptsMarketing ? 'active' : 'unsubscribed',
            'custom_fields' => array(
                'accepts_marketing' => $acceptsMarketing ? 'yes' : 'no',
            ),
        );

        if ($statusChanged) {
            $data['status'] = $acceptsMarketing ? 'active' : 'unsubscribed';
        }

        if ($updatableOnly) {
            unset($data['ip_address']);
        }

        return $data;
    }

    /**
     * prepare array of customer data we use to send in drip
     *
     * @param Mage_Customer_Model_Customer $customer
     * @param bool $updatableOnly leave only those fields which are used in update action
     * @param bool $statusChanged whether the status has changed and should be synced
     * @param bool $overriddenStatus whether the status should be something other than what is on the customer's
     *                               is_subscribed field.
     */
    static public function prepareCustomerData(
        $customer,
        $updatableOnly = true,
        $statusChanged = false,
        $overriddenStatus = null
    ) {
        if ($customer->getOrigData() && $customer->getData('email') != $customer->getOrigData('email')) {
            $newEmail = $customer->getData('email');
        } else {
            $newEmail = '';
        }

        if ($overriddenStatus !== null) {
            $status = $overriddenStatus;
        } else {
            $subscriber = Mage::getModel('newsletter/subscriber')->loadByCustomer($customer);
            $status = $subscriber->isSubscribed();
        }

        $data = array (
            'email' => (string) $customer->getEmail(),
            'new_email' => ($newEmail ? $newEmail : ''),
            'ip_address' => (string) Mage::helper('core/http')->getRemoteAddr(),
            'initial_status' => $status ? 'active' : 'unsubscribed',
            'custom_fields' => array(
                'first_name' => $customer->getFirstname(),
                'last_name' => $customer->getLastname(),
                'birthday' => $customer->getDob(),
                'gender' => Mage::helper('drip_connect')->getGenderText($customer->getGender()),
                'magento_account_created' => $customer->getCreatedAt(),
                'magento_customer_group' => Mage::getModel('customer/group')->load($customer->getGroupId())
                                                                            ->getCustomerGroupCode(),
                'magento_store' => Mage::helper('drip_connect/customer')->getCustomerStoreId($customer),
                'accepts_marketing' => ($status ? 'yes' : 'no'),
            ),
        );

        if ($statusChanged) {
            $data['status'] = $status ? 'active' : 'unsubscribed';
        }

        if ($updatableOnly) {
            unset($data['custom_fields']['magento_account_created']);
            unset($data['ip_address']);
        }

        return $data;
    }

    /**
     * check if subscriber exists on the store
     *
     * @return bool
     */
    public function isSubscriberExists($email, $storeId = null)
    {
        if ($storeId == null) {
            $storeId = Mage::app()->getStore()->getId();
        }

        $collection = Mage::getModel('newsletter/subscriber')->getCollection()
            ->addFieldToFilter('subscriber_email', $email)
            ->addFieldToFilter('store_id', $storeId);

        return (bool) $collection->getSize();
    }

    /**
     * check if customer exists on the website
     *
     * @return bool
     */
    public function isCustomerExists($email, $websiteId = null)
    {
        if ($websiteId == null) {
            $websiteId = Mage::app()->getStore()->getWebsiteId();
        }

        $customer = Mage::getModel("customer/customer")->setWebsiteId($websiteId)->loadByEmail($email);

        return (bool) $customer->getId();
    }

    /**
     * find customer by email address
     *
     * @return Mage_Customer_Model_Customer $customer
     */
    public function getCustomerByEmail($email, $websiteId = null)
    {
        if ($websiteId == null) {
            $websiteId = Mage::app()->getStore()->getWebsiteId();
        }

        $customer = Mage::getModel("customer/customer")->setWebsiteId($websiteId)->loadByEmail($email);

        return $customer;
    }

    /**
     * @param $order
     *
     * @return array
     */
    public function prepareCustomerDataForGuestCheckout($order)
    {

        return array (
            'email' => (string) $order->getCustomerEmail(),
            'ip_address' => (string) Mage::helper('core/http')->getRemoteAddr(),
            'initial_status' => 'unsubscribed',
            'custom_fields' => array(
                'first_name' => $order->getCustomerFirstname(),
                'last_name' => $order->getCustomerLastname(),
                'birthday' => $order->getCustomerDob(),
                'gender' => $this->getGenderText($order->getCustomerGender()),
                'city' => $order->getBillingAddress()->getCity(),
                'state' => $order->getBillingAddress()->getRegion(),
                'zip_code' => $order->getBillingAddress()->getPostcode(),
                'country' => $order->getBillingAddress()->getCountry(),
                'phone_number' => $order->getBillingAddress()->getTelephone(),
                'magento_account_created' => $order->getCreatedAt(),
                'magento_customer_group' => 'Guest',
                'magento_store' => $order->getStoreId(),
                'accepts_marketing' => 'no',
            ),
        );
    }

    /**
     * @param $genderCode
     *
     * @return string
     */
    public function getGenderText($genderCode)
    {
        if ($genderCode == 1) {
            $gender = 'Male';
        } else if ($genderCode == 2) {
            $gender = 'Female';
        } else {
            $gender = '';
        }

        return $gender;
    }

    /**
     * get request area
     *
     * @return string
     */
    public function getArea()
    {
        if (stripos(Mage::app()->getRequest()->getRequestUri(), "/api/") === 0) {
            return 'API';
        }

        if (Mage::app()->getStore()->isAdmin()) {
            return 'Admin';
        }

        if (Mage::getDesign()->getArea() == 'adminhtml') {
            return 'Admin';
        }

        return 'Storefront';
    }

    /**
     * @param $product
     * Return comma separated string of category names this product is assigned to
     * @return string
     */
    public function getProductCategoryNames($product)
    {
        $catIds = $product->getCategoryIds();
        $categoriesString = '';
        $numCategories = count($catIds);
        if ($numCategories) {
            $catCollection = Mage::getResourceModel('catalog/category_collection')
                ->addAttributeToSelect('name')
                ->addAttributeToFilter('entity_id', $catIds);

            foreach ($catCollection as $category) {
                $categoriesString .= $category->getName() . ', ';
            }

            $categoriesString = substr($categoriesString, 0, -2);
        }

        return $categoriesString;
    }

    /**
     * @param $price
     * consistently format prices as cents
     * strip all except numbers and periods
     *
     * @return int
     */
    public function priceAsCents($price)
    {
        if (empty($price)) {
            return 0;
        }

        return (int) (preg_replace("/[^0-9.]/", "", $price) * 100);
    }

    /**
     * @param string $date
     */
    public function formatDate($date)
    {
        $time = new DateTime($date);
        return $time->format("Y-m-d\TH:i:s\Z");
    }

    /**
     * return salt value
     *
     * @return string
     */
    protected function getSalt()
    {
        $salt = Drip_Connect_Model_Configuration::forGlobalScope()->getSalt();
        if (empty(trim($salt))) {
            $salt = self::SALT;
        }

        return $salt;
    }

    /**
     * @param int $quoteId
     * @param int $storeId
     *
     * @return string
     */
    public function getSecureKey($quoteId, $storeId)
    {
        return (substr(hash('sha256', $this->getSalt().$quoteId.$storeId), 0, 32));
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return string
     */
    public function getAbandonedCartUrl($quote)
    {
        return Mage::getUrl(
            'drip/cart/index',
            array(
                self::QUOTE_KEY => $quote->getId(),
                self::STORE_KEY => $quote->getStoreId(),
                self::SECURE_KEY => $this->getSecureKey($quote->getId(), $quote->getStoreId()),
            )
        );
    }

    /**
     * get store id which is currently being edited
     *
     * @return int
     */
    public function getAdminEditStoreId()
    {
        $storeId = Mage::app()->getRequest()->getParam('store');

        if (empty($storeId)) {
            $storeId = 0;
        }

        return $storeId;
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    public function isEmailValid($email)
    {
        return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * get version
     *
     * @return string
     */
    public function getVersion()
    {
        return 'Magento ' . Mage::getVersion() . ', '
                 . 'Drip Extension ' . Mage::getConfig()->getModuleConfig('Drip_Connect')->version;
    }
}
