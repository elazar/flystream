<?php

namespace Elazar\Flystream;

use League\Flysystem\FilesystemOperator;

/**
 * Flysystem doesn't support append operations, at least in part because at
 * least some of its drivers don't (e.g. AWS S3).
 *
 * The default size of the PHP stream write buffer differs between PHP 7.4 and
 * 8.0; see https://3v4l.org/RiENn. As such, one or more calls may be made to
 * stream_write() if the size of the data to be written exceeds the buffer
 * size.
 *
 * Because of these circumstances, written data must be buffered and then
 * written out to the destination when stream_flush() is called. The interface
 * below represents an approach to this buffering.
 */
interface BufferInterface
{
    /**
     * @return int|false
     */
    public function write(string $data);

    public function flush(
        FilesystemOperator $filesystem,
        string $path,
        array $context
    ): void;

    public function close(): void;
}
