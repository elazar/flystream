<?php

use Elazar\Flystream\PassThruPathNormalizer;
use Elazar\Flystream\StripProtocolPathNormalizer;
use League\Flysystem\PathNormalizer;

it('strips all protocols', function (string $path) {
    $normalizer = new StripProtocolPathNormalizer();
    $normalized = $normalizer->normalizePath($path);
    expect($normalized)->toBe('bar/baz');
})
->with([
    'foo:///bar/baz',
    'bar:///bar/baz'
]);

it('strips a single protocol', function () {
    $normalizer = new StripProtocolPathNormalizer('foo');
    $normalized = $normalizer->normalizePath('foo://bar');
    expect($normalized)->toBe('bar');
    $normalized = $normalizer->normalizePath('bar://baz');
    expect($normalized)->toBe('bar:/baz');
});

it('strips multiple protocols', function () {
    $normalizer = new StripProtocolPathNormalizer(['foo', 'bar']);
    $normalized = $normalizer->normalizePath('foo://baz');
    expect($normalized)->toBe('baz');
    $normalized = $normalizer->normalizePath('bar://baz');
    expect($normalized)->toBe('baz');
    $normalized = $normalizer->normalizePath('baz://baz');
    expect($normalized)->toBe('baz:/baz');
});

it('accepts a path normalizer', function () {
    $delegate = new PassThruPathNormalizer();
    $normalizer = new StripProtocolPathNormalizer(null, $delegate);
    $normalized = $normalizer->normalizePath('foo://bar');
    expect($normalized)->toBe('/bar');
});
