<?php

class Drip_Connect_Model_ApiCalls_Helper_CreateUpdateQuote
    extends Drip_Connect_Model_ApiCalls_Helper
{
    const PROVIDER_NAME = 'magento';
    const QUOTE_NEW = 'created';
    const QUOTE_CHANGED = 'updated';

    public function __construct($data = null)
    {
        $this->apiClient = Mage::getModel('drip_connect/ApiCalls_Base', array(
            'endpoint' => Mage::getStoreConfig('dripconnect_general/api_settings/account_id').'/'.self::ENDPOINT_CART,
            'v3' => true,
        ));

        $this->request = Mage::getModel('drip_connect/ApiCalls_Request_Base')
            ->setMethod(Zend_Http_Client::POST)
            ->setRawData(json_encode($data));
    }
}


