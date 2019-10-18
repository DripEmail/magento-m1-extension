<?php
/**
 * Base class for observers
 */

abstract class Drip_Connect_Model_Observer_Base
{
    const REGISTRY_KEY_CUSTOMER_IS_NEW = 'newcustomer';
    const REGISTRY_KEY_CUSTOMER_OLD_ADDR = 'oldcustomeraddress';
    const REGISTRY_KEY_CUSTOMER_OLD_DATA = 'oldcustomerdata';
    const REGISTRY_KEY_NEW_GUEST_SUBSCRIBER = 'newguestsubscriber';
    const REGISTRY_KEY_ORDER_OLD_DATA = 'orderoldvalues';
    const REGISTRY_KEY_SUBSCRIBER_PREV_STATE = 'oldsubscriptionstatus';
    const REGISTRY_KEY_SUBSCRIBER_SUBSCRIBE_INTENT = 'userwantstosubscribe';

    abstract protected function executeWhenEnabled($observer);

    public function execute($observer)
    {
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

    protected function getLogger()
    {
        return Mage::helper('drip_connect/logger')->logger();
    }
}
