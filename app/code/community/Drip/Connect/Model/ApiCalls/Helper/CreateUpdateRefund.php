<?php

class Drip_Connect_Model_ApiCalls_Helper_CreateUpdateRefund
    extends Drip_Connect_Model_ApiCalls_Helper
{
    const PROVIDER_NAME = 'magento';

    public function __construct($data = null)
    {
        $this->apiClient = Mage::getModel(
            'drip_connect/ApiCalls_Base',
            array(
                'endpoint' => Mage::getStoreConfig(
                    'dripconnect_general/api_settings/account_id'
                ).'/'.self::ENDPOINT_REFUNDS,
            )
        );

        $ordersInfo = array(
            'refunds' => array(
                $data
            )
        );
        $this->request = Mage::getModel('drip_connect/ApiCalls_Request_Base')
            ->setMethod(Zend_Http_Client::POST)
            ->setRawData(json_encode($ordersInfo));
    }
}


