<?php

class Drip_Connect_Model_ApiCalls_Response_Base
    extends Drip_Connect_Model_Restapi_Response_Abstract
    implements Drip_Connect_Model_Restapi_Response_Interface
{
    /** @var array */
    protected $responseData;

    /**
     * constructor
     */
    public function __construct($response = null, $errorMessage = null)
    {
        parent::__construct($response, $errorMessage);

        if (!$this->_isError) {
            $this->responseData = json_decode($this->getResponse()->getBody(), true);
        }
    }

    /**
     * @return string Json response
     */
    public function toJson()
    {
        return $this->getResponse();
    }

    /**
     * @return array
     */
    public function getResponseData()
    {
        return $this->responseData;
    }

    /**
     * Get the HTTP response status code
     *
     * @return int|null
     */
    public function getResponseCode()
    {
        if (empty($this->getResponse())) {
            return null;
        }

        return $this->getResponse()->getStatus();
    }
}

