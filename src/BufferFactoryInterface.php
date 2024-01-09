<?php

namespace Elazar\Flystream;

interface BufferFactoryInterface
{
    public function createBuffer(): BufferInterface;
}
