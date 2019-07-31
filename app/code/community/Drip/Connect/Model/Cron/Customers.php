<?php

class Drip_Connect_Model_Cron_Customers
{
    /**
     * run customers sync for stores
     *
     * if default sync queued, get all store ids
     * else walk through stores grab storeIds queued for sync
     * loop through storeids and sync every of them with drip
     * using their own configs and sending only storerelated data
     */
    public function syncCustomers()
    {

        $storeIds = [];
        $stores = Mage::app()->getStores(false, false);

        $trackDefaultStatus = false;
        if (Mage::getStoreConfig('dripconnect_general/actions/sync_customers_data_state', 0) == Drip_Connect_Model_Source_SyncState::QUEUED) {
            $trackDefaultStatus = true;
            $storeIds = array_keys($stores);
            Mage::helper('drip_connect')->setCustomersSyncStateToStore(0, Drip_Connect_Model_Source_SyncState::PROGRESS);
        } else {
            foreach ($stores as $storeId => $store) {
                if (Mage::getStoreConfig('dripconnect_general/actions/sync_customers_data_state', $storeId) == Drip_Connect_Model_Source_SyncState::QUEUED) {
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
            if (Mage::getStoreConfig('dripconnect_general/actions/sync_customers_data_state', $storeId) == Drip_Connect_Model_Source_SyncState::PROGRESS) {
                continue;
            }

            try {
                $result = $this->syncCustomersForStore($storeId);
                if ($result) {
                    $result = $this->syncGuestSubscribersForStore($storeId);
                }
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

            Mage::helper('drip_connect')->setCustomersSyncStateToStore($storeId, $status);
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
            Mage::helper('drip_connect')->setCustomersSyncStateToStore(0, $status);
        }
    }

    /**
     * @param int $storeId
     *
     * @return bool
     */
    protected function syncGuestSubscribersForStore($storeId)
    {
        Mage::helper('drip_connect')->setCustomersSyncStateToStore($storeId, Drip_Connect_Model_Source_SyncState::PROGRESS);

        $delay = (int) Mage::getStoreConfig('dripconnect_general/api_settings/batch_delay');

        $result = true;
        $page = 1;
        do {
            // todo filter by website\store ??
            $collection = Mage::getModel('newsletter/subscriber')->getCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter('customer_id', 0) // need only guests b/c customers have already been processed
                ->setPageSize(Drip_Connect_Model_ApiCalls_Helper::MAX_BATCH_SIZE)
                ->setCurPage($page++)
                ->load();

            $batchCustomer = array();
            $batchEvents = array();
            foreach ($collection as $subscriber) {

                $dataCustomer = Drip_Connect_Helper_Data::prepareGuestSubscriberData($subscriber);
                $dataCustomer['tags'] = array('Synced from Magento');
                $batchCustomer[] = $dataCustomer;

                $dataEvents = array(
                    'email' => $subscriber->getSubscriberEmail(),
                    'action' => ($subscriber->getDrip()
                        ? Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_CUSTOMER_UPDATED
                        : Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_CUSTOMER_NEW),
                );
                $batchEvents[] = $dataEvents;

                if (!$subscriber->getDrip()) {
                    $subscriber->setNeedToUpdate(1);
                    $subscriber->setDrip(1);
                }
            }

            if (count($batchCustomer)) {
                $response = Mage::getModel('drip_connect/ApiCalls_Helper_Batches_Subscribers', array(
                    'batch' => $batchCustomer,
                    'store_id' => $storeId,
                ))->call();

                if (empty($response) || $response->getResponseCode() != 201) { // drip success code for this action
                    $result = false;
                    break;
                }

                $response = Mage::getModel('drip_connect/ApiCalls_Helper_Batches_Events', array(
                    'batch' => $batchEvents,
                    'store_id' => $storeId,
                ))->call();

                if (empty($response) || $response->getResponseCode() != 201) { // drip success code for this action
                    $result = false;
                    break;
                }

                foreach ($collection as $subscriber) {
                    if ($subscriber->getNeedToUpdate()) {
                        $subscriber->save();
                    }
                }

                sleep($delay);
            }
        } while ($page <= $collection->getLastPageNumber());

        return $result;
    }

    /**
     * @param int $storeId
     *
     * @return bool
     */
    protected function syncCustomersForStore($storeId)
    {
        Mage::helper('drip_connect')->setCustomersSyncStateToStore($storeId, Drip_Connect_Model_Source_SyncState::PROGRESS);

        $delay = (int) Mage::getStoreConfig('dripconnect_general/api_settings/batch_delay');

        $result = true;
        $page = 1;
        do {
            // todo filter by website\store ??
            $collection = Mage::getModel('customer/customer')
                ->getCollection()
                ->addAttributeToSelect('*')
                ->setPageSize(Drip_Connect_Model_ApiCalls_Helper::MAX_BATCH_SIZE)
                ->setCurPage($page++)
                ->load();

            $batchCustomer = array();
            foreach ($collection as $customer) {
                $dataCustomer = Drip_Connect_Helper_Data::prepareCustomerData($customer);
                $dataCustomer['tags'] = array('Synced from Magento');
                $batchCustomer[] = $dataCustomer;

                if (!$customer->getDrip()) {
                    $customer->setNeedToUpdateAttribute(1);
                    $customer->setDrip(1);  // 'drip' flag on customer means it was sent to drip sometime
                }
            }

            if (count($batchCustomer)) {
                $response = Mage::getModel('drip_connect/ApiCalls_Helper_Batches_Subscribers', array(
                    'batch' => $batchCustomer,
                    'store_id' => $storeId,
                ))->call();

                if (empty($response) || $response->getResponseCode() != 201) { // drip success code for this action
                    $result = false;
                    break;
                }

                foreach ($collection as $customer) {
                    if ($customer->getNeedToUpdateAttribute()) {
                        $customer->getResource()->saveAttribute($customer, 'drip');
                    }
                }

                sleep($delay);
            }

        } while ($page <= $collection->getLastPageNumber());

        return $result;
    }
}
