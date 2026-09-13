# Larastan contributor guidance

Larastan adds Laravel-aware analysis to PHPStan. Find the nearest existing
implementation and follow its complete pattern before adding a new one.

## Sources of truth

- `composer.json` defines supported PHP, Laravel, PHPStan, and PHPUnit versions.
- `extension.neon` defines services, extension tags, parameters, and defaults.
- `CONTRIBUTING.md` documents setup and contribution expectations.
- `.github/workflows/` defines the complete CI matrix.

Do not copy version numbers or CI matrices into documentation. Link to their
source instead so guidance does not drift.

When using an unfamiliar PHPStan API, check its implementation and tests against
the installed dependency. A separate `phpstan-src` checkout or the online API
reference may be newer than the version Larastan supports.

## Where a change belongs

### For source code
- Dynamic return types belong in `src/ReturnTypes/`.
- Method and property discovery extensions belong in `src/Methods/` and
  `src/Properties/`; reusable PHPStan reflection implementations belong in
  `src/Reflection/`.
- Custom PHPStan types and type-node resolvers belong in `src/Types/`.
- Diagnostics belong in `src/Rules/`; cross-file data belongs in
  `src/Collectors/`.
- PHPStan stubs belong in `stubs/`.

### For tests
- Isolated behavior belongs in `tests/Unit/`, reflection extension behavior in
  `tests/Reflection/`, inline type inference in `tests/Type/`, rule diagnostics
  in `tests/Rules/`, and full-file analysis in `tests/Integration/`.
- Shared application classes, configuration, migrations, views, and providers
  belong in `tests/application/`.
- External-application compatibility belongs in `e2e/`.

Keep related implementation, registration, tests, and documentation together.
Check sibling extensions for the full set of files a change requires.

## Before changing behavior

- Reproduce a bug with the smallest test that fails before the fix. Start from
  the reported reproducer and preserve the syntax that triggers the failure;
  once it fails for the right reason, keep that code stable while fixing it.
- To inspect inference, use `\PHPStan\dumpType($expr)` or
  `\PHPStan\debugScope()` in a temporary reproducer analysed with the relevant
  Larastan configuration. Use PHPStan's `--debug` flag to see the file being
  analysed; add `-vvv` when investigating hangs.
- When existing behavior looks deliberate or version-dependent, inspect git
  history and the relevant Laravel and PHPStan source, tests, PRs, or issues
  before replacing it.
- Establish framework semantics from upstream tests or a focused runtime probe
  before encoding them as PHPStan types.
- Before creating an extension, check whether PHPDoc in stubs, including
  generics, conditional return types, and assertions, can express the behavior
  clearly and accurately. Prefer stubs when they can; otherwise use the smallest
  dedicated extension point that fixes the root cause. Avoid speculative
  helpers, generic machinery, and broad refactors.
- Keep unrelated discoveries out of the current change; report or fix them
  separately.
- If a test describes correct runtime behavior, fix Larastan's implementation,
  stub, or declaration instead of weakening the assertion.
- Keep inferred types both sound and useful on realistic Laravel code. Do not
  broaden a type merely to satisfy a test or narrow it merely to hide errors.
- Do not silence new failures with the baseline or ignore rules.
- For performance regressions, identify and fix the algorithmic cause while
  preserving inference precision. Iteration, recursion, or collection-size
  caps are safety nets, not substitutes for fixing the expensive operation.

## Extension wiring and contracts

- Choose the hook for the information being changed: dynamic return type
  extensions refine the call's result; type-specifying extensions narrow other
  expressions after assertions or in conditional branches; parameter closure
  extensions supply callback parameter types or the callback's `$this` type.
  Rules report diagnostics and should not mutate analysis scope.
- In type-specifying extensions, handle `TypeSpecifierContext` explicitly for
  the supported truthy, falsey, or standalone assertion contexts. Obtain
  `TypeSpecifier` through `TypeSpecifierAwareExtension` to avoid a circular
  constructor dependency. Consult the
  [type-specifying extension guide](https://phpstan.org/developing-extensions/type-specifying-extensions)
  when adding narrowing behavior.
- For cross-file diagnostics, return data from collectors and consume it in a
  rule for `CollectedDataNode`. Analysis can run in separate worker processes;
  mutable service state cannot aggregate findings across all files.
- Prefer PHPStan APIs marked `@api`; unmarked internals can change in minor
  releases. Respect `@api-do-not-implement` on interfaces. Inject PHPStan
  services rather than constructing internal implementations whose
  constructors are outside the compatibility promise.
- Register extensions in `extension.neon` with the tag required by their
  PHPStan interface. For feature-gated tags or explicit `active` arguments,
  follow the nearest existing registration pattern.
- Add every Larastan-owned public configuration parameter to both `parameters`
  and `parametersSchema`, then document it in
  `docs/custom-config-parameters.md` or, for rule-specific settings, alongside
  the rule in `docs/rules.md`.
- New diagnostic identifiers use the `larastan.` prefix and dotted camelCase
  segments, such as `larastan.console.undefinedArgument`.
  `rules.modelAppends` is a legacy outlier; do not copy it.
- Document user-visible rules in `docs/rules.md`, custom types in
  `docs/custom-types.md`, and other features in `docs/features.md`.
- Reflection extensions that implement `hasMethod()`/`getMethod()` or
  `hasProperty()`/`getProperty()` must make both calls agree. When `has*()` does
  runtime-, container-, or resolution-dependent discovery, cache the found
  reflection under the same stable key for `get*()`; deterministic extensions
  may reconstruct it.
- For dynamic return type extensions, return `null` when the extension cannot
  refine the call. For other extension types, follow their documented fallback
  semantics. Use `ErrorType` only for a genuinely invalid expression, following
  the nearest sibling.
- PHPStan extension code that resolves services from the booted Laravel
  application should use the existing `HasContainer` mechanism. Its `resolve()`
  method deliberately returns `null` when the application cannot resolve a
  service; preserve that fallback.

## Stub files and Laravel versions

- Shared stubs live in `stubs/common/`; version-specific overrides live in the
  numbered directories.
- Stub discovery keeps only the newest applicable file for each relative path.
  A versioned file therefore replaces the whole earlier file; it is not merged
  declaration by declaration.
- Put a signature in the earliest version where it is valid and add a versioned
  override only where the signature changes.
- Keep version-specific tests at the same boundary as the corresponding stub or
  framework behavior. Use `laravel_version_compare()` like nearby tests.

## PHPStan type handling

### Queries and transformations

- When identifying a type, prefer its query methods or `isSuperTypeOf()`. Use
  `accepts()` for assignment or argument compatibility. Use `instanceof` only
  for representation-specific behavior the `Type` API does not expose:
  concrete-class checks miss unions and intersections with accessory types.
- Check the `Type` API for existing operations such as `getConstantStrings()`,
  `getConstantArrays()`, and `getObjectClassReflections()` before adding manual
  dispatch over concrete type classes.
- Use `equals()` for type equality. Reserve `describe()` for human-readable
  output; compare, sort, and transform the underlying types or constant values
  instead of their rendered descriptions.
- Build unions and intersections with `TypeCombinator`.
- Handle all three `TrinaryLogic` outcomes deliberately: yes, no, and maybe.
  `! $result->no()` includes maybe; it is not equivalent to `$result->yes()`.
- Use `TypeTraverser::map()` for nested transformations. The callback controls
  recursion: `return $traverse($type)` visits children, while `return $type`
  stops at that node. Limit traversal to unions/intersections when transforming
  only top-level alternatives; descending into generic arguments or callable
  signatures can change unrelated types.
- Build array shapes with `ConstantArrayTypeBuilder::createEmpty()`,
  `setOffsetValueType($key, $value, $optional)`, and `getArray()`. Preserve
  optional keys when transforming shapes; optional and nullable are different.
  For general iterables, query `getIterableKeyType()`/`getIterableValueType()`.

### Generics and class identity

- Preserve generic bindings by working with the receiver's `Type` or
  `getObjectClassReflections()`. Looking up its class name again through
  `ReflectionProvider` loses that receiver's concrete type arguments.
- Read a known ancestor's template through
  `$type->getTemplateType(Ancestor::class, 'TModel')`, or obtain the ancestor
  reflection with `getAncestorWithClassName()` and use its active template map.
  `getActiveTemplateTypeMap()` contains substitutions when available;
  `getTemplateTypeMap()` describes the declared templates. Handle missing
  ancestors/templates using the extension's fallback.
- When constructing `GenericObjectType`, follow the target class's declared
  template order and preserve any relevant call-site variance. Keep unresolved
  templates and `StaticType`/`ThisType` semantics until the operation actually
  requires resolving them; replacing them with bounds or plain `ObjectType`
  loses relationships needed at the call site.
- `getObjectClassNames()` identifies the direct object types;
  `getReferencedClasses()` also includes classes nested inside generic arguments
  and callable signatures. Use the former to identify a receiver.

## PHPStan scope, reflection, and calls

- Use the public `Scope` interface in extensions. `getType($expr)` includes
  PHPDoc information; use `getNativeType($expr)` when the behavior specifically
  concerns native types. `MutatingScope` is PHPStan's internal implementation.
- For classes from the analysed project, inject `ReflectionProvider` and use
  `hasClass()`/`getClass()` instead of `class_exists()`. Runtime dependency
  checks for Larastan's own dependencies may use `class_exists()`.
- On a `Type`, establish `hasMethod($name)->yes()` before `getMethod()`;
  corresponding `ClassReflection` queries return booleans. Pass the actual
  `$scope` when member access depends on the caller. Use `OutOfClassScope` only
  when intentionally modelling access from outside a class.
- `ClassReflection::getMethod()` includes methods supplied by extensions;
  `getNativeMethod()` looks for a real declared/inherited method. Choose
  deliberately, especially inside reflection extensions where querying the
  same virtual member can recursively re-enter the extension.
- Keep property read and write types distinct: use `getReadableType()` for
  reads and `getWritableType()` for assignments. Laravel accessors and mutators
  can expose different types.
- When caching a reflection that depends on generic bindings, include
  `ClassReflection::getCacheKey()` rather than only the class name, plus the
  member and any other inputs that affect the result.
- For a concrete call, select its signature with
  `ParametersAcceptorSelector::selectFromArgs($scope, $args, $variants, $namedVariants)`.
  Supply `getNamedArgumentsVariants()` when exposed by the reflection.
  `getVariants()[0]` bypasses overload selection and call-site template inference;
  reserve it for inspecting a known single declaration. See the
  [reflection guide](https://phpstan.org/developing-extensions/reflection).
- Raw call arguments in rules may be named or unpacked. Before indexing by
  parameter position, use the selected signature with `ArgumentsNormalizer`
  and handle its `null` result. PHPStan already normalizes calls passed to
  dynamic return type extensions; check the calling contract before adding
  another normalization step. Still handle missing arguments and unpacking.
- For arbitrary callables, establish `isCallable()->yes()` and use
  `getCallableParametersAcceptors($scope)`. A callable can be a closure, string,
  array, or invokable object; select its signature using the actual arguments.

## Custom PHPDoc resolution and types

- Reuse resolved PHPDoc information from reflection. When parsing a custom
  type node, resolve its children through `TypeNodeResolver` with the original
  `NameScope`, preserving imports, aliases, and template names. Return `null`
  for syntax the extension does not own; resolving the same outer node again
  would re-enter the extension.
- Obtain `TypeNodeResolver` via `TypeNodeResolverAwareExtension` setter
  injection; constructor injection creates a circular dependency.
- When a custom type's result depends on unresolved templates, follow the
  `LateResolvableType`/`LateResolvableTypeTrait` pattern in
  `src/Types/CollectionOf/` and `src/Types/RelationOf/`. Preserve the operands
  until substitution makes the result resolvable. Consult the
  [custom PHPDoc type guide](https://phpstan.org/developing-extensions/custom-phpdoc-types).
- For custom types containing other types, keep `equals()`, `traverse()`,
  `traverseSimultaneously()`, `getReferencedClasses()`,
  `getReferencedTemplateTypes()`, and `toPhpDocNode()` consistent with every
  stored operand. Otherwise template substitution or dependency tracking can
  miss part of the type even when `describe()` looks correct.

## Test fixtures and expectations

- Register every new or renamed type fixture in a `dataFileAsserts()` provider
  via `gatherAssertTypes()`. General fixtures go in `GeneralTypeTest`;
  feature-specific fixtures go in the existing test class for that extension.
  Register integration fixtures in `IntegrationTest::dataIntegrationTests()`.
- Keep fixture class and function names globally unique. Prefer namespaces;
  unnamespaced fixtures are acceptable when their symbols cannot collide.
- Put tests with the behavior they exercise, even when another fixture could
  technically host the assertion.
- Pair integration fixtures with any required code in `tests/application/` and
  use the test configuration that enables the feature under test.
- Put supported-version conditions in the data provider, not inside the
  fixture. Keep the boundary aligned with the corresponding stub or framework
  behavior.
- Type fixtures use inline `assertType()`/`assertNativeType()` assertions. Rule
  and integration fixtures use their existing expected-error formats; keep
  line numbers synchronized with the fixture.
- Integration expectations reject unexpected reported errors but do not prove
  that every item in a partially populated expected-error list was reported.
  When editing such expectations, verify each expected diagnostic actually
  occurs.
- Cover representative boundaries, not only the happy path: supported Laravel
  versions, calls inside and outside the declaring class, unions,
  intersections, nullability, generics, constant and mixed values, objects,
  framework contracts, and backed or unit enums where relevant.
- E2E projects are pinned in `.github/workflows/e2e-tests.yml` and run in CI.
  When one fails, review every added or removed entry in its regenerated
  baseline artifact as a user-visible behavior change.

## Writing PHPDocs

- Document non-obvious contracts and the role of key abstractions. Keep prose
  concise and focused on information absent from the method name and type tags.
- Preserve `@api`, `@phpstan-assert`, and tags that express information beyond
  native PHP types, such as generics, array shapes, and conditional types.
- Keep type tags on their own lines; avoid descriptions that merely restate
  them or explain ordinary PHP semantics.

## Verification

Run the narrowest relevant PHPUnit test or PHPStan path while iterating. Before
considering a change complete, run:

```bash
composer test:cs
composer test:types
composer test:unit
```

Once required checks pass, repeat or broaden verification only for subsequent
changes, failures, or unresolved concerns.

For compatibility-sensitive changes, use the relevant workflow or script in
`.github/workflows/` or `tests/laravel-test.sh` rather than inventing a local
matrix. Run the affected E2E project when a change reaches external Laravel
applications.

When editing `composer.json`, run `composer validate`. After adding or renaming
PHP classes, run `composer dump-autoload --optimize --strict-psr`. Do not mark
new files executable; CI permits only `tests/laravel-test.sh`.
