<?php

class Drip_Connect_Model_Configuration
{
    /**
     * @var string|null The store's ID. If null, try to infer from context.
     */
    protected $storeId;

    public static function forGlobalScope()
    {
        return new self(0);
    }

    public static function forCurrentScope()
    {
        return new self(Mage::app()->getStore()->getId());
    }

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
