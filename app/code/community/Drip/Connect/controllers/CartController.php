<?php

class Drip_Connect_CartController extends Mage_Core_Controller_Front_Action
{
    /**
     * Default action
     */
    public function indexAction()
    {
        if (! Mage::helper('drip_connect')->isModuleActive()) {
            $this->norouteAction();
            return;
        }

        Mage::getSingleton('customer/session')->setIsAbandonedCartProcessed(1);

        $quoteId = Mage::app()->getRequest()->getParam(Drip_Connect_Helper_Data::QUOTE_KEY);
        $storeId = Mage::app()->getRequest()->getParam(Drip_Connect_Helper_Data::STORE_KEY);
        $secureKey = Mage::app()->getRequest()->getParam(Drip_Connect_Helper_Data::SECURE_KEY);

        if (! $quoteId || ! $storeId || ! $secureKey) {
            Mage::getSingleton('core/session')->addError(Mage::helper('drip_connect')->__('Link is broken'));
            $this->_redirect('/');
            return;
        }

        if ($secureKey !== Mage::helper('drip_connect')->getSecureKey($quoteId, $storeId)) {
            Mage::getSingleton('core/session')->addError(Mage::helper('drip_connect')->__('Link is broken'));
            $this->_redirect('/');
            return;
        }

        $store = Mage::getSingleton('core/store')->load($storeId);
        if (! $store->getId()) {
            Mage::getSingleton('core/session')->addError(Mage::helper('drip_connect')->__('Unknown Store'));
            $this->_redirect('/');
            return;
        }

        $oldQuote = Mage::getModel('sales/quote')->setStore($store)->load($quoteId);
        if (! $oldQuote->getId()) {
            Mage::getSingleton('core/session')->addError(Mage::helper('drip_connect')->__('Unknown Cart'));
            $this->_redirect('/');
            return;
        }

        if (! Mage::getSingleton('customer/session')->isLoggedIn()) {
            Mage::getSingleton('customer/session')->setIsAbandonedCartGuest(1);
        }

        Mage::helper('drip_connect/quote')->recreateCartFromQuote($oldQuote);

        $this->_redirect('checkout/cart');
    }
}
