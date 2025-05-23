<?php

use Elazar\Flystream\FilesystemRegistry;
use Elazar\Flystream\ServiceLocator;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\PathNormalizer;
use Elazar\Flystream\Tests\TestInMemoryFilesystemAdapter;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

function createFile(string $uri): void
{
    $result = touch($uri);
    expect($result)->toBeTrue();
    expect(file_exists($uri))->toBeTrue();
}

beforeEach(function () {
    $serviceLocator = new ServiceLocator();
    ServiceLocator::setInstance($serviceLocator);

    $this->logger = new Logger(__FILE__);
    $this->logger->pushHandler(new TestHandler());
    ServiceLocator::set(LoggerInterface::class, $this->logger);

    $this->registry = ServiceLocator::get(FilesystemRegistry::class);

    $this->filesystem = new Filesystem(
        new TestInMemoryFilesystemAdapter(),
        [],
        ServiceLocator::get(PathNormalizer::class),
    );
    $this->registry->register('fly', $this->filesystem);
});

afterEach(function () {
    $this->registry->unregister('fly');
});

it('can detect, create, and delete directories', function () {
    $this->assertFalse(is_dir('fly://foo'));
    $mkResult = mkdir('fly://foo');
    expect($mkResult)->toBeTrue();
    $this->assertTrue(is_dir('fly://foo'));
    $rmResult = rmdir('fly://foo');
    expect($rmResult)->toBeTrue();
    $this->assertFalse(is_dir('fly://foo'));
});

it('can detect a directory', function () {
    mkdir('fly://foo');
    expect(is_dir('fly://foo'))->toBeTrue();
    rmdir('fly://foo');
});

it('can copy an empty file', function () {
    createFile('fly://src');

    $success = copy('fly://src', 'fly://dst');
    expect($success)->toBe(true);

    $success = file_exists('fly://dst');
    expect($success)->toBe(true);

    $contents = file_get_contents('fly://dst');
    expect($contents)->toBe('');
});

it('handles opening a nonexistent directory', function () {
    $dir = opendir('fly://foo');
    expect($dir)->toBeResource();
    $result = readdir($dir);
    expect($result)->toBeFalse();
    closedir($dir);
});

it('can iterate over an empty directory', function () {
    $result = mkdir('fly://foo');
    $dir = opendir('fly://foo');
    $result = readdir($dir);
    expect($result)->toBeFalse();
    closedir($dir);
});

it('can iterate over a non-empty directory', function () {
    $file = fopen('fly://foo/bar', 'w');
    fwrite($file, 'bar');
    fclose($file);
    $dir = opendir('fly://foo');
    $result = readdir($dir);
    expect($result)->toBe('foo/bar');
    closedir($dir);
});

it('can rewind a directory iterator', function () {
    $file = fopen('fly://foo/bar', 'w');
    fwrite($file, 'bar');
    fclose($file);
    $dir = opendir('fly://foo');
    $result = readdir($dir);
    expect($result)->toBe('foo/bar');
    rewinddir($dir);
    $result = readdir($dir);
    expect($result)->toBe('foo/bar');
    closedir($dir);
});

it('can rename an existing file', function () {
    createFile('fly://foo');
    $result = rename('fly://foo', 'fly://bar');
    expect($result)->toBeTrue();
    clearstatcache();
    expect(file_exists('fly://foo'))->toBeFalse();
    expect(file_exists('fly://bar'))->toBeTrue();
});

it('fails to rename a nonexistent file', function () {
    $result = rename('fly://foo', 'fly://bar');
    expect($result)->toBeFalse();
});

it('can handle writes that force a buffer flush', function () {
    $file = fopen('fly://foo', 'w');
    $data = str_repeat('a', 9 * 1024);
    fwrite($file, $data);
    fclose($file);
    $contents = file_get_contents('fly://foo');
    expect($contents)->toBe($data);
});

it('can acquire multiple shared locks', function () {
    createFile('fly://foo');

    $stream1 = fopen('fly://foo', 'r');
    $result = flock($stream1, LOCK_SH);
    expect($result)->toBeTrue();

    $stream2 = fopen('fly://foo', 'r');
    $result = flock($stream2, LOCK_SH);
    expect($result)->toBeTrue();

    flock($stream1, LOCK_UN);
    flock($stream2, LOCK_UN);
    fclose($stream1);
    fclose($stream2);
});

it('cannot acquire multiple exclusive locks', function () {
    createFile('fly://foo');

    $stream1 = fopen('fly://foo', 'w');
    $result = flock($stream1, LOCK_EX);
    expect($result)->toBeTrue();

    $stream2 = fopen('fly://foo', 'w');
    $result = flock($stream2, LOCK_EX);
    expect($result)->toBeFalse();

    flock($stream1, LOCK_UN);
    fclose($stream1);
    fclose($stream2);
});

it('cannot acquire an exclusive lock with existing locks', function () {
    createFile('fly://foo');

    $stream1 = fopen('fly://foo', 'r');
    $result = flock($stream1, LOCK_SH);
    expect($result)->toBeTrue();

    $stream2 = fopen('fly://foo', 'w');
    $result = flock($stream2, LOCK_EX);
    expect($result)->toBeFalse();

    flock($stream1, LOCK_UN);
    fclose($stream1);
    fclose($stream2);
});

it('does not support operations to change owner, group, or access', function () {
    createFile('fly://foo');

    $result = chmod('fly://foo', 0755);
    expect($result)->toBeFalse();

    $result = chown('fly://foo', 1);
    expect($result)->toBeFalse();

    $result = chown('fly://foo', 'root');
    expect($result)->toBeFalse();

    $result = chgrp('fly://foo', 1);
    expect($result)->toBeFalse();

    $result = chgrp('fly://foo', 'root');
    expect($result)->toBeFalse();
});

it('supports seeking', function () {
    $result = file_put_contents('fly://foo', 'foobar');
    expect($result)->not->toBeFalse();
    expect(file_exists('fly://foo'))->toBeTrue();

    $stream = fopen('fly://foo', 'r');
    $result = fseek($stream, 3, SEEK_SET);
    expect($result)->toBe(0);
    $result = ftell($stream);
    expect($result)->toBe(3);
    $contents = stream_get_contents($stream);
    expect($contents)->toBe('bar');
    fclose($stream);
});

it('supports setting the stream mode to non-blocking', function () {
    file_put_contents('fly://foo', 'foobar');
    $stream = fopen('fly://foo', 'r');
    $result = stream_set_blocking($stream, 0);
    expect($result)->toBeTrue();
    fclose($stream);
});

it('does not support setting the stream read timeout', function () {
    file_put_contents('fly://foo', 'foobar');
    $stream = fopen('fly://foo', 'r');
    $result = stream_set_timeout($stream, 1, 0);
    expect($result)->toBeFalse();
    fclose($stream);
});

it('does not support setting the stream write buffer', function () {
    file_put_contents('fly://foo', 'foobar');
    $stream = fopen('fly://foo', 'r');
    $result = stream_set_write_buffer($stream, 1);
    expect($result)->not->toBe(0);
    fclose($stream);
});

it('supports truncation', function () {
    $result = file_put_contents('fly://foo', 'foobar');
    expect($result)->not->toBeFalse();
    expect(file_exists('fly://foo'))->toBeTrue();

    $stream = fopen('fly://foo', 'r');
    $result = ftruncate($stream, 3);
    expect($result)->toBeTrue();
    $contents = stream_get_contents($stream);
    expect($contents)->toBe('foo');
    fclose($stream);
});

it('fails attempting to write to a read-only stream', function () {
    $result = file_put_contents('fly://foo', 'foobar');
    expect(file_exists('fly://foo'))->toBeTrue();

    $stream = fopen('fly://foo', 'r');
    $result = fwrite($stream, 'foo');
    expect($result)->toBeFalse();
    fclose($stream);
});

it('deletes a file', function () {
    file_put_contents('fly://foo', 'bar');
    $result = unlink('fly://foo');
    expect($result)->toBeTrue();
    expect(file_exists('fly://foo'))->toBeFalse();
});

it('supports stream selection', function () {
    $result = file_put_contents('fly://foo', 'foobar');
    expect(file_exists('fly://foo'))->toBeTrue();

    $stream = fopen('fly://foo', 'r');
    $read = [$stream];
    $write = $except = [];
    $result = stream_select(
        $read,
        $write,
        $except,
        1
    );
    expect($result)->toBe(1);
    fclose($stream);
});

it('can read and write to a Flysystem filesystem', function () {
    $path = 'foo';
    $expected = 'bar';
    $this->filesystem->write($path, $expected);

    $actual = file_get_contents("fly://$path");

    expect($actual)->toBe($expected);
});

it('fails attempting to read a missing file', function () {
    expect(fn () => file_get_contents("fly://doesnotexist.txt"))
        ->toTriggerWarning('file_get_contents(fly://doesnotexist.txt): Failed to open stream: "Elazar\\Flystream\\StreamWrapper::stream_open" call failed')
        ->toBeFalse();
});

it('can stat a file', function () {
    $result = file_put_contents('fly://foo', 'foobar');
    expect(file_exists('fly://foo'))->toBeTrue();

    $stream = fopen('fly://foo', 'r');
    $metadata = \stream_get_meta_data($stream);
    expect($metadata['uri'])->toEqual('fly://foo');
    fclose($stream);
    ;
});
