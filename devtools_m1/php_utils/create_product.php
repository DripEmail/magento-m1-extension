<?php

require_once __DIR__.'/../abstract.php';

class Mage_Shell_Drip_CreateProduct extends Mage_Shell_Abstract
{
    public function run()
    {
        $stdin = fopen('php://stdin', 'r');
        $data = stream_get_contents($stdin);
        $json = json_decode($data, true);

        if ($json === null) {
            throw new \Exception('Null JSON parse');
        }

        $type = $json['typeId'];
        switch ($type) {
            case 'simple':
            case '':
            case null:
                $this->buildSimpleProduct($json)->save();
                break;
            case 'configurable':
                $this->buildConfigurableProduct($json);
                break;
            default:
                throw new \Exception("Unsupported type: ${type}");
        }
    }

    protected function buildSimpleProduct($data)
    {
        $product = Mage::getModel('catalog/product');

        $defaultAttrSetId = Mage::getModel('catalog/product')->getDefaultAttributeSetId();

        $defaults = array(
            "storeId" => 1,
            "websiteIds" => [1],
            "typeId" => "simple",
            "weight" => 4.0000,
            "status" => 1, //product status (1 - enabled, 2 - disabled)
            "taxClassId" => 0, //tax class (0 - none, 1 - default, 2 - taxable, 4 - shipping)
            "price" => 11.22,
            "cost" => 22.33,
            "attributeSetId" => $defaultAttrSetId,
            "createdAt" => strtotime('now'),
            "updatedAt" => strtotime('now'),
            "stockData" => array(
                "use_config_manage_stock" => 0,
                "manage_stock" => 1,
                "is_in_stock" => 1,
                "qty" => 999
            ),
        );
        $fullData = array_replace_recursive($defaults, $data);

        // This assumes that you properly name all of the attributes. But we control both ends, so it should be fine.
        foreach ($fullData as $key => $value) {
            $methodName = "set".ucfirst($key);
            $product->$methodName($value);
        }

        $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH); //catalog and search visibility

        return $product;
    }

    protected function buildAttribute($title, $options)
    {
        $installer = new Mage_Eav_Model_Entity_Setup('core_setup');
        $installer->startSetup();
        $installer->addAttribute('catalog_product', $title, array(
            'group' => 'General',
            'label' => $title,
            'input' => 'select',
            'type' => 'varchar',
            'required' => 0,
            'visible_on_front' => false,
            'filterable' => 0,
            'filterable_in_search' => 0,
            'searchable' => 0,
            'used_in_product_listing' => true,
            'visible_in_advanced_search' => false,
            'comparable' => 0,
            'user_defined' => 1,
            'is_configurable' => 0,
            'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
            'option' => array('values' => $options),
            'note' => '',
        ));

        $installer->endSetup();

        // Obtain and return the attribute.
        return Mage::getModel('eav/config')->getAttribute('catalog_product', $title);
    }

    protected function buildConfigurableProduct($data)
    {
        $attributes = $data['attributes'];
        unset($data['attributes']);

        $configProduct = $this->buildSimpleProduct($data);
        $configProduct->setStockData(array(
            'use_config_manage_stock' => 0, //'Use config settings' checkbox
            'manage_stock' => 1, //manage stock
            'is_in_stock' => 1, //Stock Availability
        ));

        $attributeIds = array();
        $configurableProductsData = array();

        foreach ($attributes as $attrName => $attrValues) {
            $attribute = $this->buildAttribute($attrName, array_keys($attrValues));
            $attributeIds[] = $attribute->getId();

            foreach ($attrValues as $option => $simpleProductData) {
                $simpleProduct = $this->buildSimpleProduct($simpleProductData);
                $optionId = $attribute->setStoreId(0)->getSource()->getOptionId($option);
                $simpleProduct->setData($attrName, $optionId);
                $simpleProduct->save();

                $configurableProductsData[$simpleProduct->getId()][] = array(
                    'label' => $option,
                    'attribute_id' => $attribute->getId(),
                    'value_index' => $optionId,
                    'is_percent' => '0', //fixed/percent price for this option
                    'pricing_value' => $simpleProduct->getPrice()
                );
            }
        }

        // Set attribute data
        $configProduct->getTypeInstance()->setUsedProductAttributeIds($attributeIds);
        $configurableAttributesData = $configProduct->getTypeInstance()->getConfigurableAttributesAsArray();
        $configProduct->setCanSaveConfigurableAttributes(true);
        $configProduct->setConfigurableAttributesData($configurableAttributesData);
        $configProduct->setConfigurableProductsData($configurableProductsData);

        $configProduct->save();
    }
}

$shell = new Mage_Shell_Drip_CreateProduct();
$shell->run();
