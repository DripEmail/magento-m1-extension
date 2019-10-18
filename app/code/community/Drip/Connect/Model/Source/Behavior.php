<?php

class Drip_Connect_Model_Source_Behavior
{
    const CALL_API              = 'call_api';
    const FORCE_VALID           = 'force_valid';
    const FORCE_INVALID         = 'force_invalid';
    const FORCE_TIMEOUT         = 'force_timeout';
    const FORCE_ERROR           = 'force_error';
    const FORCE_UNKNOWN_ERROR   = 'force_unknown_error';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => self::CALL_API, 'label' => Mage::helper('adminhtml')->__('Call API')),
            array('value' => self::FORCE_VALID, 'label' => Mage::helper('adminhtml')->__('Force Valid Result')),
            array('value' => self::FORCE_INVALID, 'label' => Mage::helper('adminhtml')->__('Force Invalid Result')),
            array('value' => self::FORCE_TIMEOUT, 'label' => Mage::helper('adminhtml')->__('Force Timeout')),
            array('value' => self::FORCE_ERROR, 'label' => Mage::helper('adminhtml')->__('Force Error')),
            array('value' => self::FORCE_UNKNOWN_ERROR, 'label' => Mage::helper('adminhtml')->__('Force Unkown Error')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            self::CALL_API => Mage::helper('adminhtml')->__('Call API'),
            self::FORCE_VALID => Mage::helper('adminhtml')->__('Force Valid Result'),
            self::FORCE_INVALID => Mage::helper('adminhtml')->__('Force Invalid Result'),
            self::FORCE_TIMEOUT => Mage::helper('adminhtml')->__('Force Timeout'),
            self::FORCE_ERROR => Mage::helper('adminhtml')->__('Force Error'),
            self::FORCE_UNKNOWN_ERROR => Mage::helper('adminhtml')->__('Force Unknown Error'),
        );
    }
}
