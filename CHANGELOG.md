# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

### Added
- Document common errors for users to ignore ([#564](https://github.com/nunomaduro/larastan/pull/564))

### Changed
- Do not overwrite PHPStan's default for `reportUnmatchedIgnoredErrors` ([#564](https://github.com/nunomaduro/larastan/pull/564))

### Removed
- Stop ignoring errors ([#564](https://github.com/nunomaduro/larastan/pull/564))

## [0.5.8] - 2020-05-06

### Added
- Support for custom Eloquent collections. Thanks @timacdonald ([#537](https://github.com/nunomaduro/larastan/pull/537))
- Added issue and PR templates for new contributors. Thanks @spawnia ([#560](https://github.com/nunomaduro/larastan/pull/560))
### Fixed
- Fixed some of the collection methods in stub files. Thanks @Daanra ([#556](https://github.com/nunomaduro/larastan/pull/556))

## [0.5.7] - 2020-04-28

### Fixed
- Fixed incorrect stubs for model creation methods. ([85716a5](https://github.com/nunomaduro/larastan/commit/85716a50610740af787899709814c1053ef4acf6))
- Fixed false positives on NoUnnecessaryCollectionCallRule rule. Thanks @Daanra ([#546](https://github.com/nunomaduro/larastan/pull/546))

### Added

- Added more methods to collection stubs. Thanks @Daanra ([#547](https://github.com/nunomaduro/larastan/pull/547))

## [0.5.6] - 2020-04-26

### Fixed
- Fixed relation methods with custom builders always returning custom builder. ([#520](https://github.com/nunomaduro/larastan/pull/520))
- Fixed reading `boolean` columns from migrations. ([#514](https://github.com/nunomaduro/larastan/pull/514), [#513](https://github.com/nunomaduro/larastan/pull/513), [692fcd1](https://github.com/nunomaduro/larastan/commit/692fcd1ddc7017a5d25a476153b3e3d0b8081624), [d1f1861](https://github.com/nunomaduro/larastan/commit/d1f1861ae0094cd8e0f24f001f2bc43e2c85c9fb))
- Annotations for model properties have higher order than migration files. ([ec22906](https://github.com/nunomaduro/larastan/commit/ec22906dba63325b21c1ac2640879dfd55a1394f))
- Improved support for Eloquent relationships. ([#533](https://github.com/nunomaduro/larastan/pull/533))

### Added
- Eloquent relations are now also generic. ([#518](https://github.com/nunomaduro/larastan/pull/518))
- Support for Composer 2. Thanks @GrahamCampbell ([#528](https://github.com/nunomaduro/larastan/pull/528))
- Support for `abort_unless`, `throw_if` and `throw_unless` functions. Thanks @Daanra ([#542](https://github.com/nunomaduro/larastan/pull/542))
- Support for `retry` helper return type. Thanks @Daanra ([#543](https://github.com/nunomaduro/larastan/pull/543))
- A rule for detecting expensive calls on a Collection. Thanks @Daanra ([#538](https://github.com/nunomaduro/larastan/pull/538))
- Support for `value` helper function return type. Thanks @Daanra ([#545](https://github.com/nunomaduro/larastan/pull/545))

## [0.5.5] - 2020-03-26

### Fixed
- Assume id property exists only when it is not found. ([#510](https://github.com/nunomaduro/larastan/pull/510))
- Fixed an issue with generics in BuilderModelFindExtension. ([#511](https://github.com/nunomaduro/larastan/pull/511))

## [0.5.4] - 2020-03-22

### Added
- Support for return type inference of `find*` methods on Builder class depending on passed arguments. ([#503](https://github.com/nunomaduro/larastan/pull/503))

## [0.5.3] - 2020-03-21

### Added
- Support for Eloquent resources. Thanks @mr-feek ([#470](https://github.com/nunomaduro/larastan/pull/470))
- Treat Laravel ide-helper generated relationship properties as generic Collections. Thanks @mr-feek ([#479](https://github.com/nunomaduro/larastan/pull/479))
- Treat Laravel ide-helper generated builder typehints as generic Builders. ([#497](https://github.com/nunomaduro/larastan/pull/497))
- `id` property on any model class will be recognized as integer type. ([#499](https://github.com/nunomaduro/larastan/pull/499))

### Fixed
- Corrected parameter type of builder dynamic wheres. Thanks @mr-feek ([#482](https://github.com/nunomaduro/larastan/pull/482))
- Added a check to see if migrations directory exists. Thanks @deleugpn ([#498](https://github.com/nunomaduro/larastan/pull/498))
- Added `Carbon/Carbon` to possible types for a date properties in models. Thanks @arxeiss ([#500](https://github.com/nunomaduro/larastan/pull/500))
- Fixed issue with scanning the migrations. ([#501](https://github.com/nunomaduro/larastan/pull/501))

## [0.5.2] - 2020-02-10

### Fixed
- Model scopes returns the builder, if return type is `void` ([#450](https://github.com/nunomaduro/larastan/pull/450))
- Fix return type of calling query builder methods on custom builders ([#453](https://github.com/nunomaduro/larastan/pull/453))
- Fix return type of `all` on model. Thanks @BertvanHoekelen ([#454](https://github.com/nunomaduro/larastan/pull/454))
- Any query builder method should return the generic query builder ([#457](https://github.com/nunomaduro/larastan/pull/457))
- Don't throw exception when unknown column type is encountered while scanning the migrations ([#451](https://github.com/nunomaduro/larastan/pull/451))

## [0.5.1] - 2020-02-04

### Added
- Support for model accessors ([#401](https://github.com/nunomaduro/larastan/pull/401))
- Handle Builder method calls, model scope calls and dynamic where calls on relations. Thanks @BertvanHoekelen ([#410](https://github.com/nunomaduro/larastan/pull/410), [#419](https://github.com/nunomaduro/larastan/pull/419), [#423](https://github.com/nunomaduro/larastan/pull/423))
- Support for custom Eloquent builders ([#432](https://github.com/nunomaduro/larastan/pull/432))
- Infer Eloquent model property types. Thanks @muglug ([#435](https://github.com/nunomaduro/larastan/pull/435))
- Support for app and resolve helper functions return type. Thanks @troelsselch ([#431](https://github.com/nunomaduro/larastan/pull/431))
- Add generic stubs for Eloquent collection and Support collection. ([#439](https://github.com/nunomaduro/larastan/pull/439))

### Fixed
- Better return type support for find* methods ([#400](https://github.com/nunomaduro/larastan/pull/400))
- Don't register abstract service providers. Thanks @CyberiaResurrection  ([#440](https://github.com/nunomaduro/larastan/pull/440))

## [0.5.0] - 2019-12-25

Blogpost: [nunomaduro.com/larastan-0-5-is-out](https://nunomaduro.com/larastan-0-5-is-out)
Upgrade guide: [UPGRADE.md](https://github.com/nunomaduro/larastan/blob/master/UPGRADE.md)

### Added 
- Support to PHPStan `0.12` [#378](https://github.com/nunomaduro/larastan/pull/378)
- Support to Laravel 7 [#377](https://github.com/nunomaduro/larastan/pull/377)
- Support for Facade fakes [#347](https://github.com/nunomaduro/larastan/pull/347)
- Support for model relations accessed as properties [#361](https://github.com/nunomaduro/larastan/pull/361)
- Support for custom Eloquent builders [#364](https://github.com/nunomaduro/larastan/pull/364)
- Support for return types of `spy`, `mock` and `partialMock` methods of `TestCase` [#362](https://github.com/nunomaduro/larastan/pull/362)

### Fixed
- Fixed a bug about handling method calls that starts with `find` on model instances [#360](https://github.com/nunomaduro/larastan/pull/360)

### Removed
- The artisan `code:analyse` command. [391](https://github.com/nunomaduro/larastan/pull/391)

## [0.4.3] - 2019-10-22
### Added
- Support for `abort_if`. Fixes [#116](https://github.com/nunomaduro/larastan/issues/116) ([#330](https://github.com/nunomaduro/larastan/pull/330))

### Fixed
- Better return type inference in models and builders ([#325](https://github.com/nunomaduro/larastan/pull/325), [#336](https://github.com/nunomaduro/larastan/pull/336))

## [0.4.2] - 2019-10-16
### Added
- Support for determining correct return type for relation create method ([#320](https://github.com/nunomaduro/larastan/pull/320), [#323](https://github.com/nunomaduro/larastan/pull/323))

### Fixed
- `getProjectClasses` method to use Composer data to get the classes ([#318](https://github.com/nunomaduro/larastan/pull/318))
- Return type of calling scope on relation ([#322](https://github.com/nunomaduro/larastan/pull/322))

## [0.4.1] - 2019-10-07
### Added
- Better return type inference in relation methods ([#319](https://github.com/nunomaduro/larastan/pull/319))
- Better return type inference for auth guard method calls on auth helper and facade ([#317](https://github.com/nunomaduro/larastan/pull/317))
- Support to phpstan extension plugin  ([#314](https://github.com/nunomaduro/larastan/pull/314))

## [0.4.0] - 2019-08-28
### Added
- Support to `Carbon` macros ([#301](https://github.com/nunomaduro/larastan/pull/301))

### Fixed
- Support to `laravel/framework:^6.0` without `laravel/helpers` package ([#311](https://github.com/nunomaduro/larastan/pull/311))

### Removed
- Dependency of `orchestra/testbench` in Laravel projects ([#305](https://github.com/nunomaduro/larastan/pull/305))

## [0.3.21] - 2019-08-17
### Fixed
- Macro method detector class implements MethodReflection instead of BuiltinMethodReflection ([#299](https://github.com/nunomaduro/larastan/pull/299))

## [0.3.20] - 2019-08-17
### Fixed
- Macro method detector class implements MethodReflection instead of BuiltinMethodReflection ([#298](https://github.com/nunomaduro/larastan/pull/298))

## [0.3.19] - 2019-08-16
### Added
- Partial support to `auth` helper ([#254](https://github.com/nunomaduro/larastan/pull/254))

### Fixed
- Compatibility with PHPStan 0.11.13 ([#294](https://github.com/nunomaduro/larastan/pull/294))

## [0.3.18] - 2019-08-04
### Added
- Support to Laravel 6 ([2be403c](https://github.com/nunomaduro/larastan/commit/2be403c784d0f4b84449b1b4f91b5c6ace3585a1))

## [0.3.17] - 2019-05-29
### Fixed
- Issue with PHPStan 0.11.8 because of MethodReflectionFactory signature change ([#270](https://github.com/nunomaduro/larastan/pull/270))

## [0.3.16] - 2019-03-30
### Added
- Support to Lumen (Put commit here)

### Fixed
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

[Unreleased]: https://github.com/nunomaduro/larastan/compare/v0.5.8...HEAD
[0.5.8]: https://github.com/nunomaduro/larastan/compare/v0.5.7...HEAD
[0.5.7]: https://github.com/nunomaduro/larastan/compare/v0.5.6...HEAD
[0.5.6]: https://github.com/nunomaduro/larastan/compare/v0.5.5...HEAD
[0.5.5]: https://github.com/nunomaduro/larastan/compare/v0.5.4...HEAD
[0.5.4]: https://github.com/nunomaduro/larastan/compare/v0.5.3...HEAD
[0.5.3]: https://github.com/nunomaduro/larastan/compare/v0.5.2...v0.5.3
[0.5.2]: https://github.com/nunomaduro/larastan/compare/v0.5.1...v0.5.2
[0.5.1]: https://github.com/nunomaduro/larastan/compare/v0.5.0...v0.5.1
[0.5.0]: https://github.com/nunomaduro/larastan/compare/v0.4.3...v0.5.0
[0.4.3]: https://github.com/nunomaduro/larastan/compare/v0.4.2...v0.4.3
[0.4.2]: https://github.com/nunomaduro/larastan/compare/v0.4.1...v0.4.2
[0.4.1]: https://github.com/nunomaduro/larastan/compare/v0.4.0...v0.4.1
[0.4.0]: https://github.com/nunomaduro/larastan/compare/v0.3.21...v0.4.0
[0.3.21]: https://github.com/nunomaduro/larastan/compare/v0.3.20...v0.3.21
[0.3.20]: https://github.com/nunomaduro/larastan/compare/v0.3.19...v0.3.20
[0.3.19]: https://github.com/nunomaduro/larastan/compare/v0.3.18...v0.3.19
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
