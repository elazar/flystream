<?php

use Elazar\Flystream\BufferInterface;
use Elazar\Flystream\Container;
use Elazar\Flystream\FileBuffer;
use Elazar\Flystream\FlystreamException;
use Elazar\Flystream\MemoryBuffer;

it('can iterate, detect, and get default entries', function () {
    $container = new Container();
    $expectedDependencyCount = 15;
    $actualDependencyCount = 0;
    foreach ($container as $class => $instance) {
        $actualDependencyCount++;
        expect($container->has($class))->toBe(true);
        expect($container->get($class))->toBe($instance);
    }
    expect($actualDependencyCount)->toBe($expectedDependencyCount);
});

it('throws an exception for an unknown key', function () {
    (new Container())->get('unknown-key');
})->throws(FlystreamException::class);

it('can override a dependency using a class name', function () {
    $container = new Container();

    $default = $container->get(BufferInterface::class);
    expect($default)->toBeInstanceOf(MemoryBuffer::class);

    $container->set(BufferInterface::class, FileBuffer::class);

    $override = $container->get(BufferInterface::class);
    expect($override)->toBeInstanceOf(FileBuffer::class);
});

it('can override a dependency using an instance', function () {
    $container = new Container();

    $buffer = new FileBuffer();
    $container->set(BufferInterface::class, $buffer);

    $override = $container->get(BufferInterface::class);
    expect($override)->toBe($buffer);
});
