<?php

class Drip_Connect_Model_ApiCalls_Base
    extends Drip_Connect_Model_Restapi_Abstract
{
    const DEFAULT_RESPONSE_MODEL = 'drip_connect/ApiCalls_Response_Base';

    /**
     * @param Drip_Connect_Model_Configuration $config
     * @param string $endpoint
     * @param boolean $v3
     */
    public function __construct(Drip_Connect_Model_Configuration $config, $endpoint, $v3 = false)
    {
        $storeId = $config->getStoreId();
        $this->setStoreId($storeId);
        $this->_behavior = $config->getBehavior();

        $this->_responseModel = self::DEFAULT_RESPONSE_MODEL;

        $url = $config->getUrl().$endpoint;

        if ($v3) {
            $url = str_replace('/v2/', '/v3/', $url);
        }

        $this->_httpClient = Mage::getModel(
            'drip_connect/Http_Client',
            array(
                'uri' => $url,
                'config' => array(
                    'useragent' => self::USERAGENT,
                    'timeout' => $config->getTimeout() / 1000,
                ),
                'logger' => $this->getLogger(),
            )
        );

        $this->_httpClient->setHeaders(
            array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            )
        );

        $this->_httpClient->setAuth(
            $config->getApiKey(),
            '',
            Zend_Http_Client::AUTH_BASIC
        );
    }

    /**
     * Call the API
     *
     * @param Drip_Connect_Model_Restapi_Request $request
     * @throws Zend_Http_Client_Exception
     */
    protected function _callApi($request)
    {
        if (!empty($request->getParametersGet())) {
            $this->_httpClient->setParameterGet($request->getParametersGet());
        }

        if (!empty($request->getParametersPost())) {
            $this->_httpClient->setParameterPost($request->getParametersPost());
        }

        if (!empty($request->getRawData())) {
            $this->_httpClient->setRawData($request->getRawData());
        }

        $response = $this->_httpClient->request($request->getMethod());

        $this->_lastRequestUrl = $this->_httpClient->getUri();
        $this->_lastRequest = $this->_httpClient->getLastRequest();

        return $response;
    }

    protected function _forceValidResponse($request)
    {
        return new Zend_Http_Response(
            200,
            array("Content-type" => "application/json; charset=utf-8"),
            json_encode(
                array(
                    "Status" => "OK",
                    "Message" => "Forced Valid Response"
                )
            )
        );
    }

    protected function _forceInvalidResponse($request)
    {
        return new Zend_Http_Response(
            200,
            array("Content-type" => "application/json; charset=utf-8"),
            json_encode(
                array(
                    "Status" => "OK",
                    "Message" => "Forced Invalid Response"
                )
            )
        );
    }

    protected function _forceError($request)
    {
        return new Zend_Http_Response(
            500,
            array("Content-type" => "application/json; charset=utf-8"),
            json_encode(
                array(
                    "Status" => "Error",
                    "Message" => "Forced Error Message"
                )
            )
        );
    }

    /**
     * @param string response class
     */
    public function setResponseModel($response)
    {
        $this->_responseModel = $response;
    }

}


