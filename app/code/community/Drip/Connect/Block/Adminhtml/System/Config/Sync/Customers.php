<?php

class Drip_Connect_Block_Adminhtml_System_Config_Sync_Customers
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Set template to itself
     *
     * @return Drip_Connect_Block_Adminhtml_System_Config_Sync_Customers
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('drip/connect/config/sync/customers.phtml');
        }
        return $this;
    }

    /**
     * Unset some non-related element parameters
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $originalData = $element->getOriginalData();
        $this->addData(array(
            'button_label' => Mage::helper('drip_connect')->__($originalData['button_label']),
            'html_id' => $element->getHtmlId(),
            'ajax_url' => Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/config_sync_customers/run'),
            'account_id' => Mage::getStoreConfig('dripconnect_general/api_settings/account_id', Mage::app()->getRequest()->getParam('store')),
        ));

        return $this->_toHtml();
    }

    /**
     * @return bool
     */
    protected function isSyncAvailable()
    {
        if (!Mage::helper('drip_connect')->isModuleActive()) {
            return false;
        }
        if (Mage::getStoreConfig('dripconnect_general/actions/sync_customers_data_state', Mage::app()->getRequest()->getParam('store')) != Drip_Connect_Model_Source_SyncState::READY) {
            return false;
        }
        return true;
    }
}
