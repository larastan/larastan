# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## [0.3.18] - 2019-08-04
### Added
- Support to Laravel 6 ([2be403c](https://github.com/nunomaduro/larastan/commit/2be403c784d0f4b84449b1b4f91b5c6ace3585a1))

## [0.3.17] - 2019-05-29
### Fixes
- Issue with PHPStan 0.11.8 because of MethodReflectionFactory signature change ([#270](https://github.com/nunomaduro/larastan/pull/270))

## [0.3.16] - 2019-03-30
### Added
- Support to Lumen (Put commit here)

### Fixes
- Void return type on Models (Put commit here)

## [0.3.15] - 2019-01-23
### Added
- Support to Laravel 5.8 ([0949fa5](https://github.com/nunomaduro/larastan/commit/0949fa59dea711c462c2d7a3a26b4a4a6cbafbf1))

## [0.3.14] - 2019-01-22
### Changed
- Bumps PHPStan version to > 0.11.1 ([#229](https://github.com/nunomaduro/larastan/pull/229))

## [0.3.13] - 2018-12-21
### Added
- Support to `trans` helper ([#220](https://github.com/nunomaduro/larastan/pull/220))

## [0.3.12] - 2018-12-03
### Added
- Support to paginators ([c29af44](https://github.com/nunomaduro/larastan/commit/c29af44b318d57c8625db7dab1aa6d138e2bf48b))

## [0.3.11] - 2018-11-26
### Fixed
- Null return type in `Auth::user()` ([#211](https://github.com/nunomaduro/larastan/pull/211))

## [0.3.10] - 2018-11-22
### Added
- Support to return type of `$this` from Eloquent builder

## [0.3.9] - 2018-11-21
### Added
- Support to return type of `Auth::user()`

## [0.3.8] - 2018-11-01
### Added
- Auto detects configuration `phpstan.neon` ([#194](https://github.com/nunomaduro/larastan/pull/194))

## [0.3.7] - 2018-10-31
### Fixed
- Fixes while resolving application with invalid php files ([7dd69ad](https://github.com/nunomaduro/larastan/commit/7dd69ad4feb7fe58676c2e4fd8dfe8f91d9af9d9))

## [0.3.6] - 2018-10-30
### Added
- Support to return type of `url` helper ([#179](https://github.com/nunomaduro/larastan/pull/179))

## [0.3.5] - 2018-10-29
### Fixed
- Internal error caused by non-existing provider name ([#170](https://github.com/nunomaduro/larastan/pull/170))
- Issue when project name have spaces ([#186](https://github.com/nunomaduro/larastan/pull/186))

## [0.3.4] - 2018-10-09
### Fixed
- Issue while calling soft deletes macros staticly

## [0.3.3] - 2018-10-09
### Added
- Support for Soft Deletes

## [0.3.2] - 2018-10-05
### Added
- Support to return type of `redirect` & `view` helpers ([#157](https://github.com/nunomaduro/larastan/pull/157))

## [0.3.1] - 2018-10-04
### Fixed
- Usage with Laravel 5.6 ([2d13d9a](https://github.com/nunomaduro/larastan/commit/2d13d9a3ae0f2a50739ecc25e3b5860199486d7e))

## [0.3.0] - 2018-10-04
### Added
- Support for static analysis in Laravel Packages

## [0.2.12] - 2018-09-27
### Added
- Support to return type of `request` helper ([#145](https://github.com/nunomaduro/larastan/pull/145))

## [0.2.11] - 2018-09-21
### Added
- Support to return type of `response` helper
- Support to return type of `\Illuminate\Http\Response::input` method

### Fixed
- False positives when performing long Eloquent queries
- Issue when there is recursive mixins
- When container got used twice using the `ArrayAccess` interface

## [0.2.10] - 2018-09-12
### Fixed
- Issue while using invalid mixins ([#137](https://github.com/nunomaduro/larastan/pull/137))

## [0.2.9] - 2018-09-01
### Fixed
- Usage of spaces with the option `--paths`

## [0.2.8] - 2018-09-01
### Fixed
- Issue while resolving implementations from container

## [0.2.7] - 2018-08-27
### Fixed
- Issue on Windows with the default path param ([#128](https://github.com/nunomaduro/larastan/pull/128))
- Issue with exit code ([#115](https://github.com/nunomaduro/larastan/pull/115))
- While running Larastan on CI envs ([#113](https://github.com/nunomaduro/larastan/pull/113))

## [0.2.6] - 2018-08-27
### Added
- Support to Laravel 5.7

## [0.2.5] - 2018-08-17
### Fixed
- Issue with option `errorFormat` ([#121](https://github.com/nunomaduro/larastan/pull/121))

## [0.2.4] - 2018-07-24
### Fixed
- Issue while resolving implementations that don't exist on the container

## [0.2.3] - 2018-07-23
### Fixed
- Common laravel false positives on trusted proxies and app exception handler

## [0.2.2] - 2018-07-23
### Fixed
- Resolved null type from container

## [0.2.1] - 2018-07-23
### Fixed
- Error - "internal error: * product does not exist"

## [0.2.0] - 2018-07-22
### Fixed
- Issues while using Lumen

## [0.1.9] - 2018-07-22
### Added
- Support to Lumen and Laravel Zero

## [0.1.8] - 2018-07-22
### Added
- Support to builder dynamic wheres

## [0.1.7] - 2018-07-22
### Added
- Support to "object" return type

### Fixed
- Bug on macro extension

## [0.1.6] - 2018-07-22
### Added
- Allows array access on objects that respects container's contract

## [0.1.5] - 2018-07-22
### Fixed
- Removes unused `dd`

## [0.1.4] - 2018-07-22
### Fixed
- Issue when `static` is missing was return type hint on Illuminate Model mixins

## [0.1.3] - 2018-07-20
### Fixed
- Usage on Windows + Laravel Homestead ([#55](https://github.com/nunomaduro/larastan/pull/55))

## [0.1.2] - 2018-07-20
### Added
- `Illuminate\Contracts` property extension

## [0.1.1] - 2018-07-18
### Fixed
- Infinite recursion in mixins middleware ([b5a4317](https://github.com/nunomaduro/larastan/commit/b5a4317ef7c19b9008e4efff7ef50d2649b00151))

## 0.1.0 - 2018-07-17
### Added
- Adds first alpha version

[Unreleased]: https://github.com/nunomaduro/larastan/compare/v0.3.18...HEAD
[0.3.18]: https://github.com/nunomaduro/larastan/compare/v0.3.17...v0.3.18
[0.3.17]: https://github.com/nunomaduro/larastan/compare/v0.3.16...v0.3.17
[0.3.16]: https://github.com/nunomaduro/larastan/compare/v0.3.15...v0.3.16
[0.3.15]: https://github.com/nunomaduro/larastan/compare/v0.3.14...v0.3.15
[0.3.14]: https://github.com/nunomaduro/larastan/compare/v0.3.13...v0.3.14
[0.3.13]: https://github.com/nunomaduro/larastan/compare/v0.3.12...v0.3.13
[0.3.12]: https://github.com/nunomaduro/larastan/compare/v0.3.11...v0.3.12
[0.3.11]: https://github.com/nunomaduro/larastan/compare/v0.3.10...v0.3.11
[0.3.10]: https://github.com/nunomaduro/larastan/compare/v0.3.9...v0.3.10
[0.3.9]: https://github.com/nunomaduro/larastan/compare/v0.3.8...v0.3.9
[0.3.8]: https://github.com/nunomaduro/larastan/compare/v0.3.7...v0.3.8
[0.3.7]: https://github.com/nunomaduro/larastan/compare/v0.3.6...v0.3.7
[0.3.6]: https://github.com/nunomaduro/larastan/compare/v0.3.5...v0.3.6
[0.3.5]: https://github.com/nunomaduro/larastan/compare/v0.3.4...v0.3.5
[0.3.4]: https://github.com/nunomaduro/larastan/compare/v0.3.3...v0.3.4
[0.3.3]: https://github.com/nunomaduro/larastan/compare/v0.3.2...v0.3.3
[0.3.2]: https://github.com/nunomaduro/larastan/compare/v0.3.1...v0.3.2
[0.3.1]: https://github.com/nunomaduro/larastan/compare/v0.3.0...v0.3.1
[0.3.0]: https://github.com/nunomaduro/larastan/compare/v0.2.12...v0.3.0
[0.2.12]: https://github.com/nunomaduro/larastan/compare/v0.2.11...v0.2.12
[0.2.11]: https://github.com/nunomaduro/larastan/compare/v0.2.10...v0.2.11
[0.2.10]: https://github.com/nunomaduro/larastan/compare/v0.2.9...v0.2.10
[0.2.9]: https://github.com/nunomaduro/larastan/compare/v0.2.8...v0.2.9
[0.2.8]: https://github.com/nunomaduro/larastan/compare/v0.2.7...v0.2.8
[0.2.7]: https://github.com/nunomaduro/larastan/compare/v0.2.6...v0.2.7
[0.2.6]: https://github.com/nunomaduro/larastan/compare/v0.2.5...v0.2.6
[0.2.5]: https://github.com/nunomaduro/larastan/compare/v0.2.4...v0.2.5
[0.2.4]: https://github.com/nunomaduro/larastan/compare/v0.2.3...v0.2.4
[0.2.3]: https://github.com/nunomaduro/larastan/compare/v0.2.2...v0.2.3
[0.2.2]: https://github.com/nunomaduro/larastan/compare/v0.2.1...v0.2.2
[0.2.1]: https://github.com/nunomaduro/larastan/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/nunomaduro/larastan/compare/v0.1.9...v0.2.0
[0.1.9]: https://github.com/nunomaduro/larastan/compare/v0.1.8...v0.1.9
[0.1.8]: https://github.com/nunomaduro/larastan/compare/v0.1.7...v0.1.8
[0.1.7]: https://github.com/nunomaduro/larastan/compare/v0.1.6...v0.1.7
[0.1.6]: https://github.com/nunomaduro/larastan/compare/v0.1.5...v0.1.6
[0.1.5]: https://github.com/nunomaduro/larastan/compare/v0.1.4...v0.1.5
[0.1.4]: https://github.com/nunomaduro/larastan/compare/v0.1.3...v0.1.4
[0.1.3]: https://github.com/nunomaduro/larastan/compare/v0.1.2...v0.1.3
[0.1.2]: https://github.com/nunomaduro/larastan/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/nunomaduro/larastan/compare/v0.1.0...v0.1.1
