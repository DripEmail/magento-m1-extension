<?php

require_once __DIR__.'/../abstract.php';

class Mage_Shell_Drip_CreateCustomer extends Mage_Shell_Abstract
{
    public function run()
    {
        $stdin = fopen('php://stdin', 'r');
        $data = stream_get_contents($stdin);
        $json = json_decode($data, true);

        if ($json === null) {
            throw new \Exception('Null JSON parse');
        }

        $defaults = array(
            // 'websiteId' => 1,
            // 'store' => 1,
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'jd1@example.com',
            'password' => 'somepassword',
        );
        $fullData = array_replace_recursive($defaults, $json);

        $customer = Mage::getModel("customer/customer");

        // This assumes that you properly name all of the attributes. But we control both ends, so it should be fine.
        foreach ($fullData as $key => $value) {
            $methodName = "set".ucfirst($key);
            $customer->$methodName($value);
        }

        $customer->save();
    }
}

$shell = new Mage_Shell_Drip_CreateCustomer();
$shell->run();
