<?php

namespace Elazar\Flystream;

use League\Flysystem\FilesystemOperator;

class MemoryBuffer implements BufferInterface
{
    /**
     * @var resource
     */
    private $stream = null;

    /**
     * {@inheritdoc}
     */
    public function write(string $data)
    {
        if ($this->stream === null) {
            $this->stream = fopen('php://memory', 'r+');
        }
        return fwrite($this->stream, $data);
    }

    public function flush(
        FilesystemOperator $filesystem,
        string $path,
        array $context
    ): void {
        $filesystem->writeStream(
            $path,
            $this->stream,
            $context
        );
    }

    public function close(): void
    {
        if ($this->stream) {
            fclose($this->stream);
        }
    }
}
