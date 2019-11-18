<?php

abstract class Drip_Connect_Model_ApiCalls_Helper
{
    const PROVIDER_NAME = 'magento';
    const ENDPOINT_ACCOUNTS = 'accounts';
    const ENDPOINT_SUBSCRIBERS = 'subscribers';
    const ENDPOINT_EVENTS = 'events';
    const ENDPOINT_ORDERS = 'shopper_activity/order';
    const ENDPOINT_REFUNDS = 'refunds';
    const ENDPOINT_CART = 'shopper_activity/cart';
    const ENDPOINT_PRODUCT = 'shopper_activity/product';
    const ENDPOINT_BATCH_SUBSCRIBERS = 'subscribers/batches';
    const ENDPOINT_BATCH_ORDERS = 'shopper_activity/order/batch';
    const ENDPOINT_BATCH_EVENTS = 'events/batches';

    const MAX_BATCH_SIZE = 1000;

    /** @var Drip_Connect_Model_ApiCalls_Base */
    protected $apiClient;

    /** @var Drip_Connect_Model_ApiCalls_Request_Base */
    protected $request;

    /**
     * call api
     *
     * @return Drip_Connect_Model_ApiCalls_Response_Base
     */
    public function call()
    {
        return $this->apiClient->callApi($this->request);
    }
}
