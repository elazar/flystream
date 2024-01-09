<?php

namespace Elazar\Flystream;

use League\Flysystem\FilesystemOperator;

class OverflowBuffer extends AbstractBuffer
{
    /**
     * @param int|null $maxMemory Maximum amount of data in bytes to buffer
     *        in memory before using a temporary file, defaults to 2 MB
     */
    public function __construct(
        private ?int $maxMemory = null
    ) {
        $this->maxMemory = $maxMemory;
    }

    /**
     * {@inheritdoc}
     */
    protected function createStream(): mixed
    {
        $path = 'php://temp';
        if ($this->maxMemory !== null) {
            $path .= '/maxmemory:' . ((string) $this->maxMemory);
        }
        return fopen($path, 'r+');
    }

    /**
     * @param int $maxMemory Maximum amount of data in bytes to buffer in
     *        memory before using a temporary file, defaults to 2 MB
     */
    public function setMaxMemory(int $maxMemory): void
    {
        $this->maxMemory = $maxMemory;
    }

    public function getMaxMemory(): ?int
    {
        return $this->maxMemory;
    }
}
