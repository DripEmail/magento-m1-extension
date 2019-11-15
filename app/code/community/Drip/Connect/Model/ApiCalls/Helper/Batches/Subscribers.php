<?php

class Drip_Connect_Model_ApiCalls_Helper_Batches_Subscribers
    extends Drip_Connect_Model_ApiCalls_Helper
{
    public function __construct($data = null)
    {
        // TODO: Pass config in instead of store id.
        $config = new Drip_Connect_Model_Configuration((int) $data['store_id']);

        $this->apiClient = new Drip_Connect_Model_ApiCalls_Base($config, $config->getAccountId().'/'.self::ENDPOINT_BATCH_SUBSCRIBERS);

        $subscribersInfo = array(
            'subscribers' => $data['batch']
        );
        $batchesInfo = array(
            'batches' => array(
                $subscribersInfo
            )
        );

        $this->request = Mage::getModel('drip_connect/ApiCalls_Request_Base')
            ->setMethod(Zend_Http_Client::POST)
            ->setRawData(json_encode($batchesInfo));
    }
}


