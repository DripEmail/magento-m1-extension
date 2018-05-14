<?php

class Drip_Connect_Adminhtml_Config_Sync_CustomersController
    extends Mage_Adminhtml_Controller_Action
{
    /**
     * prepare and send customers data
     *
     * @return void
     */
    public function runAction()
    {
        $storeId = $this->getRequest()->getParam('store_id');

        if (empty($storeId)) {
            Mage::getConfig()->saveConfig(
                'dripconnect_general/actions/sync_customers_data_state',
                Drip_Connect_Model_Source_SyncState::QUEUED
            );
        } else {
            Mage::getConfig()->saveConfig(
                'dripconnect_general/actions/sync_customers_data_state',
                Drip_Connect_Model_Source_SyncState::QUEUED,
                'stores',
                $storeId
            );
        }

        $this->getResponse()->setBody(Drip_Connect_Model_Source_SyncState::QUEUED);
    }
}
