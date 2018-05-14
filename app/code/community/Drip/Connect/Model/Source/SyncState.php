<?php

class Drip_Connect_Model_Source_SyncState
{
    const READY = 0; // job not running and not going to run
    const QUEUED = 1; // job will start shortly (when cron starts)
    const PROGRESS = 2; // job in progress

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => self::READY, 'label' => Mage::helper('adminhtml')->__('Ready')),
            array('value' => self::QUEUED, 'label' => Mage::helper('adminhtml')->__('Queued')),
            array('value' => self::PROGRESS, 'label' => Mage::helper('adminhtml')->__('In Progress')),
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
            self::READY => Mage::helper('adminhtml')->__('Ready'),
            self::QUEUED => Mage::helper('adminhtml')->__('Queued'),
            self::PROGRESS => Mage::helper('adminhtml')->__('In Progress'),
        );
    }

    /**
     * @return string
     */
    static function getLabel($key)
    {
        switch ($key) {
            case self::READY :
                return Mage::helper('adminhtml')->__('Ready');
                break;
            case self::QUEUED :
                return Mage::helper('adminhtml')->__('Queued');
                break;
            case self::PROGRESS :
                return Mage::helper('adminhtml')->__('In Progress');
                break;
        }
        return '';
    }
}
