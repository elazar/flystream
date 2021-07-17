<?php

namespace Elazar\Flystream;

class PermissiveLockRegistry implements LockRegistryInterface
{
    public function acquire(Lock $lock): bool
    {
        return true;
    }

    public function release(Lock $lock): bool
    {
        return true;
    }
}
