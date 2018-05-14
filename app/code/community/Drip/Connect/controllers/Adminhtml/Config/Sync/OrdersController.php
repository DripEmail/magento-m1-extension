<?php

class Drip_Connect_Adminhtml_Config_Sync_OrdersController
    extends Mage_Adminhtml_Controller_Action
{
    /**
     * prepare and send orders data
     *
     * @return void
     */
    public function runAction()
    {
        $storeId = $this->getRequest()->getParam('store_id');

        if (empty($storeId)) {
            Mage::getConfig()->saveConfig(
                'dripconnect_general/actions/sync_orders_data_state',
                Drip_Connect_Model_Source_SyncState::QUEUED
            );
        } else {
            Mage::getConfig()->saveConfig(
                'dripconnect_general/actions/sync_orders_data_state',
                Drip_Connect_Model_Source_SyncState::QUEUED,
                'stores',
                $storeId
            );
        }

        $this->getResponse()->setBody(Drip_Connect_Model_Source_SyncState::QUEUED);
    }
}
