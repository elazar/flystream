<?php

namespace Elazar\Flystream;

use League\Flysystem\FilesystemOperator;

class OverflowBuffer implements BufferInterface
{
    /**
     * @var resource
     */
    private $stream = null;

    /**
     * Maximum amount of data in bytes to buffer in memory before using a
     * temporary file, defaults to 2 MB
     */
    private ?int $maxMemory = null;

    /**
     * {@inheritdoc}
     */
    public function write(string $data)
    {
        if ($this->stream === null) {
            $path = 'php://temp';
            if ($this->maxMemory !== null) {
                $path .= '/maxmemory:' . ((string) $this->maxMemory);
            }
            $this->stream = fopen($path, 'r+');
        }
        return fwrite($this->stream, $data);
    }

    public function flush(
        FilesystemOperator $filesystem,
        string $path,
        array $context
    ): void {
        fseek($this->stream , 0);

        $filesystem->writeStream(
            $path,
            $this->stream,
            $context
        );
    }

    public function setMaxMemory(int $maxMemory): void
    {
        $this->maxMemory = $maxMemory;
    }

    public function close(): void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }
}
