<?php

use Elazar\Flystream\OverflowBuffer;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;

beforeEach(function () {
    $this->buffer = new OverflowBuffer;
    $this->filesystem = new Filesystem(new InMemoryFilesystemAdapter());
});

it('initializes a stream on initial write', function () {
    $expected = 'foo';
    $this->buffer->write($expected);
    $this->buffer->flush($this->filesystem, '/foo', []);
    $this->buffer->close();
    $actual = $this->filesystem->read('/foo');
    expect($actual)->toBe($expected);
});

it('handles multiple writes', function () {
    $this->buffer->write('foo');
    $this->buffer->write('bar');
    $this->buffer->flush($this->filesystem, '/foo', []);
    $this->buffer->close();
    $actual = $this->filesystem->read('/foo');
    expect($actual)->toBe('foobar');
});

it('uses a custom memory cap', function () {
    $this->buffer->setMaxMemory(3);
    $this->buffer->write('foobar');
    $this->buffer->flush($this->filesystem, '/foo', []);
    $this->buffer->close();
    $actual = $this->filesystem->read('/foo');
    expect($actual)->toBe('foobar');
});
