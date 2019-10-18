<?php

class Drip_Connect_Model_ApiCalls_Helper_CreateUpdateProduct
    extends Drip_Connect_Model_ApiCalls_Helper
{
    const PRODUCT_NEW = 'created';
    const PRODUCT_CHANGED = 'updated';
    const PRODUCT_DELETED = 'deleted';

    public function __construct($data = null)
    {
        $this->apiClient = Mage::getModel(
            'drip_connect/ApiCalls_Base',
            array(
                'endpoint' => Mage::getStoreConfig(
                    'dripconnect_general/api_settings/account_id'
                ).'/'.self::ENDPOINT_PRODUCT,
                'v3' => true,
            )
        );

        if (!empty($data) && is_array($data)) {
            $data['version'] = 'Magento ' . Mage::getVersion() . ', '
                             . 'Drip Extension ' . Mage::getConfig()->getModuleConfig('Drip_Connect')->version;
        }

        $this->request = Mage::getModel('drip_connect/ApiCalls_Request_Base')
            ->setMethod(Zend_Http_Client::POST)
            ->setRawData(json_encode($data));
    }
}


