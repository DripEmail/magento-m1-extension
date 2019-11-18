<?php

class Drip_Connect_Model_Configuration
{
    /**
     * @var string|null The store's ID. If null, try to infer from context.
     */
    protected $storeId;

    /**
     * Obtains configuration scoped to the global or default installation config.
     *
     * @return self
     */
    public static function forGlobalScope()
    {
        return new self(0);
    }

    /**
     * Obtains configuration scoped to the current store based on the request param.
     *
     * @todo This might be a functional dup of forCurrentScope()
     * @return self
     */
    public static function forCurrentStoreParam()
    {
        return new self(Mage::app()->getRequest()->getParam('store'));
    }

    /**
     * Obtains configuration scoped to the current store.
     *
     * Only useful when in a store view scope. E.g. this doesn't work in the admin.
     *
     * @return self
     */
    public static function forCurrentScope()
    {
        return new self(Mage::app()->getStore()->getId());
    }

    /**
     * @param int $storeId The ID of the Store View (called `store` in the DB and code)
     */
    public function __construct($storeId = null)
    {
        $this->storeId = $storeId;
    }

    public function getStoreId()
    {
        return $this->storeId;
    }

    public function getAccountId()
    {
        return $this->getStoreConfig('dripconnect_general/api_settings/account_id');
    }

    public function getBehavior()
    {
        return $this->getStoreConfig('dripconnect_general/api_settings/behavior');
    }

    public function getUrl()
    {
        return $this->getStoreConfig('dripconnect_general/api_settings/url');
    }

    public function getTimeout()
    {
        return $this->getStoreConfig('dripconnect_general/api_settings/timeout');
    }

    public function getApiKey()
    {
        return $this->getStoreConfig('dripconnect_general/api_settings/api_key');
    }

    public function getCustomersSyncState()
    {
        return $this->getStoreConfig('dripconnect_general/actions/sync_customers_data_state');
    }

    public function getOrdersSyncState()
    {
        return $this->getStoreConfig('dripconnect_general/actions/sync_orders_data_state');
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)$this->getStoreConfig('dripconnect_general/module_settings/is_enabled');
    }

    public function getSalt()
    {
        return $this->getStoreConfig('dripconnect_general/module_settings/salt');
    }

    public function getLogSettings()
    {
        return $this->getStoreConfig('dripconnect_general/log_settings');
    }

    public function getMemoryLimit()
    {
        return $this->getStoreConfig('dripconnect_general/api_settings/memory_limit');
    }

    public function getBatchDelay()
    {
        return (int) $this->getStoreConfig('dripconnect_general/api_settings/batch_delay');
    }

    protected function getStoreConfig($path)
    {
        return Mage::getStoreConfig($path, $this->storeId);
    }
}
