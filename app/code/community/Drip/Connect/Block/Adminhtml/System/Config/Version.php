<?php

class Drip_Connect_Block_Adminhtml_System_Config_Version extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element){
        $config = Mage::getConfig();
        $moduleConfig = $config->getModuleConfig('Drip_Connect');

        $element->setData('value', "[Dummy]");

        $html = parent::render($element);
        $html = str_replace("[Dummy]", '<b>'.(string)$moduleConfig->version."</b>", $html);

        return $html;
    }
}
