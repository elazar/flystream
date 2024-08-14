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
    $testLock = new Lock('foo', Lock::TYPE_EXCLUSIVE);
    $released = false;
    $registry = new class ($testLock, $released) extends LocalLockRegistry {
        private Lock $testLock;
        private bool $released;
        public function __construct(Lock $testLock, bool &$released)
        {
            $this->testLock = $testLock;
            $this->released = &$released;
        }
        public function release(Lock $lock): bool
        {
            $this->released = true;
            expect($lock)->toBe($this->testLock);
            return parent::release($lock);
        }
    };
    $result = $registry->acquire($testLock);
    expect($result)->toBeTrue();
    unset($registry);
    expect($released)->toBeTrue();
});
