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
            $collection = Mage::getModel('sales/order')
                ->getCollection()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('state', array('nin' => array(
                    Mage_Sales_Model_Order::STATE_CANCELED,
                    Mage_Sales_Model_Order::STATE_CLOSED
                    )))
                ->setPageSize(Drip_Connect_Model_ApiCalls_Helper::MAX_BATCH_SIZE)
                ->setCurPage($page++)
                ->load();

            $batch = array();
            foreach ($collection as $order) {
                $data = Mage::helper('drip_connect/order')->getOrderDataNew($order);
                $data['occurred_at'] = $order->getCreatedAt();
                $batch[] = $data;
            }

            $response = Mage::getModel('drip_connect/ApiCalls_Helper_Batches_Orders', array(
                'batch' => $batch,
                'account' => $accountId,
            ))->call();

            if ($response->getResponseCode() != 202) { // drip success code for this action
                $result = 0;
                break;
            }
        } while ($page <= $collection->getLastPageNumber());

        $this->getResponse()->setBody($result);
    }
}
