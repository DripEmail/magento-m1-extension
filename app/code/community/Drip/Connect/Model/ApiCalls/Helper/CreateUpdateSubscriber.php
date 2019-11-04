<?php

class Drip_Connect_Model_ApiCalls_Helper_CreateUpdateSubscriber
    extends Drip_Connect_Model_ApiCalls_Helper
{
    public function __construct($params = null)
    {
        $store = null;
        if (array_key_exists('store', $params)) {
            $store = $params['store'];
            unset($params['store']);
        }

        $this->apiClient = Mage::getModel(
            'drip_connect/ApiCalls_Base',
            array(
                'endpoint' => Mage::getStoreConfig(
                    'dripconnect_general/api_settings/account_id',
                    $store
                ).'/'.self::ENDPOINT_SUBSCRIBERS,
                'store_id' => $store,
            )
        );

        $subscriberInfo = array(
            'subscribers' => array(
                $params['data']
            )
        );
        $this->request = Mage::getModel('drip_connect/ApiCalls_Request_Base')
            ->setMethod(Zend_Http_Client::POST)
            ->setRawData(json_encode($subscriberInfo));
    }
}


