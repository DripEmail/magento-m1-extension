<?php

class Drip_Connect_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * check if module active
     *
     * @return bool
     */
    public function isModuleActive()
    {
        if (!empty(Mage::app()->getRequest()->getParam('store'))) {
            return (bool)Mage::getStoreConfig('dripconnect_general/module_settings/is_enabled', Mage::app()->getRequest()->getParam('store'));
        }

        return (bool)Mage::getStoreConfig('dripconnect_general/module_settings/is_enabled');
    }

    /**
     * prepare array of guest subscriber data
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @param bool $updatableOnly leave only those fields which are used in update action
     *
     * @return array
     */
    static public function prepareGuestSubscriberData($subscriber, $updatableOnly = true)
    {
        if ($subscriber->getSubscriberStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
            $acceptsMarketing = 'yes';
        } else {
            $acceptsMarketing = 'no';
        }

        $data = array (
            'email' => $subscriber->getSubscriberEmail(),
            'ip_address' => Mage::helper('core/http')->getRemoteAddr(),
            'custom_fields' => array(
                'accepts_marketing' => $acceptsMarketing,
            ),
        );

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
     */
    static public function prepareCustomerData($customer, $updatableOnly = true)
    {
        if ($customer->getOrigData() && $customer->getData('email') != $customer->getOrigData('email')) {
            $newEmail = $customer->getData('email');
        } else {
            $newEmail = '';
        }
        $data = array (
            'email' => $customer->getEmail(),
            'new_email' => ($newEmail ? $newEmail : ''),
            'ip_address' => Mage::helper('core/http')->getRemoteAddr(),
            'custom_fields' => array(
                'first_name' => $customer->getFirstname(),
                'last_name' => $customer->getLastname(),
                'birthday' => $customer->getDob(),
                'gender' => Mage::helper('drip_connect')->getGenderText($customer->getGender()),
                'magento_account_created' => $customer->getCreatedAt(),
                'magento_customer_group' => Mage::getModel('customer/group')->load($customer->getGroupId())->getCustomerGroupCode(),
                'magento_store' => $customer->getStoreId(),
                'accepts_marketing' => ($customer->getIsSubscribed() ? 'yes' : 'no'),
            ),
        );

        if ($updatableOnly) {
            unset($data['custom_fields']['magento_account_created']);
            unset($data['ip_address']);
        }

        return $data;
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
     * @param $order
     *
     * @return array
     */
    public function prepareCustomerDataForGuestCheckout($order) {

        return array (
            'email' => $order->getCustomerEmail(),
            'ip_address' => Mage::helper('core/http')->getRemoteAddr(),
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
    public function getGenderText($genderCode) {
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
    public function getProductCategoryNames($product) {
        $catIds = $product->getCategoryIds();
        $categoriesString = '';
        $numCategories = count($catIds);
        if($numCategories) {
            $catCollection = Mage::getResourceModel('catalog/category_collection')
                ->addAttributeToSelect('name')
                ->addAttributeToFilter('entity_id', $catIds);

            foreach($catCollection as $category) {
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
     * @return string
     */
    public function priceAsCents($price) {
        if (empty($price)) {
            return '';
        }

        return (int) (preg_replace("/[^0-9.]/", "", $price) * 100);
    }

    /**
     * @param int $storeId
     * @param int $state
     */
    public function setCustomersSyncStateToStore($storeId, $state)
    {
        if (empty($storeId)) {
            Mage::getConfig()->saveConfig(
                'dripconnect_general/actions/sync_customers_data_state',
                $state
            );
            $storeId = null;
        } else {
            Mage::getConfig()->saveConfig(
                'dripconnect_general/actions/sync_customers_data_state',
                $state,
                'stores',
                $storeId
            );
        }
        Mage::app()->getStore($storeId)->resetConfig();
    }

    /**
     * @param int $storeId
     * @param int $state
     */
    public function setOrdersSyncStateToStore($storeId, $state)
    {
        if (empty($storeId)) {
            Mage::getConfig()->saveConfig(
                'dripconnect_general/actions/sync_orders_data_state',
                $state
            );
            $storeId = null;
        } else {
            Mage::getConfig()->saveConfig(
                'dripconnect_general/actions/sync_orders_data_state',
                $state,
                'stores',
                $storeId
            );
        }
        Mage::app()->getStore($storeId)->resetConfig();
    }
}
