<?php

use Elazar\Flystream\Lock;
use Elazar\Flystream\PermissiveLockRegistry;

beforeEach(function () {
    $this->registry = new PermissiveLockRegistry();
    $this->lock = new Lock('foo', Lock::TYPE_EXCLUSIVE);
});

it('allows all lock acquisitions', function () {
    $result = $this->registry->acquire($this->lock);
    expect($result)->toBeTrue();
    $result = $this->registry->acquire(clone $this->lock);
    expect($result)->toBeTrue();
});

it('allows release of acquired locks', function () {
    $this->registry->acquire($this->lock);
    $result = $this->registry->release($this->lock);
    expect($result)->toBeTrue();
});

it('allows release of unacquired locks', function () {
    $result = $this->registry->release($this->lock);
    expect($result)->toBeTrue();
});
