<?php

namespace Elazar\Flystream;

use League\Flysystem\UnixVisibility\VisibilityConverter;
use Psr\Log\LoggerInterface;

class PhpStreamWrapper extends StreamWrapper
{
    private static Container $container;

    public static function setContainer(Container $container): void
    {
        static::$container = $container;
    }
    
    public function __construct()
    {
        parent::__construct(
            static::$container->get(VisibilityConverter::class),
            static::$container->get(FilesystemRegistry::class),
            static::$container->get(LockRegistryInterface::class),
            static::$container->get(BufferFactoryInterface::class),
            static::$container->get(LoggerInterface::class)
        );
    }
}
