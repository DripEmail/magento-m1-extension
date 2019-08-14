<?php
class Drip_Connect_Model_Http_Client extends Zend_Http_Client
{

    /** @var Zend_Log */
    protected $_logger;

    public function __construct($args)
    {
        $uri = isset($args['uri']) ? $args['uri'] : null;
        $config = isset($args['config']) ? $args['config'] : null;
        $this->_logger = isset($args['logger']) ? $args['logger'] : null;
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
