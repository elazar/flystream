<?php

namespace Elazar\Flystream;

use League\Flysystem\PathNormalizer;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\UnixVisibility\VisibilityConverter;
use League\Flysystem\WhitespacePathNormalizer;
use Pimple\Container;
use Pimple\Psr11\Container as PsrContainer;
use Pimple\ServiceProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ServiceLocator implements ServiceProviderInterface
{
    private static ?self $instance = null;

    private Container $container;

    public function __construct()
    {
        $this->container = new Container();
        $this->register($this->container);
    }

    /**
     * @return void
     */
    public function register(Container $c)
    {
        $c[FilesystemRegistry::class] =
            fn () => new FilesystemRegistry();

        $c[PassThruPathNormalizer::class] =
            fn () => new PassThruPathNormalizer();

        $c[StripProtocolPathNormalizer::class] =
            fn () => new StripProtocolPathNormalizer(
                null,
                $c[WhitespacePathNormalizer::class]
            );

        $c[PathNormalizer::class] =
            fn () => $c[StripProtocolPathNormalizer::class];

        $c[WhitespacePathNormalizer::class] =
            fn () => new WhitespacePathNormalizer();

        $c[PortableVisibilityConverter::class] =
            fn () => new PortableVisibilityConverter();

        $c[VisibilityConverter::class] =
            fn () => $c[PortableVisibilityConverter::class];

        $c[LockRegistryInterface::class] =
            fn () => $c[LocalLockRegistry::class];

        $c[LocalLockRegistry::class] =
            fn () => new LocalLockRegistry();

        $c[NullLogger::class] =
            fn () => new NullLogger();

        $c[LoggerInterface::class] =
            fn () => $c[NullLogger::class];

        $c[BufferInterface::class] =
            fn () => $c[MemoryBuffer::class];

        $c[OverflowBuffer::class] =
            fn () => new OverflowBuffer();

        $c[FileBuffer::class] =
            fn () => new FileBuffer();

        $c[MemoryBuffer::class] =
            fn () => new MemoryBuffer();
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getPsrContainer(): PsrContainer
    {
        return new PsrContainer($this->container);
    }

    public static function getInstance(): ?self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @return mixed
     */
    public static function get(string $class)
    {
        return self::getInstance()->getContainer()[$class];
    }

    /**
     * @param string|object $instanceOrClass
     */
    public static function set(string $class, $instanceOrClass): void
    {
        $container = self::getInstance()->getContainer();
        $container[$class] = fn () => is_string($instanceOrClass)
            ? $container[$instanceOrClass]
            : $instanceOrClass;
    }

    public static function setInstance(self $instance): void
    {
        self::$instance = $instance;
    }
}
