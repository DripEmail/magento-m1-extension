<?php

class Drip_Connect_Helper_Data extends Mage_Core_Helper_Abstract
{
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
        if (stripos(Mage::app()->getRequest()->getRequestUri(), "index.php/api")) {
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
}
