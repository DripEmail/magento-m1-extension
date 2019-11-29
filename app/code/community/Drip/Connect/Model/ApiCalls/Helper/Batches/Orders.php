<?php

class Drip_Connect_Model_ApiCalls_Helper_Batches_Orders
    extends Drip_Connect_Model_ApiCalls_Helper
{
    /**
     * @param Drip_Connect_Model_Configuration $config
     * @param array $batch
     */
    public function __construct(Drip_Connect_Model_Configuration $config, array $batch)
    {
        $this->apiClient = new Drip_Connect_Model_ApiCalls_Base($config, $config->getAccountId().'/'.self::ENDPOINT_BATCH_ORDERS, true);

        $ordersInfo = array(
            'orders' => $batch
        );

        $this->request = Mage::getModel('drip_connect/ApiCalls_Request_Base')
            ->setMethod(Zend_Http_Client::POST)
            ->setRawData(json_encode($ordersInfo));
    }
}
