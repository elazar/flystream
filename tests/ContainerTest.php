<?php

use Elazar\Flystream\Container;
use Elazar\Flystream\FlystreamException;
use Elazar\Flystream\PassThruPathNormalizer;
use Elazar\Flystream\StripProtocolPathNormalizer;
use League\Flysystem\PathNormalizer;

it('can iterate, detect, and get default entries', function () {
    $container = new Container;
    $expectedDependencyCount = 12;
    $actualDependencyCount = 0;
    foreach ($container as $class => $instance) {
        $actualDependencyCount++;
        expect($container->has($class))->toBe(true);
        expect($container->get($class))->toBe($instance);
    }
    expect($actualDependencyCount)->toBe($expectedDependencyCount);
});

it('throws an exception for an unknown key', function () {
    (new Container)->get('unknown-key');
})->throws(FlystreamException::class);

it('can override a dependency using a class name', function () {
    $container = new Container;

    $default = $container->get(PathNormalizer::class);
    expect($default)->toBeInstanceOf(StripProtocolPathNormalizer::class);

    $container->set(PathNormalizer::class, PassThruPathNormalizer::class);

    $override = $container->get(PathNormalizer::class);
    expect($override)->toBeInstanceOf(PassThruPathNormalizer::class);
});

it('can override a dependency using an instance', function () {
    $container = new Container;

    $override = new PassThruPathNormalizer;
    $container->set(PathNormalizer::class, $override);

    $actual = $container->get(PathNormalizer::class);
    expect($actual)->toBe($override);
});
