<?php

use Elazar\Flystream\FilesystemRegistry;
use Elazar\Flystream\ServiceLocator;
use League\Flysystem\PathNormalizer;
use League\Flysystem\WhitespacePathNormalizer;
use Pimple\Container;
use Psr\Container\ContainerInterface;

it('initializes an instance on initial access', function () {
    $locator = ServiceLocator::getInstance();
    expect($locator)->toBeInstanceOf(ServiceLocator::class);
});

it('allows an instance to be set', function () {
    $expected = new ServiceLocator();
    ServiceLocator::setInstance($expected);
    expect(ServiceLocator::getInstance())->toBe($expected);
});

it('exposes a Pimple container', function () {
    $container = (new ServiceLocator())->getContainer();
    expect($container)->toBeInstanceOf(Container::class);
    $keys = $container->keys();
    expect($keys)->toBeArray()->not->toBeEmpty();
    foreach ($keys as $key) {
        $dependency = $container[$key];
        expect($dependency)->toBeInstanceOf($key);
    }
});

it('exposes an equivalent PSR-11 container', function () {
    $locator = new ServiceLocator();
    $container = $locator->getPsrContainer();
    expect($container)->toBeInstanceOf(ContainerInterface::class);
    foreach ($locator->getContainer()->keys() as $key) {
        expect($container->has($key))->toBeTrue();
    }
});

it('functions as a Pimple provider', function () {
    $locator = new ServiceLocator();
    $actual = new Container();
    $actual->register($locator);
    $expected = $locator->getContainer();
    expect($actual)->toEqualCanonicalizing($expected);
});

it('provides a static accessor for dependencies', function () {
    $registry = ServiceLocator::get(FilesystemRegistry::class);
    expect($registry)->toBeInstanceOf(FilesystemRegistry::class);
});

it('supports static injection of dependencies', function () {
    $locator = new ServiceLocator();
    ServiceLocator::setInstance($locator);

    $expected = new FilesystemRegistry();
    ServiceLocator::set(FilesystemRegistry::class, $expected);
    $actual = ServiceLocator::get(FilesystemRegistry::class);
    expect($actual)->toBe($expected);

    ServiceLocator::set(PathNormalizer::class, WhitespacePathNormalizer::class);
    $actual = ServiceLocator::get(PathNormalizer::class);
    expect($actual)->toBeInstanceOf(WhitespacePathNormalizer::class);
});
