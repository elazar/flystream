<?php

namespace Elazar\Flystream;

use ArrayIterator;
use Iterator;
use IteratorAggregate;
use League\Flysystem\Config;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UnixVisibility\VisibilityConverter;
use Pimple\Container;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class StreamWrapper
{
    private ?Lock $lock = null;

    private ?string $path = null;

    private ?string $mode = null;

    private ?Iterator $dir = null;

    /**
     * @var resource|null
     */
    private $read = null;

    /**
     * @var resource|null
     */
    private $write = null;

    /** @var resource */
    public $context;

    public function dir_closedir(): bool
    {
        $this->log('info', __METHOD__);
        $this->dir = null;
        $this->path = null;
        return true;
    }

    public function dir_opendir(string $path, int $options): bool
    {
        $this->log('info', __METHOD__, func_get_args());
        try {
            $this->dir = $this->getDir($path);
            $this->path = $path;
            return true;

            // @codeCoverageIgnoreStart
        // InMemoryFilesystemAdapter->listContents() returns an empty
        // array when a directory doesn't exist.
        } catch (Throwable $e) {
            $this->log('error', __METHOD__, func_get_args() + [
                'exception' => $e,
            ]);
            return false;
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return string|false
     */
    public function dir_readdir()
    {
        $this->log('info', __METHOD__);
        if ($this->dir->valid()) {
            $current = $this->dir->current();
            $this->dir->next();
            return $current->path();
        }
        return false;
    }

    public function dir_rewinddir(): bool
    {
        $this->log('info', __METHOD__);
        $this->dir = $this->getDir($this->path);
        return true;
    }

    public function mkdir(string $path, int $mode, int $options): bool
    {
        $this->log('info', __METHOD__, func_get_args());
        $visibility = $this->get(VisibilityConverter::class);
        $filesystem = $this->getFilesystem($path);
        try {
            $config = $this->getConfig($path, [
                Config::OPTION_DIRECTORY_VISIBILITY =>
                    $visibility->inverseForDirectory($mode),
            ]);
            $filesystem->createDirectory($path, $config);
            return true;
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            $this->log('error', __METHOD__, func_get_args() + [
                'exception' => $e,
            ]);
            return false;
        }
        // @codeCoverageIgnoreEnd
    }

    public function rename(string $path_from, string $path_to): bool
    {
        $this->log('info', __METHOD__, func_get_args());
        $filesystem = $this->getFilesystem($path_from);
        try {
            $config = $this->getConfig($path_to);
            $filesystem->move($path_from, $path_to, $config);
            return true;
        } catch (Throwable $e) {
            $this->log('error', __METHOD__, func_get_args() + [
                'exception' => $e,
            ]);
            return false;
        }
    }

    public function rmdir(string $path, int $options): bool
    {
        $this->log('info', __METHOD__, func_get_args());
        $filesystem = $this->getFilesystem($path);
        try {
            $filesystem->deleteDirectory($path);
            return true;

            // @codeCoverageIgnoreStart
        // InMemoryFilesystemAdapter->deleteDirectory() does not raise
        // an error if the target doesn't exist.
        } catch (Throwable $e) {
            $this->log('error', __METHOD__, func_get_args() + [
                'exception' => $e,
            ]);
            return false;
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return resource
     */
    public function stream_cast(int $cast_as)
    {
        $this->log('info', __METHOD__, func_get_args());
        $this->openRead();
        return $this->read;
    }

    public function stream_close(): void
    {
        $this->log('info', __METHOD__);
        if ($this->read !== null) {
            fclose($this->read);
            $this->read = null;
        }
        if ($this->write !== null) {
            fclose($this->write);
            $this->write = null;
        }
    }

    public function stream_eof(): bool
    {
        $this->log('info', __METHOD__);
        return feof($this->read);
    }

    public function stream_flush(): bool
    {
        $this->log('info', __METHOD__);
        try {
            fseek($this->write, 0);
            $filesystem = $this->getFilesystem($this->path);
            $filesystem->writeStream(
                $this->path,
                $this->write,
                $this->getConfig($this->path)
            );
        } catch (Throwable $e) {
            $this->log('error', __METHOD__, func_get_args() + [
                'exception' => $e,
            ]);
            return false;
        }
        return true;
    }

    public function stream_lock(int $operation): bool
    {
        $this->log('info', __METHOD__, func_get_args());

        $locks = $this->get(LockRegistryInterface::class);

        // For now, ignore non-blocking requests
        $operation &= ~LOCK_NB;

        $shared = $operation === LOCK_SH;
        $exclusive = $operation === LOCK_EX;
        if ($shared || $exclusive) {
            $type = $shared
                ? Lock::TYPE_SHARED
                : Lock::TYPE_EXCLUSIVE;
            $lock = new Lock($this->path, $type);
            $result = $locks->acquire($lock);
            if ($result) {
                $this->lock = $lock;
            }
            return $result;
        }

        $result = $locks->release($this->lock);
        if ($result) {
            $this->lock = null;
        }
        return $result;
    }

    /**
     * @param mixed $value
     */
    public function stream_metadata(
        string $path,
        int $option,
        $value
    ): bool {
        $this->log('info', __METHOD__, func_get_args());
        if ($option === STREAM_META_TOUCH) {
            $time = time();
            $filesystem = $this->getFilesystem($path);
            $config = $this->getConfig($path);
            try {
                $filesystem->write($path, '', $config);
                return true;

                // @codeCoverageIgnoreStart
            // InMemoryFilesystemAdapter->write() does not raise errors
            } catch (Throwable $e) {
                $this->log('error', __METHOD__, func_get_args() + [
                    'exception' => $e,
                ]);
            }
            // @codeCoverageIgnoreEnd
        }
        return false;
    }

    public function stream_open(
        string $path,
        string $mode,
        int $options,
        ?string &$opened_path = null
    ): bool {
        $this->log('info', __METHOD__, func_get_args());
        $this->path = $path;
        $this->mode = $mode;
        return true;
    }

    public function stream_read(int $count): string
    {
        $this->log('info', __METHOD__, func_get_args());
        $this->openRead();
        return stream_get_contents($this->read, $count);
    }

    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        $this->log('info', __METHOD__, func_get_args());
        $this->openRead();
        return fseek($this->read, $offset, $whence) === 0;
    }

    public function stream_set_option(int $option, int $arg1, ?int $arg2 = null): bool
    {
        $this->log('info', __METHOD__, func_get_args());
        $this->openRead();

        if ($option === STREAM_OPTION_BLOCKING) {
            return stream_set_blocking($this->read, $arg1);
        }

        if ($option === STREAM_OPTION_READ_TIMEOUT) {
            return stream_set_timeout($this->read, $arg1, $arg2);
        }

        return stream_set_write_buffer($this->read, $arg2) === 0;
    }

    /**
     * @return array|false
     */
    public function stream_stat()
    {
        $this->log('info', __METHOD__);
        $this->openRead();
        return fstat($this->read);
    }

    public function stream_tell(): int
    {
        $this->log('info', __METHOD__);
        $this->openRead();
        return (int) ftell($this->read);
    }

    public function stream_truncate(int $new_size): bool
    {
        $this->log('info', __METHOD__, func_get_args());
        $this->openRead();
        return ftruncate($this->read, $new_size);
    }

    /**
     * @return int|false
     */
    public function stream_write(string $data)
    {
        $this->log('info', __METHOD__, func_get_args());
        try {
            if ($this->mode === 'r') {
                throw UnableToWriteFile::atLocation(
                    $this->path,
                    'Stream mode is "r" which does not allow writing'
                );
            }
            // Flysystem doesn't support append operations, at least in part
            // because at least some of its drivers don't (e.g. AWS S3).
            //
            // The default size of the PHP stream write buffer differs between
            // PHP 7.4 and 8.0; see https://3v4l.org/RiENn. As such, one or
            // more calls may be made to stream_write() if the size of the data
            // to be written exceeds the buffer size.
            //
            // Because of these circumstances, written data must be buffered
            // and then written out to the destination when stream_flush() is
            // called.
            if ($this->write === null) {
                $this->write = fopen('php://temp', 'w+');
            }
            return fwrite($this->write, $data);
        } catch (Throwable $e) {
            $this->log('error', __METHOD__, func_get_args() + [
                'exception' => $e,
            ]);
            return false;
        }
    }

    public function unlink(string $path): bool
    {
        $this->log('info', __METHOD__, func_get_args());
        $filesystem = $this->getFilesystem($path);
        try {
            $filesystem->delete($path);
            return true;

            // @codeCoverageIgnoreStart
            // InMemoryFilesystemAdapter->delete() does not raise an error if the target doesn't exist.
        } catch (Throwable $e) {
            $this->log('error', __METHOD__, func_get_args() + [
                'exception' => $e,
            ]);
            return false;
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return array|false
     */
    public function url_stat(string $path, int $flags)
    {
        $this->log('info', __METHOD__, func_get_args());

        $filesystem = $this->getFilesystem($path);
        $visibility = $this->get(VisibilityConverter::class);

        if (!$filesystem->fileExists($path)) {
            return false;
        }

        $mode = 0100000 | $visibility->forFile(
            $filesystem->visibility($path)
        );
        $size = $filesystem->fileSize($path);
        $mtime = $filesystem->lastModified($path);

        return [
            'dev' => 0,
            'ino' => 0,
            'mode' => $mode,
            'nlink' => 0,
            'uid' => 0,
            'gid' => 0,
            'rdev' => 0,
            'size' => $size,
            'atime' => 0,
            'mtime' => $mtime,
            'ctime' => 0,
            'blksize' => 0,
            'blocks' => 0,
        ];
    }

    private function getConfig(string $path, array $overrides = []): array
    {
        $config = [];
        if ($this->context !== null) {
            $protocol = parse_url($path, PHP_URL_SCHEME);
            $context = stream_context_get_options($this->context);
            $config = $context[$protocol] ?? [];
        }
        return array_merge($config, $overrides);
    }

    private function getFilesystem(string $path): FilesystemOperator
    {
        $protocol = parse_url($path, PHP_URL_SCHEME);
        $registry = $this->get(FilesystemRegistry::class);
        return $registry->get($protocol);
    }

    private function get(string $key)
    {
        return ServiceLocator::getInstance()->getContainer()[$key];
    }

    private function getDir(string $path): Iterator
    {
        $filesystem = $this->getFilesystem($path);
        $dir = $filesystem->listContents($path, false);
        if ($dir instanceof IteratorAggregate) {
            return $dir->getIterator();
        }
        // @codeCoverageIgnoreStart
        // InMemoryFilesystemAdapter->listContents() only ever returns
        // a generator
        if ($dir instanceof Iterator) {
            return $dir;
        }
        return new ArrayIterator($dir);
        // @codeCoverageIgnoreEnd
    }

    private function openRead(): void
    {
        if ($this->read === null) {
            $filesystem = $this->getFilesystem($this->path);
            $this->read = $filesystem->readStream($this->path);
        }
    }

    private function log(
        string $level,
        string $message,
        array $context = []
    ): void {
        $logger = $this->get(LoggerInterface::class);
        $logger->log($level, $message, $context);
    }
}
