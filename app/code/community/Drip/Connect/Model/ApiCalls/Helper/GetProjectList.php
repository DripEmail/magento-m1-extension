<?php

class Drip_Connect_Model_ApiCalls_Helper_GetProjectList
    extends Drip_Connect_Model_ApiCalls_Helper
{
    public function __construct($data = null)
    {
        $this->apiClient = Mage::getModel('drip_connect/ApiCalls_Base', array(
            'endpoint' => self::ENDPOINT_ACCOUNTS,
        ));

        $this->request = Mage::getModel('drip_connect/ApiCalls_Request_Base')
            ->setMethod(Zend_Http_Client::GET);
    }
}

