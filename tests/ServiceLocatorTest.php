<?php

use Elazar\Flystream\FilesystemRegistry;
use Elazar\Flystream\ServiceLocator;
use League\Flysystem\PathNormalizer;
use League\Flysystem\WhitespacePathNormalizer;

it('initializes an instance on initial access', function () {
    $locator = ServiceLocator::getInstance();
    expect($locator)->toBeInstanceOf(ServiceLocator::class);
});

it('allows an instance to be set', function () {
    $expected = new ServiceLocator();
    ServiceLocator::setInstance($expected);
    expect(ServiceLocator::getInstance())->toBe($expected);
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
