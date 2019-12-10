<?php

class Drip_Connect_Model_Observer_Quote_AfterQuoteSaved extends Drip_Connect_Model_Observer_Base
{
    /**
     * @param Varien_Event_Observer $observer
     */
    protected function executeWhenEnabled($observer)
    {
        //do nothing
        if (Mage::registry(Drip_Connect_Helper_Quote::REGISTRY_KEY_CUSTOMER_REGISTERED_OR_LOGGED_IN_WITH_EMTPY_QUOTE)) {
            return;
        }

        $mutex = new Drip_Connect_Model_RegistryMutex(Drip_Connect_Model_RegistryMutex::QUOTE_OBSERVER_MUTEX_KEY);
        // Make sure we aren't currently processing a beforeQuoteSaved observer due to a loop.
        if (!$mutex->checkAvailable()) {
            return;
        }

        $quote = $observer->getEvent()->getQuote();

        if (Mage::helper('drip_connect/quote')->isUnknownUser($quote)) {
            return;
        }

        $config = Drip_Connect_Model_Configuration::forCurrentScope();

        if (Mage::registry(Drip_Connect_Helper_Quote::REGISTRY_KEY_IS_NEW)) {
            Mage::helper('drip_connect/quote')->proceedQuoteNew($config, $quote);
        } else {
            $oldData = Mage::registry(Drip_Connect_Helper_Quote::REGISTRY_KEY_OLD_DATA);
            if (empty($oldData['items']) || count($oldData['items']) == 0) {
                //customer logged in previously with empty cart and then adds a product
                Mage::helper('drip_connect/quote')->proceedQuoteNew($config, $quote);
            } else {
                if (Mage::helper('drip_connect/quote')->isQuoteChanged($quote)) {
                    Mage::helper('drip_connect/quote')->proceedQuote($config, $quote);
                }
            }
        }

        Mage::unregister(Drip_Connect_Helper_Quote::REGISTRY_KEY_IS_NEW);
        Mage::unregister(Drip_Connect_Helper_Quote::REGISTRY_KEY_OLD_DATA);
        Mage::unregister(Drip_Connect_Helper_Quote::REGISTRY_KEY_CUSTOMER_REGISTERED_OR_LOGGED_IN_WITH_EMTPY_QUOTE);
    }
}
