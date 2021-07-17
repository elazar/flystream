<?php

use Elazar\Flystream\FilesystemRegistry;
use Elazar\Flystream\FlystreamException;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;

beforeEach(function () {
    $this->registry = new FilesystemRegistry();
    $this->filesystem = new Filesystem(
        new InMemoryFilesystemAdapter()
    );
});

it('fails to register an existing protocol', function () {
    $this->registry->register('php', $this->filesystem);
})
->throws(
    FlystreamException::class,
    'Specified protocol is already registered: php'
);

it('fails to unregister a nonexistent protocol', function () {
    $this->registry->unregister('foo');
})
->throws(
    FlystreamException::class,
    'Specified protocol is not registered: foo'
);

it('fails to recognize a nonexistent protocol', function () {
    $this->registry->get('foo');
})
->throws(
    FlystreamException::class,
    'Specified protocol is not registered: foo'
);

it('recognizes an existing protocol', function () {
    $this->registry->register('foo', $this->filesystem);
    $filesystem = $this->registry->get('foo');
    expect($filesystem)->toBeInstanceOf(FilesystemOperator::class);
    $this->registry->unregister('foo');
});

it('unregisters an existing protocol', function () {
    $this->registry->register('foo', $this->filesystem);
    $this->registry->unregister('foo');
    $this->registry->get('foo');
})
->throws(
    FlystreamException::class,
    'Specified protocol is not registered: foo'
);
