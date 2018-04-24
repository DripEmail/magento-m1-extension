<?php

class Drip_Connect_Adminhtml_Config_Sync_CustomersController
    extends Mage_Adminhtml_Controller_Action
{
    /**
     * prepare and send customers data
     *
     * @return void
     */
    public function runAction()
    {
        $accountId = $this->getRequest()->getParam('account_id');
        $result = 1;
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
                $result = 0;
                break;
            }

            $response = Mage::getModel('drip_connect/ApiCalls_Helper_Batches_Events', array(
                'batch' => $batchEvents,
                'account' => $accountId,
            ))->call();

            if ($response->getResponseCode() != 201) { // drip success code for this action
                $result = 0;
                break;
            }

            foreach ($collection as $customer) {
                if ($customer->getNeedToUpdateAttribute()) {
                    $customer->getResource()->saveAttribute($customer, 'drip');
                }
            }
        } while ($page <= $collection->getLastPageNumber());

        $this->getResponse()->setBody($result);
    }
}
