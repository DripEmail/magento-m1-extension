<?php
/**
 * Actions with quote
 */

class Drip_Connect_Model_Observer_Quote
{
    /**
     * @param Varien_Event_Observer $observer
     */
    public function beforeQuoteSaved($observer)
    {
        if (!Mage::helper('drip_connect')->isModuleActive()) {
            return;
        }

        $quote = $observer->getEvent()->getQuote();

        if (Mage::helper('drip_connect/quote')->isUnknownUser($quote)) {
            return;
        }

        if (!$quote->isObjectNew()) {
            $orig = Mage::getModel('sales/quote')->load($quote->getId());
            $data = Mage::helper('drip_connect/quote')->prepareQuoteData($orig);
            Mage::register(Drip_Connect_Helper_Quote::REGISTRY_KEY_OLD_DATA, $data);
        }

        if (!$quote->getDrip()) {
            Mage::register(Drip_Connect_Helper_Quote::REGISTRY_KEY_IS_NEW, true);
            $quote->setDrip(true);
        } else {
            Mage::register(Drip_Connect_Helper_Quote::REGISTRY_KEY_IS_NEW, false);
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function afterQuoteSaved($observer)
    {
        if (!Mage::helper('drip_connect')->isModuleActive()) {
            return;
        }

        $quote = $observer->getEvent()->getQuote();

        if (Mage::helper('drip_connect/quote')->isUnknownUser($quote)) {
            return;
        }

        if (Mage::registry(Drip_Connect_Helper_Quote::REGISTRY_KEY_IS_NEW)) {
            Mage::helper('drip_connect/quote')->proceedQuoteNew($quote);
        } else {
            if (Mage::helper('drip_connect/quote')->isQuoteChanged($quote)) {
                Mage::helper('drip_connect/quote')->proceedQuote($quote);
            }
        }
        Mage::unregister(Drip_Connect_Helper_Quote::REGISTRY_KEY_IS_NEW);
        Mage::unregister(Drip_Connect_Helper_Quote::REGISTRY_KEY_OLD_DATA);
    }

}
