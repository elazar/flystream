<?php

use Elazar\Flystream\Lock;
use Elazar\Flystream\LocalLockRegistry;

beforeEach(function () {
    $this->registry = new LocalLockRegistry();
});

it('can acquire a shared lock without contention', function () {
    $lock = new Lock('foo', Lock::TYPE_SHARED);
    $result = $this->registry->acquire($lock);
    expect($result)->toBeTrue();
});

it('can acquire multiple shared locks', function () {
    $quantity = 2;
    $locks = [];
    foreach (range(1, $quantity) as $index) {
        $locks[] = $lock = new Lock('foo', Lock::TYPE_SHARED);
        $result = $this->registry->acquire($lock);
        expect($result)->toBeTrue();
    }
});

it('cannot acquire a shared lock with contention', function () {
    $exclusive = new Lock('foo', Lock::TYPE_EXCLUSIVE);
    $result = $this->registry->acquire($exclusive);
    expect($result)->toBeTrue();
    foreach ([Lock::TYPE_SHARED, Lock::TYPE_EXCLUSIVE] as $type) {
        $lock = new Lock('foo', $type);
        $result = $this->registry->acquire($lock);
        expect($result)->toBeFalse();
    }
});

it('can acquire an exclusive lock without contention', function () {
    $lock = new Lock('foo', Lock::TYPE_EXCLUSIVE);
    $result = $this->registry->acquire($lock);
    expect($result)->toBeTrue();
});

it('cannot acquire an exclusive lock with contention', function () {
    foreach ([Lock::TYPE_SHARED, Lock::TYPE_EXCLUSIVE] as $type) {
        $lock = new Lock('foo', $type);
        $result = $this->registry->acquire($lock);
        expect($result)->toBeTrue();
        $exclusive = new Lock('foo', Lock::TYPE_EXCLUSIVE);
        $result = $this->registry->acquire($exclusive);
        expect($result)->toBeFalse();
        $this->registry->release($lock);
    }
});

it('releases locks when going out of scope', function () {
    $lock = new Lock('foo', Lock::TYPE_EXCLUSIVE);
    $registry = Mockery::mock(LocalLockRegistry::class . '[release]');
    $registry->shouldReceive('release')->with($lock);
    $result = $registry->acquire($lock);
    expect($result)->toBeTrue();
});
