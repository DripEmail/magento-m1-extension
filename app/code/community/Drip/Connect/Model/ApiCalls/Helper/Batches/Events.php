<?php

class Drip_Connect_Model_ApiCalls_Helper_Batches_Events
    extends Drip_Connect_Model_ApiCalls_Helper
{
    public function __construct($data = null)
    {
        $storeId = (int) $data['store_id'];
        $accountId = Mage::getStoreConfig('dripconnect_general/api_settings/account_id', $storeId);

        $this->apiClient = Mage::getModel(
            'drip_connect/ApiCalls_Base',
            array(
                'endpoint' => $accountId.'/'.self::ENDPOINT_BATCH_EVENTS,
                'store_id' => $storeId,
            )
        );

        $eventsInfo = array(
            'events' => $data['batch']
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


