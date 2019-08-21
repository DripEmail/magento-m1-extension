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
        ini_set('memory_limit', Mage::getStoreConfig('dripconnect_general/api_settings/memory_limit'));

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

            try {
                $result = $this->syncOrdersForStore($storeId);
            } catch (\Exception $e) {
                $this->getLogger()->log($e->__toString(), Zend_Log::ERR);
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
            $status_values = array_unique(array_values($statuses));
            if (count($status_values) === 0 || (
                count($status_values) === 1 &&
                $status_values[0] === Drip_Connect_Model_Source_SyncState::READY
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
                if (Mage::helper('drip_connect/order')->isCanBeSent($order)) {
                    $data = Mage::helper('drip_connect/order')->getOrderDataNew($order);
                    $data['occurred_at'] = Mage::helper('drip_connect')->formatDate($order->getCreatedAt());
                    $batch[] = $data;
                } else {
                    $this->getLogger()->log(sprintf(
                        "Order with id %s can't be sent to Drip (email likely blank)",
                        $order->getId()
                    ), Zend_Log::NOTICE);
                }
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
            }

        } while ($page <= $collection->getLastPageNumber());

        return $result;
    }

    protected function getLogger() {
        return Mage::helper('drip_connect/logger')->logger();
    }
}
