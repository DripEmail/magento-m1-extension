<?php

// Mage::helper('drip_connect/logger')->logger()

class Drip_Connect_Helper_Logger extends Mage_Core_Helper_Abstract
{
    /** @var Zend_Log */
    protected $_logger;

    /** @var string */
    protected $_logSettingsXpath = 'dripconnect_general/log_settings';

    /** @var string */
    protected $_logFilename = 'drip.log';

    protected function getLogSettings()
    {
        $settings = new Varien_Object();
        // Using Store ID 0 so we can only turn on and off logging globally.
        $settings->setData(Mage::getStoreConfig($this->_logSettingsXpath, 0));
        return $settings;
    }

    protected function initLogger() {
        $logger = new Zend_Log();
        if ($this->getLogSettings()->getIsEnabled()) {
            $logFile = Mage::getBaseDir('log') . DS . $this->_logFilename;
            $writer = new Zend_Log_Writer_Stream($logFile);
        } else {
            $writer = new Zend_Log_Writer_Null();
        }
        $logger->addWriter($writer);
        $this->_logger = $logger;
    }

    /**
     * get Drip logger
     *
     * @return Zend_Log
     */
    public function logger()
    {
        if (!$this->_logger) {
            $this->initLogger();
        }
        return $this->_logger;
    }
}
