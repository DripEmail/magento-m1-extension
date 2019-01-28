<?php

class Drip_Connect_Adminhtml_Config_Sync_OrdersController
    extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('dripconnect_general');
    }
    
    /**
     * prepare and send orders data
     *
     * @return void
     */
    public function runAction()
    {
        $storeId = $this->getRequest()->getParam('store_id');

        Mage::helper('drip_connect')->setOrdersSyncStateToStore($storeId, Drip_Connect_Model_Source_SyncState::QUEUED);

        $this->getResponse()->setBody(Drip_Connect_Model_Source_SyncState::QUEUED);
    }
}
