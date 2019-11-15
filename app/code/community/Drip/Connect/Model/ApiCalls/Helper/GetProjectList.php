<?php

class Drip_Connect_Model_ApiCalls_Helper_GetProjectList
    extends Drip_Connect_Model_ApiCalls_Helper
{
    public function __construct($data = null)
    {
        // TODO: Pass this in from caller.
        $config = Drip_Connect_Model_Configuration::forCurrentScope();

        $this->apiClient = new Drip_Connect_Model_ApiCalls_Base($config, self::ENDPOINT_ACCOUNTS);

        $this->request = Mage::getModel('drip_connect/ApiCalls_Request_Base')
            ->setMethod(Zend_Http_Client::GET);
    }
}

