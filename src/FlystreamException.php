<?php

namespace Elazar\Flystream;

class FlystreamException extends \RuntimeException
{
    public const CODE_PROTOCOL_REGISTERED = 1;
    public const CODE_PROTOCOL_NOT_REGISTERED = 2;

    public static function protocolRegistered(string $protocol): self
    {
        return new self(
            sprintf(
                'Specified protocol is already registered: %s',
                $protocol
            ),
            self::CODE_PROTOCOL_REGISTERED
        );
    }

    public static function protocolNotRegistered(string $protocol): self
    {
        return new self(
            sprintf(
                'Specified protocol is not registered: %s',
                $protocol
            ),
            self::CODE_PROTOCOL_NOT_REGISTERED
        );
    }
}
