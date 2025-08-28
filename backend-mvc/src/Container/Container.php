<?php

namespace App\Container;

class Container
{
    private array $services = [];
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function get(string $id)
    {
        if (!isset($this->services[$id])) {
            if (!isset($this->config[$id])) {
                throw new \Exception("Service '$id' not found in container");
            }
            $this->services[$id] = $this->config[$id]($this);
        }
        return $this->services[$id];
    }
} 