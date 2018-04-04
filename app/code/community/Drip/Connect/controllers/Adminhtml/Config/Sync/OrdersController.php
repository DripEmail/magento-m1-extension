<?php

class Drip_Connect_Adminhtml_Config_Sync_OrdersController
    extends Mage_Adminhtml_Controller_Action
{
    /**
     * prepare and send orders data
     *
     * @return void
     */
    public function runAction()
    {
        $accountId = $this->getRequest()->getParam('account_id');
        $result = 1;
        $page = 1;
        do {
            // todo filder orders
            $collection = Mage::getModel('sales/order')
                ->getCollection()
                ->addAttributeToSelect('*')
                ->setPageSize(Drip_Connect_Model_ApiCalls_Helper::MAX_BATCH_SIZE)
                ->setCurPage($page++)
                ->load();

            $batch = array();
            foreach ($collection as $customer) {
                $batch[] = Drip_Connect_Helper_Data::prepareCustomerData($customer);
            }

            // todo API call

            if ($response->getResponseCode() != 202) { // drip success code for this action
                $result = 0;
                break;
            }
        } while ($page <= $collection->getLastPageNumber());

        $this->getResponse()->setBody($result);
    }
}
