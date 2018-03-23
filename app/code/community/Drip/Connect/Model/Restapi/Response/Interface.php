<?php

interface Drip_Connect_Model_Restapi_Response_Interface
{
    /**
     * @return string Json response
     */
    public function toJson();

    /**
     * Gets the response
     *
     * @return Zend_Http_Response
     */
    public function getResponse();

    /**
     * @return bool
     */
    public function isError();

    /**
     * @return string|null
     */
    public function getErrorMessage();
}
