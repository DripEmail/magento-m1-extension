<?php
/**
 * Base class for observers
 */

abstract class Drip_Connect_Model_Observer_Base
{
    const REGISTRY_KEY_OLD_DATA = 'orderoldvalues';

    abstract protected function executeWhenEnabled($observer);

    public function execute($observer) {
        if (!Mage::helper('drip_connect')->isModuleActive()) {
            return;
        }

        $myClass = get_class($this);
        $this->getLogger()->info("Observer triggered: {$myClass}");

        try {
            $this->executeWhenEnabled($observer);
        } catch (\Exception $e) {
            // We should never blow up a customer's site due to bugs in our code.
            $this->getLogger()->critical($e);
        }
    }

    protected function getLogger() {
        return Mage::helper('drip_connect/logger')->logger();
    }
}
