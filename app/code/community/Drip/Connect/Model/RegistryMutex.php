<?php

/**
 * A simple mutex using the Magento registry.
 *
 * This is obviously very not thread-safe and is intended to be used in the
 * context of an observer.
 */
class Drip_Connect_Model_RegistryMutex
{
    // A couple of defined mutexes.
    const QUOTE_OBSERVER_MUTEX_KEY = 'dripquoteobservermutex';

    /**
     * @var string The registry key.
     */
    protected $key;

    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * Check whether the mutex is set and obtain it if not.
     *
     * @return bool Whether we have successfully obtained the lock.
     */
    public function checkAndSet()
    {
        $res = $this->checkAvailable();
        if ($res) {
            Mage::register($this->key, 'obtained');
        }
        return $res;
    }

    /**
     * Check whether the mutex is set.
     *
     * @return bool True if the lock is available.
     */
    public function checkAvailable()
    {
        return Mage::registry($this->key) === null;
    }

    /**
     * Release the mutex lock.
     */
    public function release()
    {
        Mage::unregister($this->key);
    }
}
