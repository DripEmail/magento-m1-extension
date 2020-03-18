<?php   
require_once __DIR__.'/../abstract.php';
class Mage_Shell_Drip_CreateAttributes extends Mage_Shell_Abstract
{
    public function run()
    {
        $stdin = fopen('php://stdin', 'r');
        $data = stream_get_contents($stdin);
        $json = json_decode($data, true);

        if ($json === null) {
            throw new \Exception('Null JSON parse');
        }

        if(count($json) < 1) {
            throw new \Exception('Expected array of attributes');
        }

        $this->buildAttributes($json);
    }

    private function buildAttributes($attribute_data_array) {
        foreach ($attribute_data_array as $attribute_data => $attribute_values) {
            $this->buildAttribute($attribute_values);
        }
    }

    private function buildAttribute($attribute_values)
    {
        $attribute_data = $this->buildAttributeDataStructure($attribute_values);

        $installer = new Mage_Eav_Model_Entity_Setup('core_setup');
        $installer->startSetup();
        $installer->addAttribute('catalog_product', $attribute_values['code'], $attribute_data);
        $installer->endSetup();
    }

    private function buildAttributeDataStructure($data) {
        $attribute_data = array(
            'type' => $data['type'],
            'input' => $data['input'],
            'visible' => $data['visible'],
            'required' => $data['required'],
            'is_configurable' => $data['configurable'],
            'filterable' => $data['filterable'],
            'filterable_in_search' => $data['filterable'],
            'visible_on_front' => $data['visible_on_front'],
            'apply_to' => $data['apply_to'],
            'group' => 'General',
            'user_defined' => 1,
            'searchable' => 0,
            'comparable' => 0,
            'used_in_product_listing' => 1,
            'visible_in_advanced_search' => 1,
            'note' => '',
            'backend' => '',
            'frontend' => '',
            'class' => '',
            'source' => ''
        );

        switch($data['scope']) {
            case "website":
                $attribute_data['global'] = Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE;
                break;
            case "store":
                $attribute_data['global']= Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE;
                break;
            default:
                $attribute_data['global']= Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL;
        }

        if(isset($data['label'])) {
            $attribute_data['label'] = $data['label'];
        }

        if(isset($data['options'])) {
            $attribute_data['option'] = array('values' => $data['options']);
        }

        return $attribute_data;
    }
}

$shell = new Mage_Shell_Drip_CreateAttributes();
$shell->run();
