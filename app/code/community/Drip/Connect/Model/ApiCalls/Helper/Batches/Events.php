<?php

class Drip_Connect_Model_ApiCalls_Helper_Batches_Events
    extends Drip_Connect_Model_ApiCalls_Helper
{
    /**
     * @param Drip_Connect_Model_Configuration $config
     * @param array $batch
     */
    public function __construct(Drip_Connect_Model_Configuration $config, array $batch)
    {
        $this->apiClient = new Drip_Connect_Model_ApiCalls_Base($config, $config->getAccountId().'/'.self::ENDPOINT_BATCH_EVENTS);

        $eventsInfo = array(
            'events' => $batch
        );
        $batchesInfo = array(
            'batches' => array(
                $eventsInfo
            )
        );

        $this->request = Mage::getModel('drip_connect/ApiCalls_Request_Base')
            ->setMethod(Zend_Http_Client::POST)
            ->setRawData(json_encode($batchesInfo));
    }
}
