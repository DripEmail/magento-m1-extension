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
            if ($this->syncCustomersWithAccount($accountId)) {
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
    protected function syncCustomersWithAccount($accountId)
    {
        $stores = $this->accounts[$accountId];
        foreach ($stores as $storeId) {
            Mage::helper('drip_connect')->setCustomersSyncStateToStore($storeId, Drip_Connect_Model_Source_SyncState::PROGRESS);
        }

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
            $batchEvents = array();
            foreach ($collection as $customer) {
                $dataCustomer = Drip_Connect_Helper_Data::prepareCustomerData($customer);
                $dataCustomer['tags'] = array('Synced from Magento');
                $batchCustomer[] = $dataCustomer;

                $dataEvents = array(
                    'email' => $customer->getEmail(),
                    'action' => ($customer->getDrip()
                        ? Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_CUSTOMER_UPDATED
                        : Drip_Connect_Model_ApiCalls_Helper_RecordAnEvent::EVENT_CUSTOMER_NEW),
                );
                $batchEvents[] = $dataEvents;

                if (!$customer->getDrip()) {
                    $customer->setNeedToUpdateAttribute(1);
                    $customer->setDrip(1);
                }
            }

            $response = Mage::getModel('drip_connect/ApiCalls_Helper_Batches_Subscribers', array(
                'batch' => $batchCustomer,
                'account' => $accountId,
            ))->call();

            if ($response->getResponseCode() != 201) { // drip success code for this action
                $result = false;
                break;
            }

            $response = Mage::getModel('drip_connect/ApiCalls_Helper_Batches_Events', array(
                'batch' => $batchEvents,
                'account' => $accountId,
            ))->call();

            if ($response->getResponseCode() != 201) { // drip success code for this action
                $result = false;
                break;
            }

            foreach ($collection as $customer) {
                if ($customer->getNeedToUpdateAttribute()) {
                    $customer->getResource()->saveAttribute($customer, 'drip');
                }
            }
        } while ($page <= $collection->getLastPageNumber());

        return $result;
    }
}
