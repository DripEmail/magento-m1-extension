<?php

class Drip_Connect_Model_ApiCalls_Helper_GetSubscriberList
    extends Drip_Connect_Model_ApiCalls_Helper
{
    public function __construct($data)
    {
        $data = array_merge(array(
            'status' => '',
            'tags' => '',
            'subscribed_before' => '',
            'subscribed_after' => '',
            'page' => '',
            'per_page' => '',
        ), $data);

        $this->apiClient = Mage::getModel('drip_connect/ApiCalls_Base', array(
            'endpoint' => Mage::getStoreConfig('dripconnect_general/api_settings/account_id').'/'.self::ENDPOINT_SUBSCRIBERS,
        ));

        $this->request = Mage::getModel('drip_connect/ApiCalls_Request_Base')
            ->setMethod(Zend_Http_Client::GET)
            ->setParametersGet(array(
                'status' => $data['status'],
                'tags' => $data['tags'],
                'subscribed_before' => $data['subscribed_before'],
                'subscribed_after' => $data['subscribed_after'],
                'page' => $data['page'],
                'per_page' => $data['per_page'],
            ));
    }
}

