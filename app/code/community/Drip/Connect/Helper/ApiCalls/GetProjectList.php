<?php

class Drip_Connect_Helper_ApiCalls_GetProjectList
    extends Drip_Connect_Helper_ApiCalls_Base
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

