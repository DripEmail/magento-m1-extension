<?php

class Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent
    extends Drip_Connect_Model_ApiCalls_Helper
{
    const EVENT_CUSTOMER_NEW = 'Customer created';
    const EVENT_CUSTOMER_UPDATED = 'Customer updated';
    const EVENT_CUSTOMER_DELETED = 'Customer deleted';
    const EVENT_CUSTOMER_LOGIN = 'Customer logged in';
    const EVENT_ORDER_CREATED = 'Order created';
    const EVENT_ORDER_COMPLETED = 'Order fulfilled';
    const EVENT_ORDER_REFUNDED = 'Order refunded';
    const EVENT_ORDER_CANCELED = 'Order canceled';
    const EVENT_QUOTE_NEW = 'Checkout created';
    const EVENT_QUOTE_CHANGED = 'Checkout updated';
    const EVENT_WISHLIST_ADD_PRODUCT = 'Added item to wishlist';

    public function __construct($data = null)
    {
        $this->apiClient = Mage::getModel('drip_connect/ApiCalls_Base', array(
            'endpoint' => Mage::getStoreConfig('dripconnect_general/api_settings/account_id').'/'.self::ENDPOINT_EVENTS,
        ));

        if (!empty($data) && is_array($data)) {
            $data['properties']['source'] = 'magento';
            $data['properties']['magento_source'] = Mage::helper('drip_connect')->getArea();
        }

        $eventInfo = [
            'events' => [
                $data
            ]
        ];
        $this->request = Mage::getModel('drip_connect/ApiCalls_Request_Base')
            ->setMethod(Zend_Http_Client::POST)
            ->setRawData(json_encode($eventInfo));
    }
}

