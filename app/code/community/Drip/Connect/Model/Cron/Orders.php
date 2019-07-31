<?php

class Drip_Connect_Model_Cron_Orders
{
    /**
     * run orders sync for stores
     *
     * if default sync queued, get all store ids
     * else walk through stores grab storeIds queued for sync
     * loop through storeids and sync every of them with drip
     * using their own configs and sending only storerelated data
     */
    public function syncOrders()
    {
        $storeIds = [];
        $stores = Mage::app()->getStores(false, false);

        $trackDefaultStatus = false;
        if (Mage::getStoreConfig('dripconnect_general/actions/sync_orders_data_state', 0) == Drip_Connect_Model_Source_SyncState::QUEUED) {
            $trackDefaultStatus = true;
            $storeIds = array_keys($stores);
            Mage::helper('drip_connect')->setOrdersSyncStateToStore(0, Drip_Connect_Model_Source_SyncState::PROGRESS);
        } else {
            foreach ($stores as $storeId => $store) {
                if (Mage::getStoreConfig('dripconnect_general/actions/sync_orders_data_state', $storeId) == Drip_Connect_Model_Source_SyncState::QUEUED) {
                    $storeIds[] = $storeId;
                }
            }
        }

        $statuses = [];
        foreach ($storeIds as $storeId) {
            if (! Mage::getStoreConfig('dripconnect_general/module_settings/is_enabled', $storeId)) {
                continue;
            }

            // can already be in progress if store sync had been started with
            // its own button just before the default sync was also launched
            if (Mage::getStoreConfig('dripconnect_general/actions/sync_orders_data_state', $storeId) == Drip_Connect_Model_Source_SyncState::PROGRESS) {
                continue;
            }

            try {
                $result = $this->syncOrdersForStore($storeId);
            } catch (\Exception $e) {
                Mage::logException($e);
                $result = false;
            }

            if ($result) {
                $status = Drip_Connect_Model_Source_SyncState::READY;
            } else {
                $status = Drip_Connect_Model_Source_SyncState::READYERRORS;
            }

            $statuses[$storeId] = $status;

            Mage::helper('drip_connect')->setOrdersSyncStateToStore($storeId, $status);
        }

        if ($trackDefaultStatus) {
            if (count($statuses) === 0 || (
                count(array_unique($statuses)) === 1 &&
                $stauses[0] === Drip_Connect_Model_Source_SyncState::READY
            )) {
                $status = Drip_Connect_Model_Source_SyncState::READY;
            } else {
                $status = Drip_Connect_Model_Source_SyncState::READYERRORS;
            }
            Mage::helper('drip_connect')->setOrdersSyncStateToStore(0, $status);
        }
    }

    /**
     * @param int $storeId
     *
     * @return bool
     */
    protected function syncOrdersForStore($storeId)
    {
        Mage::helper('drip_connect')->setOrdersSyncStateToStore($storeId, Drip_Connect_Model_Source_SyncState::PROGRESS);

        $delay = (int) Mage::getStoreConfig('dripconnect_general/api_settings/batch_delay');

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
                ->addFieldToFilter('store_id', $storeId)
                ->setPageSize(Drip_Connect_Model_ApiCalls_Helper::MAX_BATCH_SIZE)
                ->setCurPage($page++)
                ->load();

            $batch = array();
            foreach ($collection as $order) {
                $data = Mage::helper('drip_connect/order')->getOrderDataNew($order);
                $data['occurred_at'] = Mage::helper('drip_connect')->formatDate($order->getCreatedAt());
                $batch[] = $data;
            }

            if (count($batch)) {
                $response = Mage::getModel('drip_connect/ApiCalls_Helper_Batches_Orders', array(
                    'batch' => $batch,
                    'store_id' => $storeId,
                ))->call();

                if (empty($response) || $response->getResponseCode() != 202) { // drip success code for this action
                    $result = false;
                    break;
                }

                sleep($delay);
            }

        } while ($page <= $collection->getLastPageNumber());

        return $result;
    }
}
