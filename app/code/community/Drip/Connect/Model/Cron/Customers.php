<?php

class Drip_Connect_Model_Cron_Customers
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
     * run customers sync for them
     */
    public function syncCustomers()
    {
        $this->getAccountsToSyncCustomers();

        foreach ($this->accounts as $accountId => $stores) {
            try {
                $result = $this->syncCustomersWithAccount($accountId);
                if ($result) {
                    $result = $this->syncGuestSubscribersWithAccount($accountId);
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
            foreach ($stores as $storeId) {
                Mage::helper('drip_connect')->setCustomersSyncStateToStore($storeId, $status);
            }
        }
    }

    /**
     * populate accounts array
     */
    protected function getAccountsToSyncCustomers()
    {
        if (Mage::getStoreConfig('dripconnect_general/actions/sync_customers_data_state', 0) == Drip_Connect_Model_Source_SyncState::QUEUED) {
            $defAccount = Mage::getStoreConfig('dripconnect_general/api_settings/account_id', 0);
            $this->accounts[$defAccount][] = 0;
        }

        foreach (Mage::app()->getStores() as $store) {
            $storeId = $store->getStoreId();

            if (Mage::getStoreConfig('dripconnect_general/actions/sync_customers_data_state', $storeId) == Drip_Connect_Model_Source_SyncState::QUEUED) {
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
    protected function syncGuestSubscribersWithAccount($accountId)
    {
        $stores = $this->accounts[$accountId];
        foreach ($stores as $storeId) {
            Mage::helper('drip_connect')->setCustomersSyncStateToStore($storeId, Drip_Connect_Model_Source_SyncState::PROGRESS);
        }

        $result = true;
        $page = 1;
        do {
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

            $response = Mage::getModel('drip_connect/ApiCalls_Helper_Batches_Subscribers', array(
                'batch' => $batchCustomer,
                'account' => $accountId,
            ))->call();

            if (empty($response) || $response->getResponseCode() != 201) { // drip success code for this action
                $result = false;
                break;
            }

            $response = Mage::getModel('drip_connect/ApiCalls_Helper_Batches_Events', array(
                'batch' => $batchEvents,
                'account' => $accountId,
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
        } while ($page <= $collection->getLastPageNumber());

        return $result;
    }

    /**
     * @param int $accountId
     *
     * @return bool
     */
    protected function syncCustomersWithAccount($accountId)
    {
        $stores = $this->accounts[$accountId];
        foreach ($stores as $storeId) {
            Mage::helper('drip_connect')->setCustomersSyncStateToStore($storeId, Drip_Connect_Model_Source_SyncState::PROGRESS);
        }

        $delay = (int) Mage::getStoreConfig('dripconnect_general/api_settings/batch_delay');

        $result = true;
        $page = 1;
        do {
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

            $response = Mage::getModel('drip_connect/ApiCalls_Helper_Batches_Subscribers', array(
                'batch' => $batchCustomer,
                'account' => $accountId,
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

        } while ($page <= $collection->getLastPageNumber());

        return $result;
    }
}
