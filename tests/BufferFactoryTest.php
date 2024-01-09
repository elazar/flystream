<?php

use Elazar\Flystream\BufferFactory;
use Elazar\Flystream\FlystreamException;
use Elazar\Flystream\OverflowBuffer;

it('creates a buffer from a buffer instance', function () {
    $maxMemory = 1024 ** 2;
    $instance = new OverflowBuffer($maxMemory);
    $factory = BufferFactory::fromInstance($instance);
    $buffer = $factory->createBuffer();
    expect($buffer)
        ->toBeInstanceOf(OverflowBuffer::class)
        ->not->toBe($instance);
    expect($buffer->getMaxMemory())->toBe($maxMemory);
});

it('creates a buffer from a class', function () {
    $factory = BufferFactory::fromClass(OverflowBuffer::class);
    $buffer = $factory->createBuffer();
    expect($buffer)->toBeInstanceOf(OverflowBuffer::class);
});

it('does not create a buffer from a nonexistent class', function () {
    BufferFactory::fromClass('NonexistentClass');
})->throws(
    FlystreamException::class,
    null,
    FlystreamException::CODE_BUFFER_CLASS_NOT_FOUND
);

it('does not create a buffer from a non-buffer class', function () {
    BufferFactory::fromClass('stdClass');
})->throws(
    FlystreamException::class,
    null,
    FlystreamException::CODE_BUFFER_CLASS_MISSING_INTERFACE
);
