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
        $gender = $customer->getGender();
        if ($gender == 1) {
            $gender = 'Male';
        } else if ($gender == 2) {
            $gender = 'Female';
        } else {
            $gender = '';
        }
        $data = array (
            'email' => $customer->getEmail(),
            'new_email' => ($newEmail ? $newEmail : ''),
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
        );

        if ($updatableOnly) {
            unset($data['custom_fields']['magento_account_created']);
            unset($data['ip_address']);
        }

        return $data;
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
     * @param $price
     *
     * @return int
     */
    public function formatPriceCents($price) {
        $price = number_format($price, 2, '.', '');
        return ($price*100);
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
     * consistently format prices
     * @return string
     */
    public function formatPrice($price) {
        return number_format($price, 2, '.', ',');
    }
}
