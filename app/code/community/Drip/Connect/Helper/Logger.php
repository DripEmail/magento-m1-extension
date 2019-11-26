<?php

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
        $config = Drip_Connect_Model_Configuration::forGlobalScope();
        $settings->setData($config->getLogSettings());
        return $settings;
    }

    protected function initLogger()
    {
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
