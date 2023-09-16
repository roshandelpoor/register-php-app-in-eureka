<?php

namespace EurekaClient\Discovery;

use EurekaClient\Interfaces\DiscoveryStrategy;

class RandomStrategy implements DiscoveryStrategy
{
    public function getInstance($instances)
    {
        if (count($instances) == 0)
            return null;

        return $instances[rand(0, count($instances) - 1)];
    }
}
