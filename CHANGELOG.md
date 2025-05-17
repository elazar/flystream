# CHANGELOG

## [1.4.0](https://github.com/elazar/flystream/releases/tag/1.4.0)

2025-05-17

* Fixed a bug in `stream_open()`, `stream_read()`, and `stream_stat()` in `StreamWrapper` where missing files weren't handled correctly ([#17](https://github.com/elazar/flystream/pull/17)) - thanks @BrianHenryIE

## [1.3.0](https://github.com/elazar/flystream/releases/tag/1.3.0)

2025-02-14

* Fixed a bug in `StreamWrapper->url_stat()` where directory checks weren't handled correctly ([#16](https://github.com/elazar/flystream/pull/16)) - thanks @BrianHenryIE
* Fixed a bug in `StreamWrapper->rmdir()` where stale stat cache entries weren't being cleared ([#16](https://github.com/elazar/flystream/pull/16)) - thanks @BrianHenryIE

## [1.2.0](https://github.com/elazar/flystream/releases/tag/1.2.0)

2024-08-15

* Fixed a bug in `StreamWrapper->stream_open()` related to opening read streams ([#14](https://github.com/elazar/flystream/pull/14)) - thanks @Crell

## [1.1.0](https://github.com/elazar/flystream/releases/tag/1.1.0)

2024-08-14

* Fixed a bug related to path-less stream URL handling ([#9](https://github.com/elazar/flystream/pull/9)) - thanks @Crell
* Fixed a bug related to directory handling in `StreamWrapper->url_stat()` ([#9](https://github.com/elazar/flystream/pull/9)) - thanks @Crell
* Added a `LICENSE.txt` file ([#10](https://github.com/elazar/flystream/pull/10)) - thanks @Crell
* Configured GitHub Actions to run CS-Fixer and tests on all PRs and supported PHP versions ([#11](https://github.com/elazar/flystream/pull/11)) - thanks @Crell

## [1.0.0](https://github.com/elazar/flystream/releases/tag/1.0.0)

2024-01-06

* **BC Break**: Bumped minimum PHP version from 7.4 to 8.1
* **BC Break**: Bumped `psr/log` version from `^1` to `^2.0 || ^3.0`
* **BC Break**: Removed usage of Pimple and container accessor methods from `ServiceLocator` and replaced it with a custom PSR-11 container ([#6](https://github.com/elazar/flystream/issues/6)) - thanks @mattsah
* Fixed a bug in `StreamWrapper` preventing empty files from being copied ([#7](https://github.com/elazar/flystream/issues/7), [#8](https://github.com/elazar/flystream/pull/8)) - thanks @mattsah

## [0.6.0](https://github.com/elazar/flystream/releases/tag/0.6.0)

2025-05-17

* Fixed a bug in `stream_open()`, `stream_read()`, and `stream_stat()` in `StreamWrapper` where missing files weren't handled correctly ([#17](https://github.com/elazar/flystream/pull/17)) - thanks @BrianHenryIE


## [0.5.0](https://github.com/elazar/flystream/releases/tag/0.5.0)

2025-02-14

* Fixed a bug in `StreamWrapper->url_stat()` where directory checks weren't handled correctly ([#16](https://github.com/elazar/flystream/pull/16)) - thanks @BrianHenryIE
* Fixed a bug in `StreamWrapper->rmdir()` where stale stat cache entries weren't being cleared ([#16](https://github.com/elazar/flystream/pull/16)) - thanks @BrianHenryIE

## [0.4.0](https://github.com/elazar/flystream/releases/tag/0.4.0)

2022-02-21

* Fixed a bug in `MemoryBuffer` caused by opening the same file multiple times ([#2](https://github.com/elazar/flystream/issues/2), [#4](https://github.com/elazar/flystream/pull/4)) - thanks @onet4
* Added `LoggingCompositeBuffer` to log buffer method calls
* Added notes about installing Flysystem adapters to README ([#1](https://github.com/elazar/flystream/issues/1), [#3](https://github.com/elazar/flystream/pull/3)) - thanks @onet4

## [0.3.0](https://github.com/elazar/flystream/releases/tag/0.3.0)

2022-01-14

* Add support for Flysystem 3

## [0.2.0](https://github.com/elazar/flystream/releases/tag/0.2.0)

2021-07-20

* Added support for buffer strategies

## [0.1.0](https://github.com/elazar/flystream/releases/tag/0.1.0)

2021-07-17

* Initial release
