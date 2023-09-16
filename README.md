Register PHP App In Eureka (Netflix Eureka Spring Cloud)
=========================
This Package is Eureka Client And it's a library that interacts with the Eureka Server to register, deregister, and
discover other services. (
Spring Boot - Eureka Server is an application that holds the information about all client-service applications. Every
Micro service will register into the Eureka server and Eureka server knows all the client applications running on each
port and IP address. Eureka Server is also known as Discovery Server.)

Finally, I Want To Emphasize that This is NOT New Package (ONLY Fix Bug for package piwvh/php-eureka) Because of My
Microservices Projects that I have to custom it.

# Installation

You can install this package using [Composer]:

`composer require "roshandelpoor/register-php-app-in-eureka"`

# Documentation

### Create Eureka Client

The very first thing you need to do is to create an instance of `EurekaClient` using your own configuration:

```php
$client = new EurekaClient([
    'eurekaDefaultUrl' => 'http://localhost:8761/myeureka',
    'hostName' => gethostname(),
    'appName' => 'NAME_APP_FOR_REGISTER_IN_EUREKA',
    'ip' => '127.0.0.1',
    'port' => ['8000', true],
    'homePageUrl' => 'http://localhost:8000',
    'statusPageUrl' => 'http://localhost:8000/info',
    'healthCheckUrl' => 'http://localhost:8000/health'
]);
```

List of all(default) available configuration are as follows:

- `eurekaDefaultUrl`  (default: `http://localhost:8761/myeureka`);
- `hostName`
- `appName`
- `ip`
- `status`            (default: `UP`)
- `overriddenStatus`  (default: `UNKNOWN`)
- `port`
- `securePort`        (default: `['443', false]`)
- `countryId `        (default: `1`)
- `dataCenterInfo`    (default: `['com.netflix.appinfo.InstanceInfo$DefaultDataCenterInfo', 'MyOwn']`)
- `homePageUrl`
- `statusPageUrl`
- `healthCheckUrl`
- `vipAddress`
- `secureVipAddress`
- `heartbeatInterval` (default: `30`)
- `discoveryStrategy` (default: `RandomStrategy`)
- `instanceProvider`

You can also change the configuration after creating `EurekaClient` instance, using setter methods:

```php

$client->getEurekaConfig()->setAppName("NAME_APP_FOR_REGISTER_IN_EUREKA");

```

### Operations

After creating EurekaClient instance, there will be multiple operations to perform:

- **Registration:** register your service instance with Eureka

```php

$client->register();

```

- **De-registration:** de-register your service instance from Eureka

```php

$client->deRegister();

```

- **Heartbeat:** send heartbeat to Eureka, to show the client is up (one-time heartbeat)

```php

$client->heartbeat();

```

You can register your instance and send periodic heartbeat using `start()` method:

```php

$client->start();

```

Using this method, first your service gets registered with Eureka using the
configuration you have provided. Then, a heartbeat will be sent to the Eureka periodically, based
on `heartbeatInterval` configuration value. This interval time can be changed just like any other
configuration item:

```php

$client->getEurekaConfig()->setHeartbeatInterval(30); // 30 seconds

``` 

It's apparent that this method should be used in CLI.

- **Service Discovery**: fetch an instance of a service from Eureka:

```php

$instance = $client->fetchInstance("the-service");
$homePageUrl = $instance->homePageUrl;

```

### Discovery Strategy

When fetching instances of a service from Eureka, you probably get a list of available
instances. You can choose one of them based on your desired strategy
of load balancing. For example, a Round-robin or a Random strategy might be your choice.

Currently, this library only supports `RandomStrategy`, however, you can create your custom
strategy by implementing `getInstance()` method of `DiscoveryStrategy` interface:

```php
class RoundRobinStrategy implements DiscoveryStrategy {

    public function getInstance($instances) {
        // return an instance
    }
    
}
```

Then, all you have to do is to introduce your custom strategy to `EurekaClient` instance:

```php

$client->getEurekaConfig()->setDiscoveryStrategy(new RoundRobinStrategy());

```

### Local Registry and Caching

Failure is inevitable, specially in cloud-native applications. Thus, sometimes Eureka may not be available because of
failure.
In these cases, we should have a local registry of services to avoid cascading failures.

By default, if Eureka is down, the `fetchInstance()` method fails, so an
exception would be thrown and the application cannot continue to work. To solve this
problem, you should create a local registry of services.

There is an interface called `InstanceProvider` which you can make use of, by
implementing `getInstances()` method of this interface and returning instances
of a service based on your ideal logic.

```php
class MyProvider implements InstanceProvider {

    public function getInstances($appName) { 
        // return cached instances of the service from the Redis 
    }
}
```

In this example, we have cached the instances of the service in the Redis and
are loading them when Eureka is down.

After creating your custom provider, just make it work by adding it to the configuration:

```php

$client->getEurekaConfig()->setInstanceProvider(new MyProvider());

```

Your custom provider only gets called when Eureka is down or is not answering properly.

That's all you need to do. By adding this functionality, your application keeps working even
when Eureka is down.

For caching all available instances of a specific service, you can call `fetchInstances()` method
which fetches all the instances of the service from Eureka:

```php

$instances = $client->fetchInstances("get_NAME_Another_APP_From_EUREKA");

```

# Contributing

If you find any bugs or have suggestions for new features, feel free to open an issue or submit a pull request on GitHub.

# License

Super Tools is open-source software licensed under the MIT license.

