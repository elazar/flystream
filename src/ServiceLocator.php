<?php

namespace Elazar\Flystream;

class ServiceLocator
{
    private static ?self $instance = null;

    private Container $container;

    public function __construct()
    {
        $this->container = new Container();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function get(string $class): object
    {
        return self::getInstance()->container->get($class);
    }

    public static function set(string $class, string|object $instanceOrClass): void
    {
        self::getInstance()->container->set($class, $instanceOrClass);
    }

    public static function setInstance(self $instance): void
    {
        self::$instance = $instance;
    }
}
