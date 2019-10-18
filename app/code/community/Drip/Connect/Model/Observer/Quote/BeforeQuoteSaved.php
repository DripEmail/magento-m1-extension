<?php

class Drip_Connect_Model_Observer_Quote_BeforeQuoteSaved extends Drip_Connect_Model_Observer_Base
{
    /**
     * @param Varien_Event_Observer $observer
     */
    protected function executeWhenEnabled($observer)
    {
        $quote = $observer->getEvent()->getQuote();

        if (Mage::helper('drip_connect/quote')->isUnknownUser($quote)) {
            return;
        }

        if (!$quote->isObjectNew()) {
            $orig = Mage::getModel('sales/quote')->load($quote->getId());
            $data = Mage::helper('drip_connect/quote')->prepareQuoteData($orig);
            Mage::unregister(Drip_Connect_Helper_Quote::REGISTRY_KEY_OLD_DATA);
            Mage::register(Drip_Connect_Helper_Quote::REGISTRY_KEY_OLD_DATA, $data);
        } else {
            Mage::helper('drip_connect/quote')->checkForEmptyQuote($quote);
        }

        if (!Mage::registry(
            Drip_Connect_Helper_Quote::REGISTRY_KEY_CUSTOMER_REGISTERED_OR_LOGGED_IN_WITH_EMTPY_QUOTE
        )) {
            Mage::unregister(Drip_Connect_Helper_Quote::REGISTRY_KEY_IS_NEW);
            if (!$quote->getDrip()) {
                Mage::register(Drip_Connect_Helper_Quote::REGISTRY_KEY_IS_NEW, true);
                $quote->setDrip(true);
            } else {
                Mage::register(Drip_Connect_Helper_Quote::REGISTRY_KEY_IS_NEW, false);
            }
        }
    }
}
