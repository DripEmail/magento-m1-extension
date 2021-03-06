<?php

class Drip_Connect_Model_ApiCalls_Helper_CreateUpdateQuote
    extends Drip_Connect_Model_ApiCalls_Helper
{
    const PROVIDER_NAME = 'magento';
    const QUOTE_NEW = 'created';
    const QUOTE_CHANGED = 'updated';

    /**
     * @param Drip_Connect_Model_Configuration $config
     * @param array $data
     */
    public function __construct(Drip_Connect_Model_Configuration $config, array $data)
    {
        $this->apiClient = new Drip_Connect_Model_ApiCalls_Base($config, $config->getAccountId().'/'.self::ENDPOINT_CART, true);

        if (!empty($data) && is_array($data)) {
            $data['version'] = Mage::helper('drip_connect')->getVersion();
        }

        $this->request = Mage::getModel('drip_connect/ApiCalls_Request_Base')
            ->setMethod(Zend_Http_Client::POST)
            ->setRawData(json_encode($data));
    }
}


