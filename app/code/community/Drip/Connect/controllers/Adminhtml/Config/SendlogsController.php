<?php

class Drip_Connect_Adminhtml_Config_SendlogsController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('dripconnect_general');
    }

    /**
     * send logs to support
     *
     * @return void
     */
    public function runAction()
    {
        $storeId = $this->getRequest()->getParam('store_id');

        $result = [
            'status' => Drip_Connect_Helper_Logs::SENDLOGS_RESPONSE_OK,
            'message' => Mage::helper('drip_connect')->__('Logs have been sent'),
        ];

        try {
            Mage::helper('drip_connect/logs')->sendLogs($storeId);
        } catch (\Exception $e) {
            $result['status'] = Drip_Connect_Helper_Logs::SENDLOGS_RESPONSE_FAIL;
            $result['message'] = $e->getMessage();
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}
