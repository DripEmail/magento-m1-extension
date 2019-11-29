<?php

class Drip_Connect_Adminhtml_Config_Sync_CustomersController
    extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('dripconnect_general');
    }

    /**
     * prepare and send customers data
     *
     * @return void
     */
    public function runAction()
    {
        $config = Drip_Connect_Model_Configuration::forCurrentStoreParam();
        $config->setCustomersSyncState(Drip_Connect_Model_Source_SyncState::QUEUED);
        $this->getResponse()->setBody(Drip_Connect_Model_Source_SyncState::QUEUED);
    }

    /**
     * reset sync status
     *
     * @return void
     */
    public function resetStateAction()
    {
        $config = Drip_Connect_Model_Configuration::forCurrentStoreParam();
        $config->setCustomersSyncState(Drip_Connect_Model_Source_SyncState::READY);
        $this->getResponse()->setBody(Drip_Connect_Model_Source_SyncState::READY);
    }
}
