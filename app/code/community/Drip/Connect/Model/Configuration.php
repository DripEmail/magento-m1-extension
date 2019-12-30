<?php

class Drip_Connect_Model_Configuration
{
    const ACCOUNT_ID_PATH = 'dripconnect_general/api_settings/account_id';
    const BEHAVIOR_PATH = 'dripconnect_general/api_settings/behavior';
    const API_URL_PATH = 'dripconnect_general/api_settings/url';
    const API_TIMEOUT_PATH = 'dripconnect_general/api_settings/timeout';
    const API_KEY_PATH = 'dripconnect_general/api_settings/api_key';
    const CUSTOMER_DATA_STATE_PATH = 'dripconnect_general/actions/sync_customers_data_state';
    const ORDER_DATA_STATE_PATH = 'dripconnect_general/actions/sync_orders_data_state';
    const MODULE_ENABLED_PATH = 'dripconnect_general/module_settings/is_enabled';
    const SALT_PATH = 'dripconnect_general/module_settings/salt';
    const LOG_SETTINGS_PATH = 'dripconnect_general/log_settings';
    const MEMORY_LIMIT_PATH = 'dripconnect_general/api_settings/memory_limit';
    const BATCH_DELAY_PATH = 'dripconnect_general/api_settings/batch_delay';

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

    /**
     * The website ID attached to the current store view.
     *
     * @returns int
     */
    public function getWebsiteId()
    {
        return Mage::app()->getStore($this->storeId)->getWebsiteId();
    }

    public function getAccountId()
    {
        return $this->getStoreConfig(self::ACCOUNT_ID_PATH);
    }

    public function getBehavior()
    {
        return $this->getStoreConfig(self::BEHAVIOR_PATH);
    }

    public function getUrl()
    {
        return $this->getStoreConfig(self::API_URL_PATH);
    }

    public function getTimeout()
    {
        return $this->getStoreConfig(self::API_TIMEOUT_PATH);
    }

    public function getApiKey()
    {
        return $this->getStoreConfig(self::API_KEY_PATH);
    }

    public function getCustomersSyncState()
    {
        return $this->getStoreConfig(self::CUSTOMER_DATA_STATE_PATH);
    }

    /**
     * @param int $state
     */
    public function setCustomersSyncState($state)
    {
        $this->setStoreConfig(self::CUSTOMER_DATA_STATE_PATH, $state);
    }

    public function getOrdersSyncState()
    {
        return $this->getStoreConfig(self::ORDER_DATA_STATE_PATH);
    }

    /**
     * @param int $state
     */
    public function setOrdersSyncState($state)
    {
        $this->setStoreConfig(self::ORDER_DATA_STATE_PATH, $state);
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)$this->getStoreConfig(self::MODULE_ENABLED_PATH);
    }

    public function getSalt()
    {
        return $this->getStoreConfig(self::SALT_PATH);
    }

    public function getLogSettings()
    {
        return $this->getStoreConfig(self::LOG_SETTINGS_PATH);
    }

    public function getMemoryLimit()
    {
        return $this->getStoreConfig(self::MEMORY_LIMIT_PATH);
    }

    public function getBatchDelay()
    {
        return (int) $this->getStoreConfig(self::BATCH_DELAY_PATH);
    }

    /**
     * @param string $path
     */
    protected function getStoreConfig($path)
    {
        return Mage::getStoreConfig($path, $this->storeId);
    }

    /**
     * @param string $path
     * @param mixed $val
     */
    protected function setStoreConfig($path, $val)
    {
        if (empty($this->storeId)) {
            Mage::getConfig()->saveConfig(
                $path,
                $val
            );
        } else {
            Mage::getConfig()->saveConfig(
                $path,
                $val,
                'stores',
                $this->storeId
            );
        }
    }
}
