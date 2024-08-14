<?php

namespace Elazar\Flystream;

use Psr\Container\NotFoundExceptionInterface;

class FlystreamException extends \RuntimeException
{
    public const CODE_PROTOCOL_REGISTERED = 1;
    public const CODE_PROTOCOL_NOT_REGISTERED = 2;
    public const CODE_CONTAINER_ENTRY_NOT_FOUND = 3;

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

    public static function containerEntryNotFound(string $id): self
    {
        return new class (sprintf('Specified container entry not found: %s', $id), self::CODE_CONTAINER_ENTRY_NOT_FOUND) extends FlystreamException implements NotFoundExceptionInterface { };
    }
}
