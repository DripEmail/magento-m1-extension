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
        $globalConfig = Drip_Connect_Model_Configuration::forGlobalScope();

        ini_set('memory_limit', $globalConfig->getMemoryLimit());

        // TODO: This may need nuking once we properly pass the config object everywhere. We'd rather not set globals everywhere.
        Mage::app()->setCurrentStore('default');

        $storeIds = array();
        $stores = Mage::app()->getStores(false, false);

        $trackDefaultStatus = false;
        if ($globalConfig->getOrdersSyncState() == Drip_Connect_Model_Source_SyncState::QUEUED) {
            $trackDefaultStatus = true;
            $storeIds = array_keys($stores);
            // TODO: Refactor into config object.
            Mage::helper('drip_connect')->setOrdersSyncStateToStore(0, Drip_Connect_Model_Source_SyncState::PROGRESS);
        } else {
            foreach ($stores as $storeId => $store) {
                $storeConfig = new Drip_Connect_Model_Configuration($storeId);
                if ($storeConfig->getOrdersSyncState() == Drip_Connect_Model_Source_SyncState::QUEUED) {
                    $storeIds[] = $storeId;
                }
            }
        }

        $statuses = array();
        foreach ($storeIds as $storeId) {
            $storeConfig = new Drip_Connect_Model_Configuration($storeId);
            if (!$storeConfig->isEnabled()) {
                continue;
            }

            try {
                $result = $this->syncOrdersForStore($storeConfig);
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

            // TODO: Refactor into config object.
            Mage::helper('drip_connect')->setOrdersSyncStateToStore($storeId, $status);
        }

        if ($trackDefaultStatus) {
            $statusValues = array_unique(array_values($statuses));
            if (count($statusValues) === 0 || (
                count($statusValues) === 1 &&
                $statusValues[0] === Drip_Connect_Model_Source_SyncState::READY
            )) {
                $status = Drip_Connect_Model_Source_SyncState::READY;
            } else {
                $status = Drip_Connect_Model_Source_SyncState::READYERRORS;
            }

            Mage::helper('drip_connect')->setOrdersSyncStateToStore(0, $status);
        }
    }

    /**
     * @param Drip_Connect_Model_Configuration $config
     *
     * @return bool
     */
    protected function syncOrdersForStore($config)
    {
        // TODO: Refactor into config object.
        Mage::helper('drip_connect')->setOrdersSyncStateToStore(
            $config->getStoreId(),
            Drip_Connect_Model_Source_SyncState::PROGRESS
        );

        $result = true;
        $page = 1;
        do {
            $collection = Mage::getModel('sales/order')
                ->getCollection()
                ->addAttributeToSelect('*')
                ->addFieldToFilter(
                    'state',
                    array(
                        'nin' => array(
                            Mage_Sales_Model_Order::STATE_CANCELED,
                            Mage_Sales_Model_Order::STATE_CLOSED,
                        )
                    )
                )
                ->addFieldToFilter('store_id', $config->getStoreId())
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
                    $this->getLogger()->log(
                        sprintf(
                            "Order with id %s can't be sent to Drip (email likely invalid)",
                            $order->getId()
                        ),
                        Zend_Log::NOTICE
                    );
                }
            }

            if (!empty($batch)) {
                $response = Mage::getModel(
                    'drip_connect/ApiCalls_Helper_Batches_Orders',
                    array(
                        'batch' => $batch,
                        'store_id' => $config->getStoreId(),
                    )
                )->call();

                if (empty($response) || $response->getResponseCode() != 202) { // drip success code for this action
                    $result = false;
                    break;
                }
            }
        } while ($page <= $collection->getLastPageNumber());

        return $result;
    }

    protected function getLogger()
    {
        return Mage::helper('drip_connect/logger')->logger();
    }
}
