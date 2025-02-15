# CHANGELOG

## [0.5.0](https://github.com/elazar/flystream/releases/tag/0.5.0)

2025-02-14

* Fixed a bug in `StreamWrapper->url_stat()` where directory checks weren't handled correctly ([#16](https://github.com/elazar/flystream/pull/16)) - thanks @BrianHenryIE
* Fixed a bug in `StreamWrapper->rmdir()` where stale stat cache entries weren't being cleared ([#16](https://github.com/elazar/flystream/pull/16)) - thanks @BrianHenryIE

## [0.4.0](https://github.com/elazar/flystream/releases/tag/0.4.0)

2021-02-21

* Fixed a bug in `MemoryBuffer` caused by opening the same file multiple times (#2, #4) - thanks @onet4
* Added `LoggingCompositeBuffer` to log buffer method calls
* Added notes about installing Flysystem adapters to README (#1, #3) - thanks @onet4

## [0.3.0](https://github.com/elazar/flystream/releases/tag/0.3.0)

2022-01-14

* Add support for Flysystem 3

## [0.2.0](https://github.com/elazar/flystream/releases/tag/0.2.0)

2021-07-20

* Added support for buffer strategies

## [0.1.0](https://github.com/elazar/flystream/releases/tag/0.1.0)

2021-07-17

* Initial release
