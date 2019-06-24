<?php

class Drip_Connect_Helper_Logs extends Mage_Core_Helper_Abstract
{
    const SENDLOGS_RESPONSE_OK = 1;
    const SENDLOGS_RESPONSE_FAIL = 2;

    /**
     * @param int $storeId
     * @throws \Exception
     */
    public function sendLogs($storeId = null)
    {
    }
}
