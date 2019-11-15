<?php

class Drip_Connect_Model_ApiCalls_Helper_CreateUpdateRefund
    extends Drip_Connect_Model_ApiCalls_Helper
{
    const PROVIDER_NAME = 'magento';

    public function __construct($data = null)
    {
        // TODO: Pass this in from caller.
        $config = Drip_Connect_Model_Configuration::forCurrentScope();

        $this->apiClient = new Drip_Connect_Model_ApiCalls_Base($config, $config->getAccountId().'/'.self::ENDPOINT_REFUNDS);

        $ordersInfo = array(
            'refunds' => array(
                $data
            )
        );
        $this->request = Mage::getModel('drip_connect/ApiCalls_Request_Base')
            ->setMethod(Zend_Http_Client::POST)
            ->setRawData(json_encode($ordersInfo));
    }
}


