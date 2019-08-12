<?php
abstract class Drip_Connect_Model_Restapi_Abstract
{
    const USERAGENT = 'Drip Connect M1';

    /** @var string */
    protected $_responseModel;

    /** @var string */
    protected $_logFilename = 'drip.log';

    /**  @var string */
    protected $_behavior;

    /** @var Zend_Http_Client */
    protected $_httpClient;

    /** @var string */
    protected $_lastRequestUrl;

    /** @var string */
    protected $_lastRequest;

    /** @var Zend_Http_Response */
    protected $_lastResponse;

    /** @var string */
    protected $_apiName = 'apiclient';

    /** @var Zend_Log */
    protected $_logger;

    /** @var string */
    protected $_logSettingsXpath = 'dripconnect_general/log_settings';

    /** @var int */
    protected $storeId = 0;

    /**
     * Makes API call and returns response object.
     *
     * @param $request
     * @return Drip_Connect_Model_Restapi_Response_Interface
     */
    public function callApi($request)
    {
        if (!$this->_responseModel) {
            throw new RuntimeException('Response model must be set.');
        }

        try {
            $rawResponse = $this->_callApiWithBehaviorConsidered($request);

            $className = Mage::getConfig()->getModelClassName($this->_responseModel);
            /** @var Drip_Connect_Model_Restapi_Response_Abstract $response */
            $response = new $className($rawResponse);
            return $response;
        } catch (Exception $e) {
            Mage::log(
                $e->__toString(),
                Zend_Log::ERR,
                str_replace(Mage::getBaseDir('log'), '', $this->getLogFile())
            );
            $className = Mage::getConfig()->getModelClassName($this->_responseModel);
            /** @var Drip_Connect_Model_Restapi_Response_Abstract $response */
            $response = new $className(null, $e->getMessage());
            return $response;
        }
    }

    /**
     * @param $request
     *
     * @return Zend_Http_Response
     * @throws Zend_Http_Client_Exception If a timeout occurs
     */
    protected function _callApiWithBehaviorConsidered($request)
    {
        switch ($this->_behavior) {
            case Drip_Connect_Model_Source_Behavior::FORCE_VALID:
                $this->_lastResponse = $this->_forceValidResponse($request);
                break;

            case Drip_Connect_Model_Source_Behavior::FORCE_INVALID:
                $this->_lastResponse = $this->_forceInvalidResponse($request);
                break;

            case Drip_Connect_Model_Source_Behavior::FORCE_TIMEOUT:
                $this->_forceTimeout($request);
                break;

            case Drip_Connect_Model_Source_Behavior::FORCE_ERROR:
                $this->_lastResponse = $this->_forceError($request);
                break;

            case Drip_Connect_Model_Source_Behavior::FORCE_UNKNOWN_ERROR:
                $this->_lastResponse = $this->_forceUnknownResponse($request);
                break;

            case Drip_Connect_Model_Source_Behavior::CALL_API:
            default:
                $this->_lastResponse = $this->_callApi($request);
                break;
        }

        return $this->_lastResponse;
    }

    /**
     * @return string
     */
    public function getLastRequestUrl()
    {
        return $this->_lastRequestUrl;
    }

    /**
     * @return string
     */
    public function getLastRequest()
    {
        return $this->_lastRequest;
    }

    /**
     * @return Zend_Http_Response
     */
    public function getLastResponse()
    {
        return $this->_lastResponse;
    }

    /**
     * @param int $storeId
     */
    protected function setStoreId(int $storeId)
    {
        $this->storeId = $storeId;
    }

    /**
     * Call the API
     *
     * @param $request
     * @throws Zend_Http_Client_Exception
     */
    abstract protected function _callApi($request);

    /**
     * Force a valid response
     *
     * @param $request
     * @throws Zend_Http_Client_Exception
     */
    abstract protected function _forceValidResponse($request);

    /**
     * Force an invalid response
     *
     * @param $request
     * @throws Zend_Http_Client_Exception
     */
    abstract protected function _forceInvalidResponse($request);

    /**
     * Force an error
     *
     * @param $request
     * @throws Zend_Http_Client_Exception
     */
    abstract protected function _forceError($request);

    /**
     * Force a timeout
     *
     * @param $request
     * @throws Zend_Http_Client_Exception
     */
    protected function _forceTimeout($request)
    {
        $this->_httpClient->setConfig(array('timeout' => .0001));
        $this->_httpClient->request();
    }

    /**
     * Force unknown response
     *
     * This is a malformed or unexpected response from the API.
     *
     * @param $request
     * @return Zend_Http_Response
     */
    protected function _forceUnknownResponse($request)
    {
        $httpStatusCode = 200;
        $headers = array();
        $responseBody = "This is an unknown response.";
        return new Zend_Http_Response($httpStatusCode, $headers, $responseBody);
    }

    public function getLogSettings()
    {
        $settings = new Varien_Object();
        $settings->setData(Mage::getStoreConfig($this->_logSettingsXpath, $this->storeId));
        return $settings;
    }

    public function getLogger()
    {
        if (!$this->_logger) {
            if ($this->getLogSettings()->getIsEnabled()) {
                $logger = new Zend_Log();
                $writer = new Zend_Log_Writer_Stream($this->getLogFile());
                $logger->addWriter($writer);
                $this->_logger = $logger;
            }
        }
        return $this->_logger;
    }

    protected function getLogFile()
    {
        $period = $this->getLogSettings()->getLogRotationPeriod();
        $period = $period * 60 * 60 * 24;
        $logDir = Mage::getBaseDir('log') . DS . 'drip';
        if (!is_dir($logDir)) {
            @mkdir($logDir);
            @chmod($logDir, 0777);
        }
        $logDir .= DS . $this->_apiName;
        if (!is_dir($logDir)) {
            @mkdir($logDir);
            @chmod($logDir, 0777);
        }
        $archiveDir = $logDir . DS . 'archive' . DS;
        if (!is_dir($archiveDir)) {
            @mkdir($archiveDir);
            @chmod($archiveDir, 0777);
        }
        $logFile = $logDir . DS . $this->_logFilename;
        $lastCreation = $this->getLogSettings()->getLastLogArchive();
        if (is_file($logFile) && $period && $lastCreation + $period < time()) {
            //leave default scope for this setting b/c we use one log file for all stores
            Mage::getConfig()->saveConfig($this->_logSettingsXpath.'/last_log_archive', time());
            $archive = new Mage_Archive();
            $archive->pack($logFile, $archiveDir.'archive'.date('Y-m-d-H-i-s').'.tgz');
            unlink($logFile);
        }
        return $logFile;
    }

}
