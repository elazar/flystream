<?php

namespace Elazar\Flystream;

use SplObjectStorage;

class LocalLockRegistry implements LockRegistryInterface
{
    private array $locks = [];

    public function acquire(Lock $lock): bool
    {
        $path = $lock->getPath();

        if (!isset($this->locks[$path])) {
            $this->locks[$path] = $locks = new SplObjectStorage();
            $locks->attach($lock);
            return true;
        }

        $locks = $this->locks[$path];
        if ($lock->isExclusive()) {
            return false;
        }

        foreach ($locks as $otherLock) {
            if ($otherLock->isExclusive()) {
                return false;
            }
        }

        $locks->attach($lock);
        return true;
    }

    public function release(Lock $lock): bool
    {
        $path = $lock->getPath();
        $this->locks[$path]->detach($lock);
        if (count($this->locks[$path]) === 0) {
            unset($this->locks[$path]);
        }
        return true;
    }

    // @codeCoverageIgnoreStart
    public function __destruct()
    {
        foreach ($this->locks as $locks) {
            foreach ($locks as $lock) {
                $this->release($lock);
            }
        }
    }
    // @codeCoverageIgnoreEnd
}
