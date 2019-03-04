<?php

class Drip_Connect_Model_ApiCalls_Helper_UnsubscribeSubscriber
    extends Drip_Connect_Model_ApiCalls_Helper
{
    public function __construct($data = null)
    {
        $email = $data['email'];

        $this->apiClient = Mage::getModel('drip_connect/ApiCalls_Base', array(
            'endpoint' => Mage::getStoreConfig('dripconnect_general/api_settings/account_id')
                .'/'.self::ENDPOINT_SUBSCRIBERS
                .'/'.$email
                .'/'.self::ENDPOINT_SUBSCRIBERS_UNSUBSCRIBE_ALL
        ));

        $this->request = Mage::getModel('drip_connect/ApiCalls_Request_Base')
            ->setMethod(Zend_Http_Client::POST);
    }
}


