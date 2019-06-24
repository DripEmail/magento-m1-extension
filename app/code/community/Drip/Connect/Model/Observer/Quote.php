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
            Mage::unregister(Drip_Connect_Helper_Quote::REGISTRY_KEY_OLD_DATA);
            Mage::register(Drip_Connect_Helper_Quote::REGISTRY_KEY_OLD_DATA, $data);
        } else {
            Mage::helper('drip_connect/quote')->checkForEmptyQuote($quote);
        }

        if (!Mage::registry(Drip_Connect_Helper_Quote::REGISTRY_KEY_CUSTOMER_REGISTERED_OR_LOGGED_IN_WITH_EMTPY_QUOTE)) {
            Mage::unregister(Drip_Connect_Helper_Quote::REGISTRY_KEY_IS_NEW);
            if (!$quote->getDrip()) {
                Mage::register(Drip_Connect_Helper_Quote::REGISTRY_KEY_IS_NEW, true);
                $quote->setDrip(true);
            } else {
                Mage::register(Drip_Connect_Helper_Quote::REGISTRY_KEY_IS_NEW, false);
            }
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
            if(empty($oldData['items']) || count($oldData['items']) == 0) {
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

    /**
     * If user came from abandoned-cart email,
     * we need to clear his auth cart on login before merging with guest cart,
     * b/c guest cart has abandoned products
     *
     * @param Varien_Event_Observer $observer
     */
    public function clearCartOnLogin(Varien_Event_Observer $observer)
    {
        if (! Mage::helper('drip_connect')->isModuleActive()) {
            return;
        }

        if ($this->isIgnoreMerge()) {
            return;
        }

        if (! empty(Mage::getSingleton('customer/session')->getIsAbandonedCartGuest())) {
            $observer->getEvent()->getQuote()->removeAllItems();
            Mage::getSingleton('customer/session')->unsIsAbandonedCartGuest();
        }
    }

    /**
     * check if current handler should be ignored for clear cart on quote merge
     *
     * @return bool
     */
    protected function isIgnoreMerge()
    {
        $request = Mage::app()->getRequest();
        $route = $request->getRouteName();
        $controller = $request->getControllerName();
        $action = $request->getActionName();

        return in_array($route.'_'.$controller.'_'.$action, [
            'drip_cart_index'
        ]);
    }
}
