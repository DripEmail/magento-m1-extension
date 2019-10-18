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

        $quote = $observer->getEvent()->getQuote();

        if (Mage::helper('drip_connect/quote')->isUnknownUser($quote)) {
            return;
        }

        if (Mage::registry(Drip_Connect_Helper_Quote::REGISTRY_KEY_IS_NEW)) {
            Mage::helper('drip_connect/quote')->proceedQuoteNew($quote);
        } else {
            $oldData = Mage::registry(Drip_Connect_Helper_Quote::REGISTRY_KEY_OLD_DATA);
            if (empty($oldData['items']) || count($oldData['items']) == 0) {
                //customer logged in previously with empty cart and then adds a product
                Mage::helper('drip_connect/quote')->proceedQuoteNew($quote);
            } else {
                if (Mage::helper('drip_connect/quote')->isQuoteChanged($quote)) {
                    Mage::helper('drip_connect/quote')->proceedQuote($quote);
                }
            }
        }

        Mage::unregister(Drip_Connect_Helper_Quote::REGISTRY_KEY_IS_NEW);
        Mage::unregister(Drip_Connect_Helper_Quote::REGISTRY_KEY_OLD_DATA);
        Mage::unregister(Drip_Connect_Helper_Quote::REGISTRY_KEY_CUSTOMER_REGISTERED_OR_LOGGED_IN_WITH_EMTPY_QUOTE);
    }
}
