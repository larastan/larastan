# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## [2.2.0] - 2022-08-31

### Added
* Install fileinfo PHP extension by @szepeviktor in https://github.com/nunomaduro/larastan/pull/1318
* Configure Composer properly in CI by @szepeviktor in https://github.com/nunomaduro/larastan/pull/1329
* feat: rules for unnecessary uses of with() and values() functions by @PrinsFrank in https://github.com/nunomaduro/larastan/pull/1226
* feat: add rule for missing 'provides' method in Deferrable ServiceProviders by @PrinsFrank in https://github.com/nunomaduro/larastan/pull/1262
* feat: conditional return type for Collection::random method by @canvural in https://github.com/nunomaduro/larastan/pull/1338
* Remove all unnecessary method stubs for the Query Builder by @lupinitylabs in https://github.com/nunomaduro/larastan/pull/1343
* feat: add ViewWithMethodsClassReflectionExtension by @canvural in https://github.com/nunomaduro/larastan/pull/1348

### Fixed
* fix: use Composer autoloader to determine vendor-dir by @szepeviktor in https://github.com/nunomaduro/larastan/pull/1319
* fix: return correct types from model factories by @canvural in https://github.com/nunomaduro/larastan/pull/1339
* fix: better return types for `App::environment` by @crissi in https://github.com/nunomaduro/larastan/pull/1303
* fix: remove `fromSub` from stubs by @crissi in https://github.com/nunomaduro/larastan/pull/1340


## [2.1.8] - 2022-06-04

### Fixed
* Don't use `ReflectionClass` directly https://github.com/nunomaduro/larastan/commit/2404fa8d9e27d726bb9e42ec754b323d6727bdfd
* Don't try to invoke methods on classes https://github.com/nunomaduro/larastan/commit/2a0b2dade387bc19bdf070650c1ba1e035c709da

## [2.1.7] - 2022-06-03

### Added
* Conditional return types to some global helper functions by @canvural in https://github.com/nunomaduro/larastan/pull/1260
* Conditional return types for `Conditionable` trait by @canvural in https://github.com/nunomaduro/larastan/pull/1261
* Conditional return type for `Route::middleware` by @canvural in https://github.com/nunomaduro/larastan/commit/0543c454896f07079d86145afbc318a947ee72c1

## [2.1.6] - 2022-05-23

### Changed

* Increased minimum PHPStan version to 1.7.0

## [2.1.5] - 2022-05-23

### Added

* feat: add dynamic return type extension for `App::make` by @PrinsFrank in https://github.com/nunomaduro/larastan/pull/1222
* Improve DX: specify memory limits for tests (custom composer scripts) by @lptn in https://github.com/nunomaduro/larastan/pull/1220
* Allow Builder-s in Builder::union* $query parameter by @fagai in https://github.com/nunomaduro/larastan/pull/1231
* Added conditional return types by @ricklambrechts in https://github.com/nunomaduro/larastan/pull/1236
* Add support for Storage::drive besides Storage::disk by @PrinsFrank in https://github.com/nunomaduro/larastan/pull/1241
* Date type extension by @bastien-phi in https://github.com/nunomaduro/larastan/pull/1142

### Fixed
* fix: typo in doc file name by @mnastalski in https://github.com/nunomaduro/larastan/pull/1208
* fix: fix: change `QueryBuilder::newQuery()` @return from `$this` to `static` by @dotdash in https://github.com/nunomaduro/larastan/pull/1221
* Update joinSub docblock by @harmenjanssen in https://github.com/nunomaduro/larastan/pull/1212
* When unless void default by @BertvanHoekelen in https://github.com/nunomaduro/larastan/pull/1203
* Fix Model property types by @szepeviktor in https://github.com/nunomaduro/larastan/pull/1234

## [2.1.4] - 2022-03-30

### Improvements
* feat: add cursorPaginate() support by @BramVanBerkel in https://github.com/nunomaduro/larastan/pull/1198
* feat: add App::make() support by @PrinsFrank in https://github.com/nunomaduro/larastan/pull/1222

### Fixes
* fix: inverted check in the ModelRuleHelper by @mad-briller in https://github.com/nunomaduro/larastan/pull/1207

## [2.1.3] - 2022-03-23

### Improvements
* feat: add exact typehints for `Model` `casts`, `hidden` and `visible` properties by @Magiczne in https://github.com/nunomaduro/larastan/pull/1158
* feat: support `with` in RelationExistenceRule  by @canvural in https://github.com/nunomaduro/larastan/pull/1175
* feat: update Eloquent Builder stubs to be more specific by @canvural in https://github.com/nunomaduro/larastan/pull/1178
* Break long line in transformStaticParameters by @szepeviktor in https://github.com/nunomaduro/larastan/pull/1183
* feat: improve model factory `after*` methods by @mdpoulter in https://github.com/nunomaduro/larastan/pull/1195

### Fixes
* fix: use custom collection if it does not have template types by @canvural in https://github.com/nunomaduro/larastan/pull/1170
* fix: ignore Model in ModelPropertyRule by @canvural in https://github.com/nunomaduro/larastan/pull/1184
* fix: use TypeTraverser to handle Union and Intersection types in `ModelRelationsExtension` by @canvural in https://github.com/nunomaduro/larastan/pull/1187


* fix: updated `composer/pcre` to new PHP relevant version
* feat: add support for the `foreignId` column in migrations

## [2.1.0] - 2022-03-04

### Added

* feat: add return type support for `$request->user()` call by @canvural in https://github.com/nunomaduro/larastan/pull/1136
* feat: schema dump parsing by @canvural in https://github.com/nunomaduro/larastan/pull/1147
* feat: add support for migrations using `Schema::connection` by @canvural in https://github.com/nunomaduro/larastan/pull/1151

### Fixed
* fix: collect() returns collection which can have array-key as key type in https://github.com/nunomaduro/larastan/commit/b0a50dfe2d21c44c9f2577aa5d7c9825b6b9d5b2
* fix: `Query::whereBetween` typehint by @gitetsu in https://github.com/nunomaduro/larastan/pull/1123
* fix: update PHPStan version and stubs by @canvural in https://github.com/nunomaduro/larastan/pull/1144
* fix: `deleted_at` column has date type rather than `string` by @totov in https://github.com/nunomaduro/larastan/pull/1124


## [2.0.1] - 2022-02-09

### Added

* Fix incorrect return type by `route` on Request class by @fragkp in https://github.com/nunomaduro/larastan/pull/1113

### Changed

* refactor: replace composer with copy of ClassMapGenerator by @canvural in https://github.com/nunomaduro/larastan/pull/1119

## [2.0.0] - 2022-01-26

### Changed

- Dropped support for Laravel versions older than 9.
- Moved all Collection stubs to Laravel core.
### Added
* Infer type of model `id` without migrations by @sebdesign in https://github.com/nunomaduro/larastan/pull/1057
* Enable Testbench package discovery by @mewejo in https://github.com/nunomaduro/larastan/pull/1093

## [1.0.3] - 2022-01-20

* Avoid #1059 by @szepeviktor in https://github.com/nunomaduro/larastan/pull/1062
* Fix SchemaAggregation when renaming tables through non-static method call by @N-Silbernagel in https://github.com/nunomaduro/larastan/pull/1065
* fix: Call to an undefined method Eloquent\Builder::restore by @ryo-imai-bit in https://github.com/nunomaduro/larastan/pull/1061
* fix: check for trait with getDeclaringTrait if method is declared in a trait
* fix: use container to get the FunctionCallParametersCheck class in test

## [1.0.2] - 2021-11-23

### Added
* Throw types for macros by @canvural in https://github.com/nunomaduro/larastan/pull/1013
* Improve return types for Collection::first, last, get and pull when default value is given. by @bastien-phi in https://github.com/nunomaduro/larastan/pull/1010

### Fixed
* Respect ancestor property tags in model extensions by @spawnia in https://github.com/nunomaduro/larastan/pull/1037
* Correct method reflection for magic `with*` calls on `RedirectResponse` by @canvural in https://github.com/nunomaduro/larastan/pull/1016
* Eloquent collection has array-key for the key type by @canvural in https://github.com/nunomaduro/larastan/pull/1027
* Laravel container can have scalar bindings by @canvural in https://github.com/nunomaduro/larastan/pull/1028
* `tap` helper should not enforce any return type for the callback by @tpetry in https://github.com/nunomaduro/larastan/pull/1031

### Changed
* Improve return types for Collection `first`, `firstWhere`, `last` by @sebdesign in https://github.com/nunomaduro/larastan/pull/1025
* Update default config to match new highest level by @felixdorn in https://github.com/nunomaduro/larastan/pull/1014

## [1.0.1] - 2021-11-06

### Changed
* Fix `groupBy` Collection method signature
* Renamed `CheckJobDispatchArgumentTypesCompatibleWithClassConstructorRule` to `CheckDispatchArgumentTypesCompatibleWithClassConstructorRule` by @canvural in https://github.com/nunomaduro/larastan/pull/1003
* `CheckDispatchArgumentTypesCompatibleWithClassConstructorRule` now checks for argument types compatibility with the class constructor for both Event and Job classes. by @canvural in https://github.com/nunomaduro/larastan/pull/1003

### Improvements
* Clarified "static analysis" wording in docs and logo by @asgrim in https://github.com/nunomaduro/larastan/pull/1008

## [1.0.0] - 2021-11-01

### Added
- Dynamic method return type extension for `Enumerable::filter` in https://github.com/nunomaduro/larastan/pull/981
- New rule to check for relation existence  in https://github.com/nunomaduro/larastan/pull/985
- `rescue` parameter of `rescue` function now accepts the `Throwable` by @sebdesign in https://github.com/nunomaduro/larastan/pull/989
- New `CheckJobDispatchArgumentTypesCompatibleWithClassConstructorRule` rule in https://github.com/nunomaduro/larastan/pull/991
- Added `non-empty-string` types in stubs. https://github.com/nunomaduro/larastan/commit/c5b81cfee07686e2ce0ea557f8e32f2f022d47eb

### Fixed
- PHPStan 1.0 compatibility in https://github.com/nunomaduro/larastan/pull/968

### BC breaking changes
- `JsonResource` class is no longer generic. You can remove `@template` tags from your resource classes and directly add `@mixin Model` tags to your resource classes. https://github.com/nunomaduro/larastan/commit/37f9ce80890a67a02b1d55bf79eb73604880c6b9

## [0.7.15] - 2021-10-26

### Fixed

- Removed tags from conditional rules.

## [0.7.14] - 2021-10-25

### Fixed

- Fixed template resolution when `flatMap`'s callback returns an `Enumerable` ([#970](https://github.com/nunomaduro/larastan/pull/970)) Thanks @bastien-phi

### Added

- Detection of fields added/modified a migration $table->after() method (Added in Laravel 8.27). ([#972](https://github.com/nunomaduro/larastan/pull/972)) Thanks @jimohalloran
- Add new rule `NoModelMake` to prevent inefficient model instantiation ([#976](https://github.com/nunomaduro/larastan/pull/976)) Thanks @spawnia

## [0.7.13] - 2021-10-14

### Fixed

- Fixed `Dynamic call to static method` error for regular methods to work with [`phpstan-strict-rules`](https://github.com/phpstan/phpstan-strict-rules). ([#483](https://github.com/nunomaduro/larastan/issues/483), [#917](https://github.com/nunomaduro/larastan/pull/917))

### Added

- Support global macros on the eloquent query builder.
- Partial support for defining columns with `foreignIdFor` in migrations. ([#932](https://github.com/nunomaduro/larastan/pull/932)) Thanks @Josh-G
- Extension for `collect` helper and `Collection::make` ([#904](https://github.com/nunomaduro/larastan/pull/904))
- Support for scopes defined in model docblocks ([88422fb](https://github.com/nunomaduro/larastan/commit/88422fb90e85385245fb1badcaf5fbd8e0a1530d))
- `rescue` helper stub ([#961](https://github.com/nunomaduro/larastan/pull/961)) Thanks @jrmajor
- Added `trait_exists` to ensure `orchestra/testbench` is installed for packages. ([#957](https://github.com/nunomaduro/larastan/pull/957)) Thanks @rajyan

## [0.7.12] - 2021-07-26

### Added

- Support for `AsArrayObject` and `AsCollection` model casts.
- Use bleeding edge config to run tests.

### Fixed

- Corrected stubs from bleeding edge feedback.
- Changed `EloquentBuilder` stub to fix several relation builder methods return types when custom builder is used. ([#899](https://github.com/nunomaduro/larastan/pull/899)) Thanks @fragkp
- Changed `EloquentBuilder` stub to fix `whereHas` return type when custom builder is used. ([#896](https://github.com/nunomaduro/larastan/pull/896)) Thanks @fragkp

### Changed

- Refactored tests to separate Laravel 8 specific tests.

## [0.7.11] - 2021-07-22

### Added
- Support for Laravel 8 model factories. ([#861](https://github.com/nunomaduro/larastan/pull/861), [`6a03ad7`](https://github.com/nunomaduro/larastan/commit/6a03ad7899f5b71133c459df0260e19491dc5ed4))
- Support for `orWhere` HigherOrderBuilderProxy property. ([#884](https://github.com/nunomaduro/larastan/pull/884)) Thanks @shaffe-fr
- Added more precise declarations to Factory stub ([#888](https://github.com/nunomaduro/larastan/pull/888)) Thanks @spawnia

### Changed
- Laravel 9 tests cannot run on PHP 7.4

## [0.7.10] - 2021-07-07

### Fixed

- Fixed an issue when using `return null` in PHPDocs forced the `return` keyword on the code. ([1e85de7](https://github.com/nunomaduro/larastan/commit/1e85de7b2632bab4db09154389d309bece973c2e))

## [0.7.9] - 2021-07-05

### Fixed

- Fixed an issue where accessing `dateClass` property directly causing issues. ([#865](https://github.com/nunomaduro/larastan/pull/865))

## [0.7.8] - 2021-07-02

### Fixed

- Revert "fix bootstrap path" ([#858](https://github.com/nunomaduro/larastan/pull/858))

## [0.7.7] - 2021-07-01

### Added

- Use `checkExplicitMixed` PHPStan option when running tests ([#844](https://github.com/nunomaduro/larastan/pull/844))
- Added .github folder to the export list ([#849](https://github.com/nunomaduro/larastan/pull/849)) Thanks @dingo-d
- Added support for detecting nested trait usage for SoftDeletes trait ([#838](https://github.com/nunomaduro/larastan/pull/838)) Thanks @IAmRGroot
- Added support for vendor-dir ([#850](https://github.com/nunomaduro/larastan/pull/850)) Thanks @rajyan


### Fixed

- Correct return type for restore method
- Nullable properties should not be overwritten when applying model casts
- Fixed suggest in Composer config ([#846](https://github.com/nunomaduro/larastan/pull/846)) Thanks @szepeviktor
- Fixed bootstrap path ([#852](https://github.com/nunomaduro/larastan/pull/852)) Thanks @rajyan


## [0.7.6] - 2021-05-18

### Fixed

- Update link to Octane dependency injection docs ([#829](https://github.com/nunomaduro/larastan/pull/829)) Thanks @spawnia

## [0.7.5] - 2021-04-29

### Added

- Support for `sole` method on Eloquent builder ([#822](https://github.com/nunomaduro/larastan/pull/822)) Thanks @ThibaudDauce

### Changed

- Cleanup call forwarding logic in extensions ([#816](https://github.com/nunomaduro/larastan/pull/816))

### Fixed

- `increment` and `decrement` methods on query builder has side effects

## [0.7.4] - 2021-04-16

### Fixed

- Correct `when` and `unless` return value ([#807](https://github.com/nunomaduro/larastan/pull/807), [#811](https://github.com/nunomaduro/larastan/pull/811)) Thanks @netpok and @BertvanHoekelen

## [0.7.3] - 2021-04-12

### Added

- Octane compatibility checking rule ([#809](https://github.com/nunomaduro/larastan/pull/809))

## [0.7.2] - 2021-04-08

### Fixed

- PHPStan 0.12.82 compatibility ([#801](https://github.com/nunomaduro/larastan/pull/801))

## [0.7.1] - 2021-03-19

### Added

- Support for HigherOrderCollectionProxy ([#781](https://github.com/nunomaduro/larastan/pull/781))
- Support for Illuminate\Database\Query\Expression as column ([#784](https://github.com/nunomaduro/larastan/issues/784))
- Support for `when` and `unless` methods on Eloquent builder ([#791](https://github.com/nunomaduro/larastan/pull/791))

### Fixed

- Fixed incorrectly inferring the return type of custom methods that return Collections ([#751](https://github.com/nunomaduro/larastan/pull/751)) Thanks @Josh-G

### Changed

- Locked phpstan/phpstan dependency to v0.12.81

## [0.7.0] - 2021-02-01

### Changed

- Updated `phpstan/phpstan` from v0.12.65 to v0.12.70 ([#774](https://github.com/nunomaduro/larastan/pull/774)) Thanks @chrisp-github

### Added

- **BREAKING**: Changed `databaseMigrationsPath` to be an array instead of a string, so it allows multiple directories. ([#777](https://github.com/nunomaduro/larastan/pull/777)) Thanks @haringsrob
- Support for Log facade ([#771](https://github.com/nunomaduro/larastan/pull/771)) Thanks @Pe46dro

## [0.6.13] - 2021-01-22

### Added

- DB facade stub ([8aa4b6d](https://github.com/nunomaduro/larastan/commit/8aa4b6dc3407fad6ced8115da02f610aad55366f))
- Queue facade stub ([d0b0909](https://github.com/nunomaduro/larastan/commit/d0b0909c3ef60d45d3db9a2cf1fb91a73711ef26))
- Logger stub ([208df29](https://github.com/nunomaduro/larastan/commit/208df29dab0ad80cabd6dc1b4e2232fc0f1044a0))

### Changed

- Use updated excludePaths parameter ([#759](https://github.com/nunomaduro/larastan/pull/759)) Thanks @erikgaal
- Improve Pipes\Facades ([#744](https://github.com/nunomaduro/larastan/pull/744)) Thanks @szepeviktor

### Fixed

- Fixed insertOrIgnore typehint ([#756](https://github.com/nunomaduro/larastan/pull/756)) Thanks @bistory
- Fix BelongsToMany stub's file name ([#736](https://github.com/nunomaduro/larastan/pull/736)) Thanks @szepeviktor

## [0.6.12] - 2021-01-03

### Added

- Support to Laravel 9 ([060aa1d](https://github.com/nunomaduro/larastan/commit/060aa1df1a63f4861ebce541257c77f4038c43c6))
- Support for `Model::whereNotBetween()` ([#731](https://github.com/nunomaduro/larastan/pull/731))

### Fixed
- Search missing method also in templated model ([#739](https://github.com/nunomaduro/larastan/pull/739))

## [0.6.11] - 2020-12-07

### Added

- Support for autoloaded arrays ([#725](https://github.com/nunomaduro/larastan/pull/725)) Thanks @stancl

### Fixed

- Changed bootstrap file path ([#727](https://github.com/nunomaduro/larastan/pull/727)) Thanks @bepsvpt
- Removed mixin pipe class ([#704](https://github.com/nunomaduro/larastan/pull/704))
- Don't return dummy method reflection for unknown methods ([2581f3b](https://github.com/nunomaduro/larastan/commit/2581f3b255a6ad3f38301d088557a0ddc598dbf8))

## [0.6.10] - 2020-11-26

### Added

- Added `dd` to list of early terminating functions ([#710](https://github.com/nunomaduro/larastan/pull/710))

### Fixed

- Model::with() accepts array of Closure ([#713](https://github.com/nunomaduro/larastan/pull/713)) Thanks @kvas-damian
- Use function map instead of calling newQuery on model ([#717](https://github.com/nunomaduro/larastan/pull/717))

## [0.6.9] - 2020-10-30

### Added

- Support to PHP 8 ([#701](https://github.com/nunomaduro/larastan/pull/701))

### Fixed

- Corrected return type for some QueryBuilder methods ([#702](https://github.com/nunomaduro/larastan/pull/702))

## [0.6.8] - 2020-10-23

### Added

- Dynamic return type support for `$request->file()` method call.

### Fixed

- Fixed an issue with running tests on Windows ([#696](https://github.com/nunomaduro/larastan/pull/696)) Thanks @kwebble
- Fixed autoloading of autoload-dev classes during bootstrap. ([#696](https://github.com/nunomaduro/larastan/pull/696)) Thanks @kwebble

## [0.6.7] - 2020-10-21

### Fixed

- `SoftDeletes` methods on relations are no longer marked as undefined ([#692](https://github.com/nunomaduro/larastan/pull/692)) Thanks @jdrieghe
- Generic model type is preserved when `with` method is used on a model instance.

## [0.6.6] - 2020-10-17

### Added

- Support for checking model properties on dynamic wheres ([#686](https://github.com/nunomaduro/larastan/pull/686))

## [0.6.5] - 2020-10-15

This release introduces a new rule that can check the arguments of methods that expects a model property name, and can warn you if the passed argument is not actually a property of the model. You can read the details about the rule [here](https://github.com/nunomaduro/larastan/blob/master/docs/rules.md#modelpropertyrule).

**NOTE**: This rule is currently in beta! If you want to improve it's analysis, you can check out the issue [here](https://github.com/nunomaduro/larastan/issues/676) and contribute!


### Added
- Add a new `view-string` PHPDoc type ([#654](https://github.com/nunomaduro/larastan/pull/654))
- Stubs for Eloquent builder `value` and `orWhere` methods

### Fixed
- Parameter type of the query builder's `where`, `orWhere` and `addArrayOfWheres` ([#651](https://github.com/nunomaduro/larastan/pull/651)).
- Fix callback parameters for `retry` ([#663](https://github.com/nunomaduro/larastan/pull/663)).
- Using Reflection to initiate a model in `ModelPropertyExtension` to avoid errors caused by using Model constructor. ([#666](https://github.com/nunomaduro/larastan/pull/666))

### Changed
- Made improvements to database migrations scanning. ([#670](https://github.com/nunomaduro/larastan/pull/670))
- Improved running test suite commands and Windows compatibility ([#682](https://github.com/nunomaduro/larastan/pull/682))

## [0.6.4] - 2020-09-02

### Changed
- Update `orchestra/testbench` version to allow Laravel 8 installations ([#646](https://github.com/nunomaduro/larastan/pull/646))

### Fixed
- Return type of `firstWhere` method on model, builder and relations ([#649](https://github.com/nunomaduro/larastan/pull/649))

## [0.6.3] - 2020-08-31

### Added

- Return type extension for `validator` helper ([#641](https://github.com/nunomaduro/larastan/pull/641))

### Fixed

- Return type of `associate`, `dissociate` and `getChild` methods of `BelongsTo` relations ([#633](https://github.com/nunomaduro/larastan/pull/633))

## [0.6.2] - 2020-07-30

### Fixed

- Fix false positive for NoUnnecessaryCollectionCallRule when statically calling hydrate on a Model class. ([#609](https://github.com/nunomaduro/larastan/pull/609))
- Fixed slightly incorrect stubs for accepted $values for `whereBetween`/`orWhereBetween` and `whereRowValues`/`orWhereRowValues` ([#626](https://github.com/nunomaduro/larastan/pull/626))
- Check if facade fake exists ([852c131](https://github.com/nunomaduro/larastan/commit/852c1313e1c94feab8da8055d0abceda97a27586))
- Correct query builder stub ([d8d8b41](https://github.com/nunomaduro/larastan/commit/d8d8b41d91d8934fa5a1d44908b37c344af87472))
- Dont override return type when eloquent builder returns a query builder instance ([b5f96b4](https://github.com/nunomaduro/larastan/commit/b5f96b4df2d0931a6eb00a5c6539c56df2d67bd9))
- Handle the case when a custom query builder does not have generic annotations ([c54b517](https://github.com/nunomaduro/larastan/commit/c54b5179e72b8ea18e724c50bf6a9c36af79d52d))
- Soft delete trait methods should return generic Eloquent builder with model ([023043b](https://github.com/nunomaduro/larastan/commit/023043b05c9ec04ef92f32960315621fdfe21100))

### Added

- Support for Redis facade ([#615](https://github.com/nunomaduro/larastan/issues/615)) ([a42b2f6](https://github.com/nunomaduro/larastan/commit/a42b2f65448548e725f5d4b1bf1c4fe9840c4262))
- Auth guard with multiple dynamic auth models ([#605](https://github.com/nunomaduro/larastan/issues/605)) ([63a3934](https://github.com/nunomaduro/larastan/commit/63a393433805a1142fbffe507875f2af825d11fd))
- Use latest version of Composer in travis ([#599](https://github.com/nunomaduro/larastan/issues/599)) ([29a9023](https://github.com/nunomaduro/larastan/commit/29a9023d19750298ae7b656e989ebef221925fb9))

## [0.6.1] - 2020-06-20

### Added
- Support for dynamic auth model loading from config. Thanks @0xb4lint ([#602](https://github.com/nunomaduro/larastan/pull/602))
- Support for Laravel 8

### Fixed
- Fix false positive when calling `tap($this)` ([#601](https://github.com/nunomaduro/larastan/pull/601))

## [0.6.0] - 2020-06-10

### Added
- Document common errors for users to ignore ([#564](https://github.com/nunomaduro/larastan/pull/564))
- Add `abort` to `earlyTerminatingFunctionCalls` config option ([#567](https://github.com/nunomaduro/larastan/pull/567))
- Support for `tap` helper. ([#575](https://github.com/nunomaduro/larastan/pull/575))
- Bumped minimum PHPStan version to 0.12.28

### Fixed
- Avoid false-positive when calling static builder methods such as `::find()` on Model classes where
  the concrete subclass is not yet known ([#565](https://github.com/nunomaduro/larastan/pull/565))
- Use correct argument order for `Str::startsWith` ([#570](https://github.com/nunomaduro/larastan/pull/570))

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
- Fixed a bug with Composer autoloading. Thanks @ondrejmirtes ([#561](https://github.com/nunomaduro/larastan/pull/561))
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
- Support for code analysis in Laravel Packages

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

[Unreleased]: https://github.com/nunomaduro/larastan/compare/v2.2.0...HEAD
[2.2.0]: https://github.com/nunomaduro/larastan/compare/v2.1.12...v2.2.0
[2.1.8]: https://github.com/nunomaduro/larastan/compare/v2.1.7...v2.1.8
[2.1.7]: https://github.com/nunomaduro/larastan/compare/v2.1.6...v2.1.7
[2.1.6]: https://github.com/nunomaduro/larastan/compare/v2.1.5...v2.1.6
[2.1.5]: https://github.com/nunomaduro/larastan/compare/v2.1.4...v2.1.5
[2.1.4]: https://github.com/nunomaduro/larastan/compare/v2.1.3...v2.1.4
[2.1.3]: https://github.com/nunomaduro/larastan/compare/v2.1.2...v2.1.3
[2.1.0]: https://github.com/nunomaduro/larastan/compare/2.0.1...2.1.0
[2.0.1]: https://github.com/nunomaduro/larastan/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/nunomaduro/larastan/compare/1.0.3...2.0.0
[1.0.3]: https://github.com/nunomaduro/larastan/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/nunomaduro/larastan/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/nunomaduro/larastan/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/nunomaduro/larastan/compare/v0.7.15...1.0.0
[0.7.15]: https://github.com/nunomaduro/larastan/compare/v0.7.14...0.7.15
[0.7.14]: https://github.com/nunomaduro/larastan/compare/v0.7.13...0.7.14
[0.7.13]: https://github.com/nunomaduro/larastan/compare/v0.7.12...0.7.13
[0.7.12]: https://github.com/nunomaduro/larastan/compare/v0.7.11...0.7.12
[0.7.11]: https://github.com/nunomaduro/larastan/compare/v0.7.10...0.7.11
[0.7.10]: https://github.com/nunomaduro/larastan/compare/v0.7.9...0.7.10
[0.7.9]: https://github.com/nunomaduro/larastan/compare/v0.7.8...0.7.9
[0.7.8]: https://github.com/nunomaduro/larastan/compare/v0.7.7...0.7.8
[0.7.7]: https://github.com/nunomaduro/larastan/compare/v0.7.6...0.7.7
[0.7.6]: https://github.com/nunomaduro/larastan/compare/v0.7.5...0.7.6
[0.7.5]: https://github.com/nunomaduro/larastan/compare/v0.7.4...0.7.5
[0.7.4]: https://github.com/nunomaduro/larastan/compare/v0.7.3...0.7.4
[0.7.3]: https://github.com/nunomaduro/larastan/compare/v0.7.2...0.7.3
[0.7.2]: https://github.com/nunomaduro/larastan/compare/v0.7.1...0.7.2
[0.7.1]: https://github.com/nunomaduro/larastan/compare/v0.7.0...v0.7.1
[0.7.0]: https://github.com/nunomaduro/larastan/compare/v0.6.13...v0.7.0
[0.6.13]: https://github.com/nunomaduro/larastan/compare/v0.6.12...v0.6.13
[0.6.12]: https://github.com/nunomaduro/larastan/compare/v0.6.11...v0.6.12
[0.6.11]: https://github.com/nunomaduro/larastan/compare/v0.6.10...v0.6.11
[0.6.10]: https://github.com/nunomaduro/larastan/compare/v0.6.9...v0.6.10
[0.6.9]: https://github.com/nunomaduro/larastan/compare/v0.6.8...v0.6.9
[0.6.8]: https://github.com/nunomaduro/larastan/compare/v0.6.7...v0.6.8
[0.6.7]: https://github.com/nunomaduro/larastan/compare/v0.6.6...v0.6.7
[0.6.6]: https://github.com/nunomaduro/larastan/compare/v0.6.5...v0.6.6
[0.6.5]: https://github.com/nunomaduro/larastan/compare/v0.6.4...v0.6.5
[0.6.4]: https://github.com/nunomaduro/larastan/compare/v0.6.3...v0.6.4
[0.6.3]: https://github.com/nunomaduro/larastan/compare/v0.6.2...v0.6.3
[0.6.2]: https://github.com/nunomaduro/larastan/compare/v0.6.1...v0.6.2
[0.6.1]: https://github.com/nunomaduro/larastan/compare/v0.6.0...v0.6.1
[0.6.0]: https://github.com/nunomaduro/larastan/compare/v0.5.8...v0.6.0
[0.5.8]: https://github.com/nunomaduro/larastan/compare/v0.5.7...v0.5.8
[0.5.7]: https://github.com/nunomaduro/larastan/compare/v0.5.6...v0.5.7
[0.5.6]: https://github.com/nunomaduro/larastan/compare/v0.5.5...v0.5.6
[0.5.5]: https://github.com/nunomaduro/larastan/compare/v0.5.4...v0.5.5
[0.5.4]: https://github.com/nunomaduro/larastan/compare/v0.5.3...v0.5.4
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
