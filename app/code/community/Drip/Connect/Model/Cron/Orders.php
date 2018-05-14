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
    protected $accounts;

    /**
     * get all queued account ids
     * run orders sync for them
     */
    public function syncOrders()
    {
        $this->getAccountsToSyncOrders();

        foreach ($accounts as $accountId) {
            if ($this->syncOrdersWithAccount($accountId)) {
                foreach ($accounts[$accountId] as $storeId) {
                    Mage::helper('drip_connect')->setOrdersSyncStateToStore($storeId, Drip_Connect_Model_Source_SyncState::READY);
                }
            } else {
                foreach ($accounts[$accountId] as $storeId) {
                    Mage::helper('drip_connect')->setOrdersSyncStateToStore($storeId, Drip_Connect_Model_Source_SyncState::READYERRORS);
                }
            }
        }
    }

    /**
     * populate accounts array
     */
    protected function getAccountsToSyncOrders()
    {
        // todo
        //$this->accounts
        // get default values for dripconnect_general/api_settings/account_id
        // and dripconnect_general/actions/sync_orders_data_state
        // add them in $accounts array
        // then walk through the all stores (except admin one)
        // if account_id != default account_id
        //   if sync_orders_data_state == QUEUED
        //     add in $accounts array
    }

    /**
     * @return bool
     */
    protected function syncOrdersWithAccount($accountId)
    {
        foreach ($this->accounts as $accountId => $stores) {
            foreach ($stores as $storeId) {
                Mage::helper('drip_connect')->setOrdersSyncStateToStore($storeId, Drip_Connect_Model_Source_SyncState::PROGRESS);
            }
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
