<?php

class Drip_Connect_Model_ApiCalls_Helper_CreateUpdateQuote
    extends Drip_Connect_Model_ApiCalls_Helper
{
    const PROVIDER_NAME = 'magento';
    const QUOTE_NEW = 'created';
    const QUOTE_CHANGED = 'updated';

    public function __construct($data = null)
    {
        // TODO: Pass this in from caller.
        $config = Drip_Connect_Model_Configuration::forCurrentScope();

        $this->apiClient = new Drip_Connect_Model_ApiCalls_Base($config, $config->getAccountId().'/'.self::ENDPOINT_CART, true);

        if (!empty($data) && is_array($data)) {
            $data['version'] = 'Magento ' . Mage::getVersion() . ', '
                             . 'Drip Extension ' . Mage::getConfig()->getModuleConfig('Drip_Connect')->version;
        }

        $this->request = Mage::getModel('drip_connect/ApiCalls_Request_Base')
            ->setMethod(Zend_Http_Client::POST)
            ->setRawData(json_encode($data));
    }
}


