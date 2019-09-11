<?php

class Drip_Connect_Model_Observer_Quote_ClearCartOnLogin extends Drip_Connect_Model_Observer_Base
{
    /**
     * If user came from abandoned-cart email,
     * we need to clear his auth cart on login before merging with guest cart,
     * b/c guest cart has abandoned products
     *
     * @param Varien_Event_Observer $observer
     */
    protected function executeWhenEnabled(Varien_Event_Observer $observer)
    {
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
