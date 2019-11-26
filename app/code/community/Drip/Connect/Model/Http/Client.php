<?php
class Drip_Connect_Model_Http_Client extends Zend_Http_Client
{
    /** @var Zend_Log */
    protected $_logger;

    /**
     * @param string $uri
     * @param array $config
     * @param Zend_Log $logger
     */
    public function __construct($uri, array $config, Zend_Log $logger)
    {
        $this->_logger = $logger;
        parent::__construct($uri, $config);
    }

    public function getLogger()
    {
        return $this->_logger;
    }

    /**
     * Send the HTTP request and return an HTTP response object
     *
     * @param string $method
     * @return Zend_Http_Response
     * @throws Zend_Http_Client_Exception
     */
    public function request($method = null)
    {
        $requestId = uniqid();
        $this->setHeaders('X-Drip-Connect-Request-Id', $requestId);
        $requestBody = $this->_prepareBody();
        $requestUrl = $this->getUri(true);
        $response = parent::request($method);
        $responseData = $response->getBody();

        $this->getLogger()->info("[{$requestId}] Request Url: {$requestUrl}");
        $this->getLogger()->info("[{$requestId}] Request Body: {$requestBody}");
        $this->getLogger()->info("[{$requestId}] Response: {$responseData}");

        return $response;
    }

}
