<?php

abstract class Drip_Connect_Model_ApiCalls_Helper
{
    const ENDPOINT_ACCOUNTS = 'accounts';
    const ENDPOINT_SUBSCRIBERS = 'subscribers';
    const ENDPOINT_SUBSCRIBERS_UNSUBSCRIBE_ALL = 'unsubscribe_all';
    const ENDPOINT_EVENTS = 'events';
    const ENDPOINT_ORDERS = 'shopper_activity/order';
    const ENDPOINT_REFUNDS = 'refunds';
    const ENDPOINT_CART = 'shopper_activity/cart';
    const ENDPOINT_BATCH_SUBSCRIBERS = 'subscribers/batches';
    const ENDPOINT_BATCH_ORDERS = 'shopper_activity/order/batch';
    const ENDPOINT_BATCH_EVENTS = 'events/batches';

    const MAX_BATCH_SIZE = 1000;

    /** @var Drip_Connect_Model_ApiCalls_Base */
    protected $apiClient;

    /** @var Drip_Connect_Model_ApiCalls_Request_Base */
    protected $request;

    /**
     * must be implemented individually for every call helper
     *
     * two classes should be instantiated in every constructor: ApiClient and Request
     */
    abstract public function __construct($data);

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
