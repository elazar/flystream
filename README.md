# Flystream

[![PHP Version Support](https://img.shields.io/static/v1?label=php&message=%3E=%207.4.0&color=blue)](https://packagist.org/packages/elazar/flystream)
[![Packagist Version](https://img.shields.io/static/v1?label=packagist&message=0.1.0&color=blue)](https://packagist.org/packages/elazar/flystream)
[![Software License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.md)
[![Buy Me a Cofee](https://img.shields.io/badge/buy%20me%20a%20coffee-donate-blue.svg)](https://www.buymeacoffee.com/DIkm1qe)
[![Patreon](https://img.shields.io/badge/patreon-donate-blue.svg)](https://patreon.com/matthewturland)

[Flysystem v2](https://flysystem.thephpleague.com/v2/docs/) + [PHP stream wrappers](https://www.php.net/manual/en/class.streamwrapper.php) = 🔥


Flystream enables you to use core PHP filesystem functions to interact with Flysystem filesystems by registering them as custom protocols.

Released under the [MIT License](https://en.wikipedia.org/wiki/MIT_License).

**WARNING: This project is in an alpha state of development and may be subject to changes that break backward compatibility. It may contain bugs, lack features or extension points, or be otherwise unsuitable for production use. User discretion is advised.**

## Supported Use Cases

* Using Flysystem with another library that interacts with the filesystem using PHP filesystem functions instead of Flysystem.
* Intercepting filesystem operations for verification in tests.
* Improving the speed of tests where the code under test would otherwise require access to the local filesystem.

## Unsupported Use Cases

* Flystream doesn't and won't support Flysystem v1. If you want a similar library for v1, see [twistor/flysystem-stream-wrapper](https://github.com/twistor/flysystem-stream-wrapper).

## Known Issues

* If a file or directory handle is not explicitly closed after use (i.e. using [`fclose()`](https://www.php.net/fclose) or [`closedir()`](https://www.php.net/closedir) as appropriate), PHP will implicitly attempt to close it during [shutdown](https://www.php.net/manual/en/function.register-shutdown-function.php). This situation may trigger a segmentation fault in some environments. This issue is [under investigation](https://github.com/elazar/xdebug-date-stream-php-segfault). In the interim, the easiest work-around is to ensure that file and directory handles are explicitly closed.

## Requirements

* PHP 7.4+
* Flysystem v2

## Installation

Use [Composer](https://getcomposer.org/).

```sh
composer require elazar/flystream
```

## Usage

The examples below aren't comprehensive, but should provide a basic understanding of the capabilities of Flystream.

```php
<?php

/**
 * 1. Configure your Flysystem filesystem as normal.
 */

use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;

$adapter = new InMemoryFilesystemAdapter();
$filesystem = new Filesystem($adapter);

/**
 * 2. Register the filesystem with Flystream and associate it with a
 *    custom protocol (e.g. 'mem').
 */

use Elazar\Flystream\FilesystemRegistry;
use Elazar\Flystream\ServiceLocator;

$registry = ServiceLocator::get(FilesystemRegistry::class);
$registry->register('mem', $filesystem);

/**
 * 3. Interact with the filesystem instance using the custom protocol.
 */

mkdir('mem://foo');

$file = fopen('mem://foo/bar', 'w');
fwrite($file, 'baz');
fclose($file);

file_put_contents('mem://foo/bar', 'bay');

$contents = file_get_contents('mem://foo/bar');
// or
$contents = stream_get_contents('mem://foo/bar');

if (file_exists('mem://foo/bar')) {
    rename('mem://foo/bar', 'mem://foo/baz');
    touch('mem://foo/bar');
}

$file = fopen('mem://foo/baz', 'r');
fseek($file, 2);
$position = ftell($file);
ftruncate($file, 0);
fclose($file);

$dir = opendir('mem://foo');
while (($entry = readdir($dir)) !== false) {
    echo $entry, PHP_EOL;
}
closedir($dir);

unlink('mem://foo/bar');
unlink('mem://foo/baz');

rmdir('mem://foo');

// These won't have any effect because Flysystem doesn't support them.
chmod('mem://foo', 0755);
chown('mem://foo', 'root');
chgrp('mem://foo', 'root');

/**
 * 4. Optionally, unregister the filesystem with Flystream.
 */
$registry->unregister('mem');
```

## Configuration

For its most basic use, Flystream requires two parameters:

1. a string containing a name for a custom protocol used by PHP filesystem functions; and
2. an object that implements the Flysystem `FilesystemOperator` interface (e.g. an instance of the `Filesystem` class).

### Path Normalization

The Flysystem `Filesystem` class supports normalization of supplied paths before they're passed to the underlying adapter. The Flysystem `PathNormalizer` interface represents this normalization process.

The implementation of this interface that Flysystem uses by default is `WhitespacePathNormalizer`, which handles normalizing the directory separator (i.e. converting `\` to `/`), removes abnormal whitespace characters, and resolves relative paths.

If you're using a third-party adapter, you'll probably need path normalization to include removing the custom protocol used to register the Flysystem filesystem with Flystream. As such, by default, Flystream registers a custom path normalizer that it defines, `StripProtocolPathNormalizer`.

If you would rather leave custom protocols intact in paths, you can override the path normalizer that Flystream uses a different one instead, such as Flysystem's default.

```php
<?php

use Elazar\Flystream\ServiceLocator;
use League\Flysystem\PathNormalizer;
use League\Flysystem\WhitespacePathNormalizer;

ServiceLocator::set(PathNormalizer::class, WhitespacePathNormalizer::class);
```

If you would prefer to limit protocols removed by `StripProtocolPathNormalizer` to a specified list, you can do so by specifying a custom instance that sets a value for its first parameter.

```php
<?php
use Elazar\Flystream\ServiceLocator;
use Elazar\Flystream\StripProtocolPathNormalizer;

// To remove a single protocol, specify it as a string
$pathNormalizer = new StripProtocolPathNormalizer('foo');

// To remove more than one protocol, specify them as an array of strings
$pathNormalizer = new StripProtocolPathNormalizer(['foo', 'bar']);

ServiceLocator::set(PathNormalizer::class, $pathNormalizer);
```

`StripProtocolPathNormalizer` also supports applying a second path normalizer after it performs its own normalization. By default, it uses Flysystem's `WhitespacePathNormalizer` as this secondary normalizer. If you'd rather that `StripProtocolPathNormalizer` not use a secondary normalizer, you can override this behavior like so.

```php
<?php

use Elazar\Flystream\PassThruPathNormalizer;
use Elazar\Flystream\ServiceLocator;
use Elazar\Flystream\StripProtocolPathNormalizer;
use League\Flysystem\PathNormalizer;

ServiceLocator::set(PathNormalizer::class, new StripProtocolPathNormalizer(

    // This is the default and results in the removal of all protocols
    null, 

    // This normalizer returns the given path unchanged
    new PassThruPathNormalizer()

));
```

If you'd rather not apply any path normalization, you can use `PassThruPathNormalizer` directly to do this.

```php
<?php

use Elazar\Flystream\PassThruPathNormalizer;
use Elazar\Flystream\ServiceLocator;
use League\Flysystem\PathNormalizer;

ServiceLocator::set(PathNormalizer::class, PassThruPathNormalizer::class);
```

### Visibility

Flysystem implements an abstraction layer for [visibility](https://flysystem.thephpleague.com/v2/docs/usage/unix-visibility/) and an implementation for handling [Unix-style visibility](https://flysystem.thephpleague.com/v2/docs/usage/unix-visibility/).

By default, Flystream uses this Unix-style visibility implementation with its default configuration. If you want to override its settings, you can override it with a configured instance.

```php
<?php

use Elazar\Flystream\ServiceLocator;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\UnixVisibility\VisibilityConverter;

ServiceLocator::set(VisibilityConverter::class, new PortableVisibilityConverter(
    // ...
));
```

You can also configure Flystream to use a custom visibility implementation.

```php
<?php

use Elazar\Flystream\ServiceLocator;
use League\Flysystem\UnixVisibility\VisibilityConverter;
use My\CustomVisibilityConverter;

// If your implementation doesn't require constructor parameters:
ServiceLocator::set(VisibilityConverter::class, CustomVisibilityConverter::class);

// If your implementation requires constructor parameters:
ServiceLocator::set(VisibilityConverter::class, new CustomVisibilityConverter(
    // ...
));
```

### Locking

By default, the Flysystem [Local adapter](https://flysystem.thephpleague.com/v1/docs/adapter/local/) uses [file locks](https://flysystem.thephpleague.com/v1/docs/adapter/local/#locks) during writes and updates, but allows overriding this behavior.

Flystream follows suit. It defines an interface, `LockRegistryInterface`, and two implementations of this interface, `LocalLockRegistry` and `PermissiveLockRegistry`. By default, Flystream uses the former, which is a naïve implementation that prevents the current PHP process from reading a file already open for writing or writing to a file already open for reading.

If you'd rather disable locking entirely, you can configure Flystream to use the latter implementation, which grants all requested lock acquisitions and releases.

```php
<?php

use Elazar\Flystream\LockRegistryInterface;
use Elazar\Flystream\PermissiveLockRegistry;
use Elazar\Flystream\ServiceLocator;

ServiceLocator::set(
    LockRegistryInterface::class,
    PermissiveLockRegistry::class
);
```

Another option is to create your own lock registry implementation, such as a distributed one that handles locking between PHP processes using a library such as [`php-lock/lock`](https://github.com/php-lock/lock).

```php
<?php

namespace My;

use Elazar\Flystream\Lock;
use Elazar\Flystream\LockRegistryInterface;

class CustomLockRegistry implements LockRegistryInterface
{
    public function acquire(Lock $lock): bool
    {
        // ...
    }

    public function release(Lock $lock): bool
    {
        // ...
    }
}
```

Then, configure Flystream to use it.

```php
<?php

use Elazar\Flystream\LockRegistryInterface;
use Elazar\Flystream\ServiceLocator;
use My\CustomLockRegistry;

// If your implementation doesn't require constructor parameters:
ServiceLocator::set(
    LockRegistryInterface::class,
    CustomLockRegistry::class
);

// If your implementation requires constructor parameters:
ServiceLocator::set(
    LockRegistryInterface::class,
    new CustomLockRegistry(
        // ...
    )
);
```

### Logging

Flystream supports any [PSR-3](https://www.php-fig.org/psr/psr-3/) logger and logs all calls to its stream wrapper methods.

By default, it uses the `NullLogger` implementation included with [`psr/log`](https://packagist.org/packages/psr/log), which discards the log entries. You can override this to use a different logger, such as [Monolog](https://packagist.org/packages/monolog/monolog).

```php
<?php

use Elazar\Flystream\ServiceLocator;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

$logger = new Logger;
// configure $logger here
ServiceLocator::set(LoggerInterface::class, $logger);
```

## Design

### Service Locator

Flystream uses a [singleton](https://en.wikipedia.org/wiki/Singleton_pattern) [service locator](https://en.wikipedia.org/wiki/Service_locator_pattern) rather than a more commonly accepted [dependency injection](https://en.wikipedia.org/wiki/Dependency_injection) configuration due to how PHP uses its stream wrapper classes. Specifically, PHP implicitly creates an instance of the stream wrapper class each time you use the associated custom protocol, and doesn't allow for dependency injection.

This requires use of a service locator for the stream wrapper to have access to dependencies, a singleton in particular so that the stream wrapper uses the same container that the end user configures to override default dependency implementations. The stream wrapper class limits its use of the service locator to a single method that fetches a dependency from the container of the singeton instance. It also supports injecting a custom singleton instance, in particular for testing. These measures limit the impact of the disadvantages of using the service locator pattern.

### Buffering

Flysystem doesn't support appending data to existing files, at least in part because some adapters don't support this (e.g. AWS S3). Because of this, Flystream must buffer the data supplied in all write operations into memory until a flush operation (e.g. instigated by closing the file receiving the writes) occurs. As such, it may not be an optimal solution when writing a large amount of data to a single file.
