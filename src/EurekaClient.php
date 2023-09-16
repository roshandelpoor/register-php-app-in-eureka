<?php

namespace EurekaClient;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class EurekaClient
{
    private $eurekaConfig;
    private $guzzleClient;
    private $instances;
    private $commonHeaders = [
        'Content-Type' => 'application/json',
        'Accept'       => 'application/json'
    ];

    public function __construct($eurekaConfig)
    {
        $this->eurekaConfig = new EurekaConfig($eurekaConfig);
        $this->guzzleClient = new GuzzleClient();
    }

    public function getEurekaConfig()
    {
        return $this->eurekaConfig;
    }

    /**
     * register with eureka
     * @throws Exception
     */
    public function register()
    {
        $this->echoMessageInOutput("[" . date("Y-m-d H:i:s") . "]" . " Registering...");
        try {
            $response = $this->guzzleClient->request(
                'POST',
                $this->eurekaConfig->getEurekaDefaultUrl() . '/eureka/apps/' . $this->eurekaConfig->getAppName(),
                [
                    'headers' => $this->commonHeaders,
                    'body'    => json_encode($this->eurekaConfig->getRegistrationConfig())
                ]
            );

            if ($response->getStatusCode() != 204) {
                throw new Exception("Could not register with Eureka.");
            }
        } catch (GuzzleException $e) {
            $this->echoMessageInOutput("[" . date("Y-m-d H:i:s") . "]" . "GuzzleException was happened because of (" . $e->getMessage() . ")(code: " . $e->getCode() . ")");
        }
    }

    // is registered with eureka
    public function isRegistered()
    {
        try {
            $response = $this->guzzleClient->request(
                'GET',
                $this->eurekaConfig->getEurekaDefaultUrl() . '/eureka/apps/' . $this->eurekaConfig->getAppName() . '/' . $this->eurekaConfig->getInstanceId(),
                [
                    'headers' => $this->commonHeaders
                ]
            );
            $statusCode = $response->getStatusCode();

        } catch (GuzzleException $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }

        return $statusCode == 200;
    }

    /**
     * de-register from eureka
     * @throws Exception
     */
    public function deRegister()
    {
        $this->echoMessageInOutput("[" . date("Y-m-d H:i:s") . "]" . " De-registering...");
        try {
            $response = $this->guzzleClient->request(
                'DELETE',
                $this->eurekaConfig->getEurekaDefaultUrl() . '/eureka/apps/' . $this->eurekaConfig->getAppName() . '/' . $this->eurekaConfig->getInstanceId(),
                [
                    'headers' => $this->commonHeaders
                ]
            );

            if ($response->getStatusCode() != 200) {
                throw new Exception("Cloud not de-register from Eureka.");
            }
        } catch (GuzzleException $e) {
            $this->echoMessageInOutput("[" . date("Y-m-d H:i:s") . "]" . "GuzzleException was happened because of (" . $e->getMessage() . ")(code: " . $e->getCode() . ")");
        }
    }

    // send heartbeat to eureka
    public function heartbeat()
    {
        $this->echoMessageInOutput("[" . date("Y-m-d H:i:s") . "]" . " Sending heartbeat...");
        try {
            $response = $this->guzzleClient->request(
                'PUT',
                $this->eurekaConfig->getEurekaDefaultUrl() . '/eureka/apps/' . $this->eurekaConfig->getAppName() . '/' . $this->eurekaConfig->getInstanceId(),
                [
                    'headers' => $this->commonHeaders
                ]);

            if ($response->getStatusCode() != 200) {
                $this->echoMessageInOutput("[" . date("Y-m-d H:i:s") . "]" . " Heartbeat failed... (code: " . $response->getStatusCode() . ")");
            }
        } catch (GuzzleException $e) {
            $this->echoMessageInOutput("[" . date("Y-m-d H:i:s") . "]" . "GuzzleException was happened because of (" . $e->getMessage() . ")(code: " . $e->getCode() . ")");
        } catch (Exception $e) {
            $this->echoMessageInOutput("[" . date("Y-m-d H:i:s") . "]" . "Heartbeat failed because of connection error... (code: " . $e->getCode() . ")");
        }
    }

    // register and send heartbeats periodically
    public function start()
    {
        $this->register();

        while (true) {
            $this->heartbeat();
            sleep($this->eurekaConfig->getHeartbeatInterval());
        }
    }

    /**
     * @throws Exception
     */
    public function fetchInstance($appName)
    {
        return $this->eurekaConfig->getDiscoveryStrategy()->getInstance($this->fetchInstances($appName));
    }

    /**
     * @throws Exception
     */
    public function fetchInstances($appName)
    {
        if (!empty($this->instances[$appName])) {
            return $this->instances[$appName];
        }

        $provider = $this->getEurekaConfig()->getInstanceProvider();

        try {
            $response = $this->guzzleClient->request(
                'GET',
                $this->eurekaConfig->getEurekaDefaultUrl() . '/eureka/apps/' . $appName,
                [
                    'headers' => $this->commonHeaders
                ]);

            if ($response->getStatusCode() != 200) {
                if (!empty($provider)) {
                    return $provider->getInstances($appName);
                }
                throw new Exception("Could not get instances from Eureka.");
            }

            $body = json_decode($response->getBody()->getContents());
            if (!isset($body->application->instance)) {
                if (!empty($provider)) {
                    return $provider->getInstances($appName);
                }
                throw new Exception("No instance found for '" . $appName . "'.");
            }

            $this->instances[$appName] = $body->application->instance;
            return $this->instances[$appName];
        } catch (RequestException $e) {
            if (!empty($provider)) {
                return $provider->getInstances($appName);
            }

            throw new Exception("No instance found for '" . $appName . "'.");
        } catch (GuzzleException $e) {
            $this->echoMessageInOutput("[" . date("Y-m-d H:i:s") . "]" . "GuzzleException was happened because of (" . $e->getMessage() . ")(code: " . $e->getCode() . ")");
        }

        return true;
    }

    private function echoMessageInOutput($message)
    {
        if (php_sapi_name() !== 'cli') {
            return;
        }
        echo $message . "\n";
    }
}
