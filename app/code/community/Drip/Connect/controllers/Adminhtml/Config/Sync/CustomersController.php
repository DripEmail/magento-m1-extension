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

            $batch = array();
            foreach ($collection as $customer) {
                $batch[] = Drip_Connect_Helper_Data::prepareCustomerData($customer);
            }

            $response = Mage::getModel('drip_connect/ApiCalls_Helper_Batches_Subscribers', array(
                'batch' => $batch,
                'account' => $accountId,
            ))->call();

            if ($response->getResponseCode() != 201) {
                $result = 0;
                break;
            }
        } while ($page <= $collection->getLastPageNumber());

        $this->getResponse()->setBody($result);
    }
}
