<?php

class Drip_Connect_Model_Cron_Customers
{
    protected function getLogger()
    {
        return Mage::helper('drip_connect/logger')->logger();
    }

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
        $globalConfig = Drip_Connect_Model_Configuration::forGlobalScope();

        ini_set('memory_limit', $globalConfig->getMemoryLimit());

        $storeIds = array();
        $stores = Mage::app()->getStores(false, false);

        $trackDefaultStatus = false;
        if ($globalConfig->getCustomersSyncState() == Drip_Connect_Model_Source_SyncState::QUEUED) {
            $trackDefaultStatus = true;
            $storeIds = array_keys($stores);
            $globalConfig->setCustomersSyncState(Drip_Connect_Model_Source_SyncState::PROGRESS);
        } else {
            foreach ($stores as $storeId => $store) {
                $storeConfig = new Drip_Connect_Model_Configuration($storeId);
                if ($storeConfig->getCustomersSyncState() == Drip_Connect_Model_Source_SyncState::QUEUED) {
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
                $customerResult = $this->syncCustomersForStore($storeConfig);
            } catch (\Exception $e) {
                $this->getLogger()->log($e->__toString(), Zend_Log::ERR);
                $customerResult = false;
            }

            try {
                $subscriberResult = $this->syncGuestSubscribersForStore($storeConfig);
            } catch (\Exception $e) {
                $this->getLogger()->log($e->__toString(), Zend_Log::ERR);
                $subscriberResult = false;
            }

            if ($subscriberResult && $customerResult) {
                $status = Drip_Connect_Model_Source_SyncState::READY;
            } else {
                $status = Drip_Connect_Model_Source_SyncState::READYERRORS;
            }

            $statuses[$storeId] = $status;

            $storeConfig->setCustomersSyncState($status);
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

            $globalConfig->setCustomersSyncState($status);
        }
    }

    /**
     * @param Drip_Connect_Model_Configuration $config
     *
     * @return bool
     */
    protected function syncGuestSubscribersForStore($config)
    {
        $config->setCustomersSyncState(Drip_Connect_Model_Source_SyncState::PROGRESS);

        $delay = $config->getBatchDelay();

        $result = true;
        $page = 1;
        do {
            $collection = Mage::getModel('newsletter/subscriber')->getCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter('customer_id', 0) // need only guests b/c customers have already been processed
                ->addFieldToFilter('store_id', $config->getStoreId())
                ->setPageSize(Drip_Connect_Model_ApiCalls_Helper::MAX_BATCH_SIZE)
                ->setCurPage($page++)
                ->load();

            $batchCustomer = array();
            $batchEvents = array();
            foreach ($collection as $subscriber) {
                $email = $subscriber->getSubscriberEmail();
                if (!Mage::helper('drip_connect')->isEmailValid($email)) {
                    $this->getLogger()->log(
                        "Skipping newsletter subscriber event during sync due to unusable email",
                        Zend_Log::NOTICE
                    );
                    continue;
                }

                $dataCustomer = Drip_Connect_Helper_Data::prepareGuestSubscriberData($subscriber);
                $dataCustomer['tags'] = array('Synced from Magento');
                $batchCustomer[] = $dataCustomer;

                $dataEvents = array(
                    'email' => $email,
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

            if (!empty($batchCustomer)) {
                $response = Mage::getModel(
                    'drip_connect/ApiCalls_Helper_Batches_Subscribers',
                    array(
                        'batch' => $batchCustomer,
                        'store_id' => $config->getStoreId(),
                    )
                )->call();

                if (empty($response) || $response->getResponseCode() != 201) { // drip success code for this action
                    $result = false;
                    break;
                }

                $apiCall = new Drip_Connect_Model_ApiCalls_Helper_Batches_Events($config, $batchEvents);
                $response = $apiCall->call();

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
     * @param Drip_Connect_Model_Configuration $config
     *
     * @return bool
     */
    protected function syncCustomersForStore($config)
    {
        $config->setCustomersSyncState(Drip_Connect_Model_Source_SyncState::PROGRESS);

        $delay = $config->getBatchDelay();

        $websiteId = Mage::app()->getStore($config->getStoreId())->getWebsiteId();

        $result = true;
        $page = 1;
        do {
            $collection = Mage::getModel('customer/customer')
                ->getCollection()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('website_id', array('in' => array(0, $websiteId)))
                ->setPageSize(Drip_Connect_Model_ApiCalls_Helper::MAX_BATCH_SIZE)
                ->setCurPage($page++)
                ->load();

            $batchCustomer = array();
            foreach ($collection as $customer) {
                $email = $customer->getEmail();
                if (!Mage::helper('drip_connect')->isEmailValid($email)) {
                    $this->getLogger()->log(
                        "Skipping subscriber during sync due to unusable email ({$email})",
                        Zend_Log::NOTICE
                    );
                    continue;
                }

                $dataCustomer = Drip_Connect_Helper_Data::prepareCustomerData($customer);
                $dataCustomer['tags'] = array('Synced from Magento');
                $batchCustomer[] = $dataCustomer;

                if (!$customer->getDrip()) {
                    $customer->setNeedToUpdateAttribute(1);
                    $customer->setDrip(1);  // 'drip' flag on customer means it was sent to drip sometime
                }
            }

            if (!empty($batchCustomer)) {
                $response = Mage::getModel(
                    'drip_connect/ApiCalls_Helper_Batches_Subscribers',
                    array(
                        'batch' => $batchCustomer,
                        'store_id' => $config->getStoreId(),
                    )
                )->call();

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
