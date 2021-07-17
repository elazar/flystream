<?php

namespace Elazar\Flystream;

class Lock
{
    public const TYPE_SHARED = 1;
    public const TYPE_EXCLUSIVE = 2;

    private string $path;
    private int $type;

    public function __construct(
        string $path,
        int $type
    ) {
        $this->path = $path;
        $this->type = $type;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function isShared(): bool
    {
        return $this->getType() === self::TYPE_SHARED;
    }

    public function isExclusive(): bool
    {
        return $this->getType() === self::TYPE_EXCLUSIVE;
    }
}
