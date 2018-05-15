<?php

class Drip_Connect_Model_Cron_Orders
{
    /**
     * array [
     *     account_id => [
     *         store_id,    // == 0 for default config
     *         store_id,
     *     ],
     * ]
     */
    protected $accounts = [];

    /**
     * get all queued account ids
     * run orders sync for them
     */
    public function syncOrders()
    {
        $this->getAccountsToSyncOrders();

        foreach ($this->accounts as $accountId => $stores) {
            if ($this->syncOrdersWithAccount($accountId)) {
                $status = Drip_Connect_Model_Source_SyncState::READY;
            } else {
                $status = Drip_Connect_Model_Source_SyncState::READYERRORS;
            }
            foreach ($stores as $storeId) {
                Mage::helper('drip_connect')->setOrdersSyncStateToStore($storeId, $status);
            }
        }
    }

    /**
     * populate accounts array
     */
    protected function getAccountsToSyncOrders()
    {
        if (Mage::getStoreConfig('dripconnect_general/actions/sync_orders_data_state', 0) == Drip_Connect_Model_Source_SyncState::QUEUED) {
            $defAccount = Mage::getStoreConfig('dripconnect_general/api_settings/account_id', 0);
            $this->accounts[$defAccount][] = 0;
        }

        foreach (Mage::app()->getStores() as $store) {
            $storeId = $store->getStoreId();

            if (Mage::getStoreConfig('dripconnect_general/actions/sync_orders_data_state', $storeId) == Drip_Connect_Model_Source_SyncState::QUEUED) {
                $account = Mage::getStoreConfig('dripconnect_general/api_settings/account_id', $storeId);
                $this->accounts[$account][] = $storeId;
            }
        }
    }

    /**
     * @param int $accountId
     *
     * @return bool
     */
    protected function syncOrdersWithAccount($accountId)
    {
        $stores = $this->accounts[$accountId];
        foreach ($stores as $storeId) {
            Mage::helper('drip_connect')->setOrdersSyncStateToStore($storeId, Drip_Connect_Model_Source_SyncState::PROGRESS);
        }


        $result = true;
        $page = 1;
        do {
            $collection = Mage::getModel('sales/order')
                ->getCollection()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('state', array('nin' => array(
                    Mage_Sales_Model_Order::STATE_CANCELED,
                    Mage_Sales_Model_Order::STATE_CLOSED
                    )))
                ->setPageSize(Drip_Connect_Model_ApiCalls_Helper::MAX_BATCH_SIZE)
                ->setCurPage($page++)
                ->load();

            $batch = array();
            foreach ($collection as $order) {
                $data = Mage::helper('drip_connect/order')->getOrderDataNew($order);
                $data['occurred_at'] = $order->getCreatedAt();
                $batch[] = $data;
            }

            $response = Mage::getModel('drip_connect/ApiCalls_Helper_Batches_Orders', array(
                'batch' => $batch,
                'account' => $accountId,
            ))->call();

            if ($response->getResponseCode() != 202) { // drip success code for this action
                $result = false;
                break;
            }
        } while ($page <= $collection->getLastPageNumber());

        return $result;
    }
}
