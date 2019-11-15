<?php

class Drip_Connect_Model_ApiCalls_Helper_CreateUpdateProduct
    extends Drip_Connect_Model_ApiCalls_Helper
{
    const PRODUCT_NEW = 'created';
    const PRODUCT_CHANGED = 'updated';
    const PRODUCT_DELETED = 'deleted';

    public function __construct($data = null)
    {
        // TODO: Pass this in from caller.
        $config = Drip_Connect_Model_Configuration::forCurrentScope();

        $this->apiClient = new Drip_Connect_Model_ApiCalls_Base($config, $config->getAccountId().'/'.self::ENDPOINT_PRODUCT, true);

        if (!empty($data) && is_array($data)) {
            $data['version'] = 'Magento ' . Mage::getVersion() . ', '
                             . 'Drip Extension ' . Mage::getConfig()->getModuleConfig('Drip_Connect')->version;
        }

        $this->request = Mage::getModel('drip_connect/ApiCalls_Request_Base')
            ->setMethod(Zend_Http_Client::POST)
            ->setRawData(json_encode($data));
    }
}


