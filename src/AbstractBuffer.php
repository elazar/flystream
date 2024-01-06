<?php

namespace Elazar\Flystream;

use League\Flysystem\FilesystemOperator;

abstract class AbstractBuffer implements BufferInterface
{
    /**
     * @var resource
     */
    private $stream = null;

    /**
     * @return resource|false
     */
    abstract protected function createStream(): mixed;

    /**
     * {@inheritdoc}
     */
    public function write(string $data)
    {
        if ($this->stream === null) {
            $this->stream = $this->createStream();
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
        if (is_resource($this->stream)) {
            fclose($this->stream);
            $this->stream = null;
        }
    }

    /**
     * When this buffer is cloned to allow for copying over property values
     * (e.g. configuration), assign it a new stream so that streams aren't
     * shared between buffer instances.
     */
    public function __clone()
    {
        $this->stream = $this->createStream();
    }
}
