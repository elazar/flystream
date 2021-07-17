<?php

namespace Elazar\Flystream;

use Elazar\Flystream\StreamWrapper;
use League\Flysystem\FilesystemOperator;

/**
 * Registry for filesystem instances, implemented as such to make those
 * instances accessible to stream wrapper instances.
 */
class FilesystemRegistry
{
    /**
     * @var array<string, FilesystemOperator>
     */
    private array $filesystems = [];

    public function register(
        string $protocol,
        FilesystemOperator $filesystem
    ): void {
        if (in_array($protocol, stream_get_wrappers())) {
            throw FlystreamException::protocolRegistered($protocol);
        }
        $this->filesystems[$protocol] = $filesystem;
        stream_wrapper_register($protocol, StreamWrapper::class);
    }

    public function unregister(
        string $protocol
    ): void {
        $this->checkForProtocol($protocol);
        unset($this->filesystems[$protocol]);
        stream_wrapper_unregister($protocol);
    }

    public function get(
        string $protocol
    ): FilesystemOperator {
        $this->checkForProtocol($protocol);
        return $this->filesystems[$protocol];
    }

    private function checkForProtocol(string $protocol): void
    {
        if (!isset($this->filesystems[$protocol])) {
            throw FlystreamException::protocolNotRegistered($protocol);
        }
    }
}
