<?php

require_once __DIR__ . '/../vendor/autoload.php';

class Example implements \EurekaClient\Interfaces\InstanceProvider
{
    public function getInstances($appName)
    {
        echo "Eureka didn't respond correctly.";

        $obj = new stdClass();
        $obj->homePageUrl = "http://roshandelpoor.ir";
        return [$obj];
    }
}

$client = new \EurekaClient\EurekaClient([
    'eurekaDefaultUrl' => 'http://localhost:8761/myeureka',
    'hostName'         => gethostname(),
    'appName'          => 'NAME_APP_FOR_REGISTER_IN_EUREKA',
    'ip'               => '127.0.0.1',
    'port'             => ['8000', true],
    'homePageUrl'      => 'http://localhost:8000',
    'statusPageUrl'    => 'http://localhost:8000/info',
    'healthCheckUrl'   => 'http://localhost:8000/health'
]);

$client->getEurekaConfig()->setInstanceProvider(new Example());

try {
    $client->register();
    $url = $client->fetchInstance("CONFIG_SERVER")->homePageUrl;
    var_dump($url);
} catch (\Exception $e) {
    echo $e->getMessage();
}
