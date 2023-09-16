<?php

namespace EurekaClient\Interfaces;

interface DiscoveryStrategy
{
    /**
     * @param $instances array
     * @return string
     */
    public function getInstance($instances);
}
