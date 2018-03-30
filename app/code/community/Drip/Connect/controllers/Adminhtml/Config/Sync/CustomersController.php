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

        //$this->getResponse()->setBody(1);
    }
}
