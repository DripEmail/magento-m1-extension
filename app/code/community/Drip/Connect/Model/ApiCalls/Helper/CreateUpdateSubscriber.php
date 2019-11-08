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
        $this->apiClient = Mage::getModel(
            'drip_connect/ApiCalls_Base',
            array(
                'endpoint' => $config->getAccountId().'/'.self::ENDPOINT_SUBSCRIBERS,
                // TODO: Figure out why we pass the store ID and try to just pass the config object instead.
                'store_id' => $config->getStoreId(),
            )
        );

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
