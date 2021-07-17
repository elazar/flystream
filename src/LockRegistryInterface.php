<?php

namespace Elazar\Flystream;

interface LockRegistryInterface
{
    public function acquire(Lock $lock): bool;

    public function release(Lock $lock): bool;
}
