<?php

class Drip_Connect_Model_ApiCalls_Helper_CreateUpdateOrder
    extends Drip_Connect_Model_ApiCalls_Helper
{
    const PROVIDER_NAME = 'magento';

    const ACTION_NEW = 'placed';
    const ACTION_CHANGE = 'updated';
    const ACTION_PAID = 'paid'; // not used?
    const ACTION_FULFILL = 'fulfilled';
    const ACTION_REFUND = 'refunded';
    const ACTION_CANCEL = 'canceled';

    /**
     * @param Drip_Connect_Model_Configuration $config
     * @param array $data
     */
    public function __construct(Drip_Connect_Model_Configuration $config, array $data)
    {
        $this->apiClient = new Drip_Connect_Model_ApiCalls_Base($config, $config->getAccountId().'/'.self::ENDPOINT_ORDERS, true);

        if (!empty($data) && is_array($data)) {
            $data['version'] = Mage::helper('drip_connect')->getVersion();
        }

        $this->request = Mage::getModel('drip_connect/ApiCalls_Request_Base')
            ->setMethod(Zend_Http_Client::POST)
            ->setRawData(json_encode($data));
    }
}
