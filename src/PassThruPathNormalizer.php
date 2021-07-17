<?php

namespace Elazar\Flystream;

use League\Flysystem\PathNormalizer;

class PassThruPathNormalizer implements PathNormalizer
{
    public function normalizePath(string $path): string
    {
        return $path;
    }
}
