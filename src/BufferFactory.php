<?php

namespace Elazar\Flystream;

class BufferFactory implements BufferFactoryInterface
{
    private function __construct(
        private BufferInterface|string $bufferInstanceOrClassFqcn
    ) { }

    public static function fromInstance(BufferInterface $instance)
    {
        return new static($instance);
    }

    public static function fromClass(string $classFqcn)
    {
        if (!class_exists($classFqcn)) {
            throw FlystreamException::bufferClassNotFound($classFqcn);
        }
        if (!is_a($classFqcn, BufferInterface::class, true)) {
            throw FlystreamException::bufferClassMissingInterface($classFqcn);
        }
        return new static($classFqcn);
    }

    public function createBuffer(): BufferInterface
    {
        return $this->bufferInstanceOrClassFqcn instanceof BufferInterface
            ? clone $this->bufferInstanceOrClassFqcn
            : new $this->bufferInstanceOrClassFqcn;
    }
}
