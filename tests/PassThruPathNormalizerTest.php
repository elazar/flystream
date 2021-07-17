<?php

use Elazar\Flystream\PassThruPathNormalizer;

beforeEach(function () {
    $this->normalizer = new PassThruPathNormalizer();
});

it('returns the given path', function () {
    $path = '/foo/bar';
    $normalized = $this->normalizer->normalizePath($path);
    expect($normalized)->toBe($path);
});
