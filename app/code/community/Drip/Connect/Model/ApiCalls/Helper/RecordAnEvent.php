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
    const EVENT_WISHLIST_ADD_PRODUCT = 'Added item to wishlist';
    const EVENT_WISHLIST_REMOVE_PRODUCT = 'Removed item from wishlist';

    /**
     * @param Drip_Connect_Model_Configuration $config
     * @param array $data
     */
    public function __construct(Drip_Connect_Model_Configuration $config, array $data)
    {
        $this->apiClient = new Drip_Connect_Model_ApiCalls_Base($config, $config->getAccountId().'/'.self::ENDPOINT_EVENTS);

        if (!empty($data) && is_array($data)) {
            $data['properties']['source'] = 'magento';
            $data['properties']['magento_source'] = Mage::helper('drip_connect')->getArea();
            $data['properties']['version'] = 'Magento ' . Mage::getVersion() . ', '
                                           . 'Drip Extension '
                                           . Mage::getConfig()->getModuleConfig('Drip_Connect')->version;
        }

        $eventInfo = array(
            'events' => array(
                $data
            )
        );
        $this->request = Mage::getModel('drip_connect/ApiCalls_Request_Base')
            ->setMethod(Zend_Http_Client::POST)
            ->setRawData(json_encode($eventInfo));
    }
}
