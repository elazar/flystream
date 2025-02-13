<?php

namespace Elazar\Flystream\Tests;

use League\MimeTypeDetection\{
    FinfoMimeTypeDetector,
    MimeTypeDetector,
};
use League\Flysystem\{
    Config,
    FileAttributes,
    FilesystemAdapter,
    InMemory\InMemoryFilesystemAdapter,
    Visibility,
};

/**
 * InMemoryFilesystemAdapter has suboptimal handling of directories; this
 * adapter wraps it to provide better support for them.
 */
class TestInMemoryFilesystemAdapter implements FilesystemAdapter
{
    /** @var InMemoryFilesystemAdapter */
    private InMemoryFilesystemAdapter $adapter;

    /** @var array<string, FileAttributes> */
    private array $directories;

    private string $defaultVisibility;

    public function __construct(
        string $defaultVisibility = Visibility::PUBLIC,
        ?MimeTypeDetector $mimeTypeDetector = null
    ) {
        $this->adapter = new InMemoryFilesystemAdapter(
            $defaultVisibility,
            $mimeTypeDetector ?? new FinfoMimeTypeDetector(),
        );
        $this->defaultVisibility = $defaultVisibility;
        $this->directories = [];
    }

    public function deleteDirectory(string $path): void
    {
        unset($this->directories[$this->preparePath($path)]);

        $this->adapter->deleteDirectory($path);
    }

    public function createDirectory(string $path, Config $config): void
    {
        $directoryPath = $this->preparePath($path);
        $visibility = $config->get(Config::OPTION_VISIBILITY, $this->defaultVisibility);
        $lastModified = $config->get('timestamp') ?? 0;
        $this->directories[$directoryPath] = new FileAttributes(
            $directoryPath,
            0,
            $visibility,
            $lastModified,
        );

        $this->adapter->createDirectory($path, $config);
    }

    public function directoryExists(string $path): bool
    {
        return isset($this->directories[$this->preparePath($path)]);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $directoryPath = $this->preparePath($path);
        if ($this->directoryExists($directoryPath)) {
            $this->directories[$directoryPath] = new FileAttributes(
                $directoryPath,
                0,
                $visibility,
                time()
            );
        }

        $this->adapter->setVisibility($path, $visibility);
    }

    public function visibility(string $path): FileAttributes
    {
        $directoryPath = $this->preparePath($path);
        return $this->directories[$directoryPath]
            ?? $this->adapter->visibility($path);
    }

    public function lastModified(string $path): FileAttributes
    {
        $directoryPath = $this->preparePath($path);
        return $this->directories[$directoryPath]
            ?? $this->adapter->lastModified($path);
    }

    public function fileExists(string $path): bool
    {
        return $this->adapter->fileExists($path);
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->adapter->write($path, $contents, $config);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->adapter->writeStream($path, $contents, $config);
    }

    public function read(string $path): string
    {
        return $this->adapter->read($path);
    }

    public function readStream(string $path)
    {
        return $this->adapter->readStream($path);
    }

    public function delete(string $path): void
    {
        $this->adapter->delete($path);
    }

    public function mimeType(string $path): FileAttributes
    {
        return $this->adapter->mimeType($path);
    }

    public function fileSize(string $path): FileAttributes
    {
        return $this->adapter->fileSize($path);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        return $this->adapter->listContents($path, $deep);
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->adapter->move($source, $destination, $config);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $this->adapter->copy($source, $destination, $config);
    }

    private function preparePath(string $path): string
    {
        return '/' . trim($path, '/') . '/';
    }
}
