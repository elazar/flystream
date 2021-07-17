<?php

use Elazar\Flystream\Lock;

it('populates properties', function () {
    $path = 'foo';
    $type = Lock::TYPE_SHARED;
    $lock = new Lock($path, $type);
    expect($lock->getPath())->toBe($path);
    expect($lock->getType())->toBe($type);
});

it('indicates presence or absence of shared type', function () {
    $lock = new Lock('foo', Lock::TYPE_SHARED);
    expect($lock->isShared())->toBeTrue();
    expect($lock->isExclusive())->toBeFalse();
});

it('indicates presence or absence of exclusive type', function () {
    $lock = new Lock('foo', Lock::TYPE_EXCLUSIVE);
    expect($lock->isExclusive())->toBeTrue();
    expect($lock->isShared())->toBeFalse();
});
