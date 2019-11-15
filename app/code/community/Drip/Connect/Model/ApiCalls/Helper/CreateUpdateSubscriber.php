<?php

class Drip_Connect_Model_ApiCalls_Helper_CreateUpdateSubscriber
    extends Drip_Connect_Model_ApiCalls_Helper
{
    /**
     * @param object $data
     * @param Drip_Connect_Model_Configuration $config
     */
    public function __construct($data, Drip_Connect_Model_Configuration $config)
    {
        $this->apiClient = new Drip_Connect_Model_ApiCalls_Base($config, $config->getAccountId().'/'.self::ENDPOINT_SUBSCRIBERS);
        $subscriberInfo = array(
            'subscribers' => array(
                $data
            )
        );
        $this->request = Mage::getModel('drip_connect/ApiCalls_Request_Base')
            ->setMethod(Zend_Http_Client::POST)
            ->setRawData(json_encode($subscriberInfo));
    }
}
