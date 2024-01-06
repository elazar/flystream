<?php

namespace Elazar\Flystream;

class MemoryBuffer extends AbstractBuffer
{
    /**
     * {@inheritdoc}
     */
    protected function createStream(): mixed
    {
        return fopen('php://memory', 'r+');
    }
}
