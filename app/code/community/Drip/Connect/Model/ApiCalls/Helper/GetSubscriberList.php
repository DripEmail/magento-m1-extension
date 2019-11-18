<?php

// TODO: This class doesn't seem to be called from anywhere. Confirm that it is dead.

class Drip_Connect_Model_ApiCalls_Helper_GetSubscriberList
    extends Drip_Connect_Model_ApiCalls_Helper
{
    /**
     * @param Drip_Connect_Model_Configuration $config
     * @param array $data
     */
    public function __construct(Drip_Connect_Model_Configuration $config, array $data)
    {
        $data = array_merge(
            array(
                'initial_status' => '',
                'tags' => '',
                'subscribed_before' => '',
                'subscribed_after' => '',
                'page' => '',
                'per_page' => '',
            ),
            $data
        );

        $this->apiClient = new Drip_Connect_Model_ApiCalls_Base($config, $config->getAccountId().'/'.self::ENDPOINT_SUBSCRIBERS);

        $this->request = Mage::getModel('drip_connect/ApiCalls_Request_Base')
            ->setMethod(Zend_Http_Client::GET)
            ->setParametersGet(
                array(
                    'initial_status' => $data['initial_status'],
                    'tags' => $data['tags'],
                    'subscribed_before' => $data['subscribed_before'],
                    'subscribed_after' => $data['subscribed_after'],
                    'page' => $data['page'],
                    'per_page' => $data['per_page'],
                )
            );
    }
}

