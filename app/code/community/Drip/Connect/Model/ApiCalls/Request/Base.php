<?php
class Drip_Connect_Model_ApiCalls_Request_Base
    implements Drip_Connect_Model_Restapi_Request_Interface
{
    protected $parametersGet = array();

    protected $parametersPost = array();

    protected $rawData = '';

    protected $method = Zend_Http_Client::GET;

    /**
     * @param array $param
     * @return this
     */
    public function setParametersGet($params)
    {
        $this->parametersGet = $params;

        return $this;
    }

    /**
     * @param array $param
     * @return this
     */
    public function setParametersPost($params)
    {
        $this->parametersPost = $params;

        return $this;
    }

    /**
     * @param string $data
     * @return this
     */
    public function setRawData($data)
    {
        $this->rawData = $data;

        return $this;
    }

    /**
     * @param string $method http request method
     * @return this
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return array
     */
    public function getParametersGet()
    {
        return $this->parametersGet;
    }

    /**
     * @return array
     */
    public function getParametersPost()
    {
        return $this->parametersPost;
    }

    /**
     * @return string
     */
    public function getRawData()
    {
        return $this->rawData;
    }

    /**
     * @return string http request method
     */
    public function getMethod()
    {
        return $this->method;
    }
}
