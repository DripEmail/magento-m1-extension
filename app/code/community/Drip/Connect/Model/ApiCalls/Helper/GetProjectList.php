<?php

// TODO: This class doesn't seem to be called from anywhere. Confirm that it is dead.

class Drip_Connect_Model_ApiCalls_Helper_GetProjectList
    extends Drip_Connect_Model_ApiCalls_Helper
{
    /**
     * @param Drip_Connect_Model_Configuration $config
     */
    public function __construct(Drip_Connect_Model_Configuration $config)
    {
        $this->apiClient = new Drip_Connect_Model_ApiCalls_Base($config, self::ENDPOINT_ACCOUNTS);

        $this->request = Mage::getModel('drip_connect/ApiCalls_Request_Base')
            ->setMethod(Zend_Http_Client::GET);
    }
}

