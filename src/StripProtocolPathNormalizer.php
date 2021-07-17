<?php

namespace Elazar\Flystream;

use League\Flysystem\PathNormalizer;
use League\Flysystem\WhitespacePathNormalizer;

class StripProtocolPathNormalizer implements PathNormalizer
{
    /**
     * @var string[]|null
     */
    private ?array $protocols;

    private ?PathNormalizer $delegateNormalizer;

    /**
     * @param string|string[]|null $protocols
     */
    public function __construct(
        $protocols = null,
        ?PathNormalizer $delegateNormalizer = null
    ) {
        $this->protocols = is_string($protocols)
            ? [ $protocols ]
            : $protocols;
        $this->delegateNormalizer = $delegateNormalizer
            ?: new WhitespacePathNormalizer();
    }

    public function normalizePath(string $path): string
    {
        $pattern = $this->protocols === null
            ? '[^:]*'
            : '(?:' . implode('|', $this->protocols) . ')';
        $path = preg_replace("#^$pattern:/+#", '/', $path);

        if ($this->delegateNormalizer !== null) {
            $path = $this->delegateNormalizer->normalizePath($path);
        }

        return $path;
    }
}
