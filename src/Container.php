<?php

namespace Elazar\Flystream;

use IteratorAggregate;
use League\Flysystem\PathNormalizer;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\UnixVisibility\VisibilityConverter;
use League\Flysystem\WhitespacePathNormalizer;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Traversable;

class Container implements ContainerInterface, IteratorAggregate
{
    /**
     * @var callable[]
     */
    private array $entries;

    /**
     * @var object[]
     */
    private array $instances;

    public function __construct()
    {
        $this->entries = [
            FilesystemRegistry::class => fn () => new FilesystemRegistry(),
            PassThruPathNormalizer::class => fn () => new PassThruPathNormalizer(),
            StripProtocolPathNormalizer::class => fn () => new StripProtocolPathNormalizer(
                null,
                $this->get(WhitespacePathNormalizer::class),
            ),
            PathNormalizer::class => fn () => $this->get(StripProtocolPathNormalizer::class),
            WhitespacePathNormalizer::class => fn () => new WhitespacePathNormalizer(),
            PortableVisibilityConverter::class => fn () => new PortableVisibilityConverter(),
            VisibilityConverter::class => fn () => $this->get(PortableVisibilityConverter::class),
            LockRegistryInterface::class => fn () => $this->get(LocalLockRegistry::class),
            LocalLockRegistry::class => fn () => new LocalLockRegistry(),
            NullLogger::class => fn () => new NullLogger(),
            LoggerInterface::class => fn () => $this->get(NullLogger::class),
            BufferInterface::class => fn () => $this->get(MemoryBuffer::class),
            MemoryBuffer::class => fn () => new MemoryBuffer(),
            OverflowBuffer::class => fn () => new OverflowBuffer(),
            FileBuffer::class => fn () => new FileBuffer(),
        ];

        $this->instances = [];
    }

    public function get(string $id)
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->entries[$id])) {
            return $this->instances[$id] = $this->entries[$id]();
        }

        throw FlystreamException::containerEntryNotFound($id);
    }

    public function has(string $id): bool
    {
        return isset($this->entries[$id]);
    }

    public function set(string $class, string|object $instanceOrClass): void
    {
        $this->instances[$class] = is_string($instanceOrClass)
            ? $this->get($instanceOrClass)
            : $instanceOrClass;
    }

    public function getIterator(): Traversable
    {
        foreach (array_keys($this->entries) as $id) {
            yield $id => $this->get($id);
        }
    }
}
